<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProgressLog;
use App\Models\OrderRework;
use App\Models\OrderStageHistory;
use App\Models\QualityCheck;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReworkService
{
    public function __construct(protected AutomationTraceService $trace) {}

    public function latestForOrder(Order $order): ?OrderRework
    {
        return OrderRework::query()->where('order_id', $order->id)->latest('id')->first();
    }

    public function activeForOrder(Order $order): ?OrderRework
    {
        return OrderRework::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [
                OrderRework::STATUS_OPEN,
                OrderRework::STATUS_IN_PROGRESS,
                OrderRework::STATUS_DONE,
                OrderRework::STATUS_RECHECK,
            ])
            ->latest('id')
            ->first();
    }

    public function openFromQc(Shop $shop, Order $order, QualityCheck $qualityCheck, User $actor, array $payload = []): OrderRework
    {
        return DB::transaction(function () use ($shop, $order, $qualityCheck, $actor, $payload) {
            $existing = $this->activeForOrder($order);
            $reason = $payload['reason'] ?? $qualityCheck->defect_notes ?? $qualityCheck->qc_notes ?? 'QC failed and correction is required.';
            $severity = $payload['severity'] ?? $this->normalizeSeverity($payload['severity'] ?? $qualityCheck->defect_type ?? 'high');
            $internalNote = $payload['internal_note'] ?? $qualityCheck->remarks ?? $qualityCheck->qc_notes;

            if ($existing) {
                $existing->update([
                    'quality_check_id' => $qualityCheck->id,
                    'reason' => $reason,
                    'severity' => $severity,
                    'status' => OrderRework::STATUS_OPEN,
                    'internal_note' => $internalNote,
                    'progress_notes' => $payload['progress_notes'] ?? $existing->progress_notes,
                    'updated_by' => $actor->id,
                    'completed_at' => null,
                    'returned_to_qc_at' => null,
                    'closed_at' => null,
                ]);
                $rework = $existing->fresh();
            } else {
                $rework = OrderRework::create([
                    'shop_id' => $shop->id,
                    'order_id' => $order->id,
                    'quality_check_id' => $qualityCheck->id,
                    'design_customization_id' => $qualityCheck->design_customization_id,
                    'production_package_id' => $qualityCheck->production_package_id,
                    'reason' => $reason,
                    'severity' => $severity,
                    'status' => OrderRework::STATUS_OPEN,
                    'internal_note' => $internalNote,
                    'progress_notes' => $payload['progress_notes'] ?? null,
                    'opened_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            }

            $order->update(['status' => 'in_production', 'current_stage' => 'rework']);
            $this->upsertStage($order, 'rework', 'active', $actor->id, 'Controlled rework opened.');
            $this->progress($order, 'rework_opened', 'Rework opened', $reason, $actor->id);

            $this->trace->notify($order->client_user_id, 'rework_opened', 'Order under correction', 'Order '.$order->order_number.' is under correction before fulfillment continues.', 'order', $order->id, [
                'category' => 'production',
                'priority' => 'medium',
                'action_label' => 'Track order',
            ]);

            return $rework;
        });
    }

    public function openManual(Shop $shop, Order $order, User $actor, array $payload): OrderRework
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        return DB::transaction(function () use ($shop, $order, $actor, $payload) {
            $latestQc = $order->latestQualityCheck;
            $active = $this->activeForOrder($order);
            $reason = $payload['reason'] ?? 'Owner opened controlled correction.';
            $severity = $this->normalizeSeverity($payload['severity'] ?? 'medium');

            $rework = $active ?: OrderRework::create([
                'shop_id' => $shop->id,
                'order_id' => $order->id,
                'quality_check_id' => $latestQc?->id,
                'design_customization_id' => $latestQc?->design_customization_id,
                'production_package_id' => $latestQc?->production_package_id,
                'reason' => $reason,
                'severity' => $severity,
                'status' => OrderRework::STATUS_OPEN,
                'opened_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $rework->update([
                'quality_check_id' => $payload['quality_check_id'] ?? $rework->quality_check_id,
                'reason' => $reason,
                'severity' => $severity,
                'status' => $payload['status'] ?? OrderRework::STATUS_OPEN,
                'internal_note' => $payload['internal_note'] ?? $rework->internal_note,
                'progress_notes' => $payload['progress_notes'] ?? $rework->progress_notes,
                'updated_by' => $actor->id,
                'completed_at' => null,
                'returned_to_qc_at' => null,
                'closed_at' => null,
            ]);

            $order->update(['status' => 'in_production', 'current_stage' => 'rework']);
            $this->upsertStage($order, 'rework', 'active', $actor->id, 'Controlled rework opened manually.');
            $this->progress($order, 'rework_opened', 'Rework opened', $reason, $actor->id);

            return $rework->fresh();
        });
    }

    public function update(Shop $shop, OrderRework $rework, User $actor, array $payload): OrderRework
    {
        abort_unless((int) $rework->shop_id === (int) $shop->id, 403, 'Rework does not belong to this shop.');

        return DB::transaction(function () use ($rework, $actor, $payload) {
            $status = $payload['status'] ?? $rework->status;
            $updates = [
                'reason' => $payload['reason'] ?? $rework->reason,
                'severity' => isset($payload['severity']) ? $this->normalizeSeverity($payload['severity']) : $rework->severity,
                'status' => $status,
                'internal_note' => $payload['internal_note'] ?? $rework->internal_note,
                'progress_notes' => $payload['progress_notes'] ?? $rework->progress_notes,
                'updated_by' => $actor->id,
            ];

            if ($status === OrderRework::STATUS_DONE) {
                $updates['completed_at'] = now();
            }
            if ($status === OrderRework::STATUS_RECHECK) {
                $updates['returned_to_qc_at'] = now();
            }
            if ($status === OrderRework::STATUS_CLOSED) {
                $updates['closed_at'] = now();
            }

            $rework->update($updates);
            $order = $rework->order()->first();

            if ($status === OrderRework::STATUS_IN_PROGRESS) {
                $order?->update(['status' => 'in_production', 'current_stage' => 'rework']);
                $this->upsertStage($order, 'rework', 'active', $actor->id, 'Rework in progress.');
            }
            if ($status === OrderRework::STATUS_DONE || $status === OrderRework::STATUS_RECHECK) {
                $order?->update(['status' => 'in_production', 'current_stage' => 'quality_check']);
                $this->upsertStage($order, 'rework', 'done', $actor->id, 'Rework done and awaiting QC recheck.');
                $this->upsertStage($order, 'quality_check', 'active', $actor->id, 'Returned to QC after rework.');
            }
            if ($status === OrderRework::STATUS_CLOSED) {
                $this->upsertStage($order, 'rework', 'done', $actor->id, 'Rework closed.');
            }

            $title = match ($status) {
                OrderRework::STATUS_RECHECK => 'Rework returned to QC',
                OrderRework::STATUS_CLOSED => 'Rework closed',
                default => 'Rework updated',
            };
            $logStatus = match ($status) {
                OrderRework::STATUS_RECHECK => 'rework_returned_to_qc',
                OrderRework::STATUS_CLOSED => 'rework_closed',
                default => 'rework_updated',
            };
            $this->progress($order, $logStatus, $title, $payload['progress_notes'] ?? $payload['internal_note'] ?? $rework->reason, $actor->id);

            return $rework->fresh();
        });
    }

    public function closeFromQcPass(Order $order, User $actor, ?string $note = null): ?OrderRework
    {
        $rework = $this->activeForOrder($order);
        if (! $rework) {
            return null;
        }

        $rework->update([
            'status' => OrderRework::STATUS_CLOSED,
            'updated_by' => $actor->id,
            'closed_at' => now(),
            'progress_notes' => trim(($rework->progress_notes ? $rework->progress_notes."\n" : '').($note ?: 'Closed after QC pass.')),
        ]);

        $this->progress($order, 'rework_closed', 'Rework closed', $note ?: 'Rework closed after QC pass.', $actor->id);

        $this->trace->notify($order->client_user_id, 'rework_closed', 'Rework completed', 'Order '.$order->order_number.' completed correction and moved back into approved flow.', 'order', $order->id, [
            'category' => 'production',
            'priority' => 'medium',
            'action_label' => 'Track order',
        ]);

        return $rework->fresh();
    }

    protected function normalizeSeverity(string $severity): string
    {
        $value = strtolower(trim($severity));
        return in_array($value, ['low', 'medium', 'high', 'critical'], true) ? $value : 'medium';
    }

    protected function progress(?Order $order, string $status, string $title, ?string $description, ?int $actorUserId = null): void
    {
        if (! $order) {
            return;
        }

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'actor_user_id' => $actorUserId,
        ]);
    }

    protected function upsertStage(?Order $order, string $stageCode, string $stageStatus, ?int $actorUserId, ?string $notes = null): void
    {
        if (! $order) {
            return;
        }

        $existing = OrderStageHistory::query()
            ->where('order_id', $order->id)
            ->where('stage_code', $stageCode)
            ->latest('id')
            ->first();

        if ($existing) {
            $existing->update([
                'stage_status' => $stageStatus,
                'actor_user_id' => $actorUserId,
                'notes' => $notes ?: $existing->notes,
                'ended_at' => $stageStatus === 'done' ? now() : null,
            ]);
            return;
        }

        OrderStageHistory::create([
            'order_id' => $order->id,
            'stage_code' => $stageCode,
            'stage_status' => $stageStatus,
            'started_at' => now(),
            'ended_at' => $stageStatus === 'done' ? now() : null,
            'actor_user_id' => $actorUserId,
            'notes' => $notes,
        ]);
    }
}
