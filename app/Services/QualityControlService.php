<?php

namespace App\Services;

use App\Models\DesignCustomization;
use App\Models\DesignProductionPackage;
use App\Models\DesignProof;
use App\Models\Order;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\QualityCheck;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class QualityControlService
{
    public function __construct(
        protected AutomationTraceService $trace,
        protected ProductionOrchestrationService $production,
        protected ReworkService $reworks,
    ) {}

    public function resolveContext(Order $order): array
    {
        $design = DesignCustomization::query()
            ->with(['approvedProof', 'latestProductionPackage'])
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        $proof = $design?->approvedProof ?: DesignProof::query()
            ->where('design_customization_id', $design?->id)
            ->where('status', 'approved')
            ->latest('id')
            ->first();

        $package = $design?->latestProductionPackage ?: DesignProductionPackage::query()
            ->where('design_customization_id', $design?->id)
            ->latest('id')
            ->first();

        return [$design, $proof, $package];
    }

    public function latestForOrder(Order $order): ?QualityCheck
    {
        return QualityCheck::query()->where('order_id', $order->id)->latest('id')->first();
    }

    public function buildSummary(Order $order): array
    {
        [$design, $proof, $package] = $this->resolveContext($order);
        $latest = $this->latestForOrder($order);
        $latestRework = $this->reworks->latestForOrder($order);

        $simplified = match (true) {
            in_array($latestRework?->status, ['rework_open', 'rework_in_progress', 'rework_done', 'rework_recheck'], true) => 'order_under_correction',
            $latestRework?->status === 'rework_closed' => 'rework_completed',
            $latest?->qc_status === QualityCheck::STATUS_PASSED => 'passed_quality_check',
            in_array($latest?->qc_status, [QualityCheck::STATUS_IN_REVIEW, QualityCheck::STATUS_PENDING], true) => 'quality_check_ongoing',
            in_array($latest?->qc_status, [QualityCheck::STATUS_FAILED, QualityCheck::STATUS_REWORK_REQUIRED], true) => 'order_under_correction',
            in_array($order->current_stage, ['quality_check', 'ready_for_qc'], true) => 'quality_check_ongoing',
            default => null,
        };

        return [
            'has_passed' => (bool) ($latest?->isPassed()),
            'is_blocking_fulfillment' => ! ($latest?->isPassed()),
            'latest' => $latest,
            'latest_rework' => $latestRework,
            'current_status' => $latest?->qc_status ?: ($order->current_stage === 'quality_check' ? QualityCheck::STATUS_IN_REVIEW : QualityCheck::STATUS_PENDING),
            'client_status' => $simplified,
            'client_label' => match ($simplified) {
                'passed_quality_check' => 'Passed quality check',
                'quality_check_ongoing' => 'Quality check ongoing',
                'order_under_correction' => 'Order under correction',
                'rework_completed' => 'Rework completed',
                default => 'Awaiting quality check',
            },
            'design' => $design,
            'approved_proof' => $proof,
            'production_package' => $package,
        ];
    }

    public function record(Shop $shop, Order $order, User $actor, array $payload): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        return DB::transaction(function () use ($shop, $order, $actor, $payload) {
            [$design, $proof, $package] = $this->resolveContext($order);
            $checks = $this->normalizeChecks($payload['checks'] ?? []);
            $qcStatus = $this->normalizeStatus($payload['qc_status'] ?? null, $payload['result'] ?? null, (bool) ($payload['rework_required'] ?? false));
            $reworkRequired = in_array($qcStatus, [QualityCheck::STATUS_FAILED, QualityCheck::STATUS_REWORK_REQUIRED], true) || (bool) ($payload['rework_required'] ?? false);
            $result = Arr::get($payload, 'result') ?: ($qcStatus === QualityCheck::STATUS_PASSED ? 'pass' : ($reworkRequired ? 'fail' : 'in_review'));
            $attachments = collect($payload['evidence'] ?? [])->pluck('photo')->filter()->values()->all();
            $startedAt = $payload['started_at'] ?? now();

            $qualityCheck = QualityCheck::create([
                'shop_id' => $shop->id,
                'order_id' => $order->id,
                'design_customization_id' => $design?->id,
                'design_proof_id' => $proof?->id,
                'production_package_id' => $package?->id,
                'checked_by' => $actor->id,
                'qc_status' => $qcStatus,
                'qc_notes' => $payload['qc_notes'] ?? null,
                'defect_type' => $payload['defect_type'] ?? null,
                'defect_notes' => $payload['defect_notes'] ?? null,
                'qc_risk' => $payload['qc_risk'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'checks_json' => $checks,
                'evidence_json' => $payload['evidence'] ?? null,
                'result' => $result,
                'issue_notes' => $payload['issue_notes'] ?? ($payload['qc_notes'] ?? null),
                'attachments_json' => $attachments,
                'rework_required' => $reworkRequired,
                'action_taken' => $payload['action_taken'] ?? null,
                'started_at' => $startedAt,
                'checked_at' => now(),
                'passed_at' => $qcStatus === QualityCheck::STATUS_PASSED ? now() : null,
                'failed_at' => in_array($qcStatus, [QualityCheck::STATUS_FAILED, QualityCheck::STATUS_REWORK_REQUIRED], true) ? now() : null,
                'rework_opened_at' => $reworkRequired ? now() : null,
            ]);

            if ($package) {
                $risks = collect($package->risk_flags_json ?? [])->filter()->values()->all();
                if ($payload['qc_risk'] ?? null) {
                    $risks[] = ['type' => 'qc_risk', 'value' => $payload['qc_risk'], 'qc_id' => $qualityCheck->id, 'recorded_at' => now()->toDateTimeString()];
                }
                $package->update([
                    'qc_note' => $payload['qc_notes'] ?? $package->qc_note,
                    'risk_flags_json' => array_values($risks),
                ]);
            }

            if ($qcStatus === QualityCheck::STATUS_PASSED) {
                $order->update(['status' => 'in_production', 'current_stage' => 'packing']);
                $this->upsertStage($order, 'quality_check', 'done', $actor->id, 'QC passed.');
                $this->upsertStage($order, 'packing', 'active', $actor->id, 'QC passed; fulfillment can proceed.');

                $this->progress($order, 'qc_started', 'QC started', 'Structured quality check started.', $actor->id);
                $this->progress($order, 'qc_passed', 'QC passed', $payload['qc_notes'] ?? 'Embroidery output passed structured quality check.', $actor->id);
                $this->reworks->closeFromQcPass($order, $actor, 'QC passed after correction review.');

                $this->trace->notify($order->client_user_id, 'quality_check_passed', 'Passed quality check', 'Order '.$order->order_number.' passed quality check.', 'order', $order->id, [
                    'category' => 'production',
                    'priority' => 'medium',
                    'action_label' => 'Track order',
                ]);
            } elseif ($reworkRequired) {
                $order->update(['status' => 'in_production', 'current_stage' => 'rework']);
                $this->upsertStage($order, 'quality_check', 'failed', $actor->id, 'QC failed; rework opened.');
                $this->upsertStage($order, 'production', 'active', $actor->id, 'Rework triggered after QC failure.');
                $this->production->routeException($order, 'quality_rework', $payload['defect_notes'] ?? $payload['qc_notes'] ?? 'QC failed and rework is required.', 'high', $actor->id);
                $rework = $this->reworks->openFromQc($shop, $order, $qualityCheck, $actor, [
                    'reason' => $payload['defect_notes'] ?? $payload['qc_notes'] ?? 'QC failed and rework is required.',
                    'severity' => $payload['severity'] ?? $payload['qc_risk'] ?? $payload['defect_type'] ?? 'high',
                    'internal_note' => $payload['remarks'] ?? null,
                    'progress_notes' => $payload['action_taken'] ?? 'Rework route opened from quality control.',
                ]);

                $this->progress($order, 'qc_started', 'QC started', 'Structured quality check started.', $actor->id);
                $this->progress($order, 'qc_failed', 'QC failed', $payload['defect_notes'] ?? $payload['qc_notes'] ?? 'Embroidery output failed quality control.', $actor->id);

                $this->trace->notify($order->client_user_id, 'quality_check_ongoing', 'Order under correction', 'Order '.$order->order_number.' is being corrected after QC review.', 'order', $order->id, [
                    'category' => 'production',
                    'priority' => 'medium',
                    'action_label' => 'Track order',
                ]);

                $this->trace->alertOnce($shop->id, $order->id, 'quality_control', 'high', 'QC failed', 'Order '.$order->order_number.' failed structured QC and requires rework.', 'order', $order->id, [
                    'qc_id' => $qualityCheck->id,
                    'rework_id' => $rework->id,
                    'defect_type' => $payload['defect_type'] ?? null,
                ]);
            } else {
                $order->update(['status' => 'in_production', 'current_stage' => 'quality_check']);
                $this->upsertStage($order, 'quality_check', 'active', $actor->id, 'QC in review.');
                $this->progress($order, 'qc_started', 'QC started', $payload['qc_notes'] ?? 'Structured quality check started.', $actor->id);

                $this->trace->notify($order->client_user_id, 'quality_check_ongoing', 'Quality check ongoing', 'Order '.$order->order_number.' is under quality review.', 'order', $order->id, [
                    'category' => 'production',
                    'priority' => 'low',
                    'action_label' => 'Track order',
                ]);
            }

            $this->trace->log($actor->id, $shop->id, 'quality_check', $qualityCheck->id, 'record_quality_check', [
                'qc_status' => $qualityCheck->qc_status,
                'order_id' => $order->id,
                'rework_required' => $qualityCheck->rework_required,
            ]);

            return [
                'message' => 'Quality check saved.',
                'quality_check' => $qualityCheck->fresh()->load(['order:id,order_number,current_stage,status', 'checker:id,name', 'design:id,name,order_id,placement_area,width_mm,height_mm', 'proof:id,proof_no,status', 'productionPackage:id,package_no,status,qc_note']),
                'quality_summary' => $this->buildSummary($order->fresh()),
            ];
        });
    }

    public function assertFulfillmentAllowed(Order $order): void
    {
        $latest = $this->latestForOrder($order);
        if (! $latest || ! $latest->isPassed()) {
            abort(422, 'Fulfillment cannot proceed until the latest structured QC record is marked qc_passed.');
        }
    }

    protected function normalizeStatus(?string $status, ?string $result, bool $reworkRequired): string
    {
        $status = strtolower((string) $status);
        $result = strtolower((string) $result);

        if (in_array($status, [
            QualityCheck::STATUS_PENDING,
            QualityCheck::STATUS_IN_REVIEW,
            QualityCheck::STATUS_PASSED,
            QualityCheck::STATUS_FAILED,
            QualityCheck::STATUS_REWORK_REQUIRED,
        ], true)) {
            return $status;
        }

        return match (true) {
            $reworkRequired, in_array($result, ['fail', 'failed'], true) => QualityCheck::STATUS_REWORK_REQUIRED,
            in_array($result, ['pass', 'passed'], true) => QualityCheck::STATUS_PASSED,
            default => QualityCheck::STATUS_IN_REVIEW,
        };
    }

    protected function normalizeChecks(array $checks): array
    {
        $defaults = [
            'design_matches_approved_proof' => null,
            'placement_matches_approved_placement' => null,
            'thread_colors_correct' => null,
            'size_correct' => null,
            'stitching_quality' => null,
            'loose_threads' => null,
            'missing_sections' => null,
            'finishing_quality' => null,
        ];

        foreach ($checks as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = is_array($value) ? $value : ['status' => $value];
            }
        }

        return $defaults;
    }

    protected function upsertStage(Order $order, string $stageCode, string $status, int $actorUserId, ?string $notes = null): void
    {
        $stage = OrderStageHistory::query()->where('order_id', $order->id)->where('stage_code', $stageCode)->latest('id')->first();
        if (! $stage) {
            OrderStageHistory::create([
                'order_id' => $order->id,
                'stage_code' => $stageCode,
                'stage_status' => $status,
                'started_at' => now(),
                'ended_at' => in_array($status, ['done', 'failed', 'skipped'], true) ? now() : null,
                'actor_user_id' => $actorUserId,
                'notes' => $notes,
            ]);
            return;
        }

        $stage->update([
            'stage_status' => $status,
            'started_at' => $stage->started_at ?? now(),
            'ended_at' => in_array($status, ['done', 'failed', 'skipped'], true) ? now() : null,
            'actor_user_id' => $actorUserId,
            'notes' => $notes ?? $stage->notes,
        ]);
    }

    protected function progress(Order $order, string $status, string $title, ?string $description, int $actorUserId): void
    {
        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'actor_user_id' => $actorUserId,
        ]);
    }
}
