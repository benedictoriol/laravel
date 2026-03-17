<?php

namespace App\Services;

use App\Models\Fulfillment;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderProgressLog;
use App\Models\OrderQuote;
use App\Models\OrderStageHistory;
use App\Models\OwnerSetting;
use App\Models\Payment;
use App\Models\PlatformNotification;
use App\Models\QualityCheck;
use App\Models\RawMaterial;
use App\Models\Shop;
use App\Models\SupplyOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OwnerExecutionService
{
    public function __construct(
        protected AutomationTraceService $trace,
        protected ProductionOrchestrationService $production,
    ) {}

    public function reassignStaff(Shop $shop, Order $order, User $actor, int $staffId, string $assignmentType, ?string $notes = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        $staff = User::query()
            ->where('shop_id', $shop->id)
            ->whereIn('role', ['hr', 'staff'])
            ->findOrFail($staffId);

        return DB::transaction(function () use ($shop, $order, $actor, $staff, $assignmentType, $notes) {
            OrderAssignment::query()
                ->where('order_id', $order->id)
                ->where('assignment_type', $assignmentType)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->update([
                    'status' => 'reassigned',
                    'completed_at' => now(),
                    'notes' => trim(($notes ? $notes.' ' : '').'Reassigned by owner automation panel.'),
                ]);

            $assignment = OrderAssignment::create([
                'order_id' => $order->id,
                'assigned_to' => $staff->id,
                'assigned_by' => $actor->id,
                'assignment_role' => $staff->role,
                'assignment_type' => $assignmentType,
                'status' => 'assigned',
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'staff_reassigned',
                'title' => 'Staff reassigned',
                'description' => sprintf('%s was assigned to %s.', $staff->name, str_replace('_', ' ', $assignmentType)),
                'actor_user_id' => $actor->id,
            ]);

            $this->trace->notify($staff->id, 'assignment_reassigned', 'New assignment received', 'You were assigned to order '.$order->order_number.' for '.str_replace('_', ' ', $assignmentType).'.', 'order', $order->id, [
                'category' => 'production',
                'priority' => 'medium',
                'action_label' => 'Open',
            ]);

            $this->trace->log($actor->id, $shop->id, 'order_assignment', $assignment->id, 'reassign_staff', [
                'order_id' => $order->id,
                'assignment_type' => $assignmentType,
                'assigned_to' => $staff->id,
                'notes' => $notes,
            ], [], ['automation' => 'owner_action_execution']);

            return [
                'message' => 'Staff reassigned successfully.',
                'assignment' => $assignment->load('assignee:id,name,role'),
            ];
        });
    }

    public function approveProductionPlan(Shop $shop, Order $order, User $actor): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');
        $plan = $this->production->orchestrateAfterPayment($order->fresh(), $actor);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'production_plan_approved',
            'title' => 'Production plan approved',
            'description' => 'Owner approved the current production orchestration plan.',
            'actor_user_id' => $actor->id,
        ]);

        return [
            'message' => 'Production plan approved.',
            'plan' => $plan,
        ];
    }

    public function createRestockRequest(Shop $shop, User $actor, int $materialId, ?int $supplierId = null, ?float $quantity = null, ?string $notes = null): array
    {
        $material = RawMaterial::query()->where('shop_id', $shop->id)->findOrFail($materialId);
        $supplier = $supplierId
            ? Supplier::query()->where('shop_id', $shop->id)->findOrFail($supplierId)
            : ($material->supplier_id ? Supplier::query()->where('shop_id', $shop->id)->find($material->supplier_id) : Supplier::query()->where('shop_id', $shop->id)->orderBy('id')->first());

        $needed = $quantity ?? max(1, ((float) ($material->reorder_level ?? 0) * 2) - (float) $material->stock_quantity);

        $supplyOrder = SupplyOrder::create([
            'shop_id' => $shop->id,
            'supplier_id' => $supplier?->id,
            'po_number' => 'PO-'.now()->format('Ymd-His'),
            'materials_json' => [[
                'material_id' => $material->id,
                'material_name' => $material->material_name,
                'category' => $material->category,
                'quantity' => $needed,
                'unit' => $material->unit,
                'estimated_unit_cost' => (float) ($material->cost_per_unit ?? 0),
            ]],
            'quantity_total' => $needed,
            'total_cost' => round($needed * (float) ($material->cost_per_unit ?? 0), 2),
            'ordered_at' => now()->toDateString(),
            'expected_arrival_at' => now()->addDays((int) ($supplier?->lead_time_days ?? 7))->toDateString(),
            'status' => 'requested',
            'notes' => $notes,
            'approved_by' => $actor->id,
        ]);

        $this->trace->alertOnce($shop->id, null, 'restock_request', 'medium', 'Restock request created', 'Restock request '.$supplyOrder->po_number.' was created for '.$material->material_name.'.', 'supply_order', $supplyOrder->id);
        $this->trace->log($actor->id, $shop->id, 'supply_order', $supplyOrder->id, 'create_restock_request', [
            'material_id' => $material->id,
            'supplier_id' => $supplier?->id,
            'quantity_total' => $needed,
        ], [], ['automation' => 'owner_action_execution']);

        return [
            'message' => 'Restock request created.',
            'supply_order' => $supplyOrder->load('supplier:id,name', 'approver:id,name'),
        ];
    }

    public function followUpPayment(Shop $shop, Order $order, User $actor, ?string $notes = null, bool $extendDueDate = false): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        $latestPayment = $order->payments()->latest('id')->first();
        if ($extendDueDate && $order->payment_due_date) {
            $order->update(['payment_due_date' => Carbon::parse($order->payment_due_date)->addDays(2)]);
        }

        $message = 'Payment follow-up sent for order '.$order->order_number.'.'.($notes ? ' '.$notes : '');
        $this->trace->notify($order->client_user_id, 'payment_follow_up', 'Payment reminder', $message, 'order', $order->id, [
            'category' => 'payments',
            'priority' => 'high',
            'action_label' => 'Pay now',
        ]);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'payment_follow_up',
            'title' => 'Payment follow-up sent',
            'description' => $message,
            'actor_user_id' => $actor->id,
        ]);

        $this->trace->log($actor->id, $shop->id, 'payment', $latestPayment?->id, 'follow_up_payment', [
            'order_id' => $order->id,
            'payment_due_date' => optional($order->fresh()->payment_due_date)?->toDateString(),
            'notes' => $notes,
        ], [], ['automation' => 'payment_followup']);

        return ['message' => 'Payment reminder sent.'];
    }

    public function escalateOrder(Shop $shop, Order $order, User $actor, ?string $notes = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        $alert = $this->trace->alertOnce($shop->id, $order->id, 'owner_escalation', 'high', 'Order escalated', 'Order '.$order->order_number.' was escalated for immediate review.'.($notes ? ' '.$notes : ''), 'order', $order->id, [
            'current_stage' => $order->current_stage,
        ]);

        $order->update(['internal_notes' => trim(($order->internal_notes ? $order->internal_notes."\n" : '').'[Escalated '.now()->toDateTimeString().'] '.($notes ?: 'Operational escalation applied.'))]);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'order_escalated',
            'title' => 'Order escalated',
            'description' => $notes ?: 'Order was escalated from the owner action center.',
            'actor_user_id' => $actor->id,
        ]);

        return ['message' => 'Order escalated.', 'alert' => $alert];
    }

    public function pauseProduction(Shop $shop, Order $order, User $actor, ?string $notes = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        $order->update([
            'status' => 'on_hold',
            'current_stage' => 'paused',
        ]);

        OrderStageHistory::create([
            'order_id' => $order->id,
            'stage_code' => 'paused',
            'stage_status' => 'active',
            'started_at' => now(),
            'actor_user_id' => $actor->id,
            'notes' => $notes ?: 'Paused from owner action center.',
        ]);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'production_paused',
            'title' => 'Production paused',
            'description' => $notes ?: 'Production was paused.',
            'actor_user_id' => $actor->id,
        ]);

        $this->trace->notify($order->client_user_id, 'production_paused', 'Order update', 'Production for order '.$order->order_number.' was temporarily paused while the shop resolves a blocker.', 'order', $order->id, [
            'category' => 'production',
            'priority' => 'medium',
            'action_label' => 'Track order',
        ]);

        return ['message' => 'Production paused.'];
    }

    public function resumeProduction(Shop $shop, Order $order, User $actor, ?string $notes = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        $resumeStage = $order->stageHistory()->where('stage_code', '!=', 'paused')->latest('id')->value('stage_code') ?: 'digitizing';
        $order->update([
            'status' => 'in_production',
            'current_stage' => $resumeStage,
        ]);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'production_resumed',
            'title' => 'Production resumed',
            'description' => $notes ?: 'Production resumed.',
            'actor_user_id' => $actor->id,
        ]);

        $this->trace->notify($order->client_user_id, 'production_resumed', 'Order back in production', 'Production resumed for order '.$order->order_number.'.', 'order', $order->id, [
            'category' => 'production',
            'priority' => 'medium',
            'action_label' => 'Track order',
        ]);

        return ['message' => 'Production resumed.'];
    }

    public function resolveAlert(Shop $shop, OperationalAlert $alert, User $actor): array
    {
        abort_unless((int) $alert->shop_id === (int) $shop->id, 403, 'Alert does not belong to this shop.');
        $alert->update(['status' => 'resolved', 'resolved_at' => now(), 'user_id' => $actor->id]);
        $this->trace->log($actor->id, $shop->id, 'operational_alert', $alert->id, 'resolve_alert', ['status' => 'resolved'], ['status' => $alert->getOriginal('status')], ['automation' => 'owner_action_execution']);
        return ['message' => 'Alert resolved.', 'alert' => $alert->fresh()];
    }

    public function snoozeAlert(Shop $shop, OperationalAlert $alert, User $actor, int $hours = 6): array
    {
        abort_unless((int) $alert->shop_id === (int) $shop->id, 403, 'Alert does not belong to this shop.');
        $meta = $alert->meta_json ?: [];
        $meta['snoozed_until'] = now()->addHours($hours)->toDateTimeString();
        $alert->update(['status' => 'snoozed', 'user_id' => $actor->id, 'meta_json' => $meta]);
        $this->trace->log($actor->id, $shop->id, 'operational_alert', $alert->id, 'snooze_alert', ['status' => 'snoozed', 'until' => $meta['snoozed_until']], ['status' => $alert->getOriginal('status')], ['automation' => 'owner_action_execution']);
        return ['message' => 'Alert snoozed.', 'alert' => $alert->fresh()];
    }

    public function createQualityCheck(Shop $shop, Order $order, User $actor, string $result, ?string $issueNotes = null, bool $reworkRequired = false, ?string $actionTaken = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');

        $qualityCheck = QualityCheck::create([
            'shop_id' => $shop->id,
            'order_id' => $order->id,
            'checked_by' => $actor->id,
            'result' => $result,
            'issue_notes' => $issueNotes,
            'rework_required' => $reworkRequired,
            'action_taken' => $actionTaken,
            'checked_at' => now(),
        ]);

        if ($reworkRequired || in_array(strtolower($result), ['fail', 'failed', 'rework'], true)) {
            $order->update(['current_stage' => 'rework', 'status' => 'in_production']);
            $this->production->routeException($order, 'quality_rework', $issueNotes ?: 'QC rework required.', 'high', $actor->id);
        } else {
            $order->update(['current_stage' => 'ready_for_fulfillment']);
        }

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'quality_checked',
            'title' => 'Quality control completed',
            'description' => 'QC result: '.$result.'.'.($issueNotes ? ' '.$issueNotes : ''),
            'actor_user_id' => $actor->id,
        ]);

        return [
            'message' => 'Quality check saved.',
            'quality_check' => $qualityCheck->load('order:id,order_number', 'checker:id,name'),
        ];
    }

    public function markPackageReady(Shop $shop, Order $order, User $actor, ?string $notes = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');
        $fulfillment = Fulfillment::firstOrCreate(['order_id' => $order->id], [
            'fulfillment_type' => $order->fulfillment_type ?: 'delivery',
            'status' => 'pending',
        ]);
        $fulfillment->update(['status' => 'ready', 'notes' => $notes ?: $fulfillment->notes]);
        $order->update(['status' => $order->fulfillment_type === 'pickup' ? 'ready_for_pickup' : 'shipped', 'current_stage' => 'packaging']);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'package_ready',
            'title' => 'Package ready',
            'description' => $notes ?: 'Order packed and ready for fulfillment dispatch.',
            'actor_user_id' => $actor->id,
        ]);

        $this->trace->notify($order->client_user_id, 'package_ready', 'Order ready for fulfillment', 'Order '.$order->order_number.' is packed and ready for '.($order->fulfillment_type === 'pickup' ? 'pickup' : 'shipment').'.', 'order', $order->id, [
            'category' => 'delivery',
            'priority' => 'medium',
            'action_label' => 'Track order',
        ]);

        return ['message' => 'Order marked package-ready.', 'fulfillment' => $fulfillment->fresh()];
    }

    public function assignCourier(Shop $shop, Order $order, User $actor, string $courierName, ?string $trackingNumber = null, ?string $notes = null): array
    {
        abort_unless((int) $order->shop_id === (int) $shop->id, 403, 'Order does not belong to this shop.');
        $fulfillment = Fulfillment::firstOrCreate(['order_id' => $order->id], [
            'fulfillment_type' => $order->fulfillment_type ?: 'delivery',
            'status' => 'pending',
        ]);
        $fulfillment->update([
            'courier_name' => $courierName,
            'tracking_number' => $trackingNumber,
            'status' => 'scheduled',
            'notes' => $notes ?: $fulfillment->notes,
        ]);

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => 'courier_assigned',
            'title' => 'Courier assigned',
            'description' => trim($courierName.' assigned.'.($trackingNumber ? ' Tracking: '.$trackingNumber.'.' : '').' '.($notes ?: '')),
            'actor_user_id' => $actor->id,
        ]);

        return ['message' => 'Courier assigned.', 'fulfillment' => $fulfillment->fresh()];
    }

    public function maintainNotificationLifecycle(Shop $shop, User $actor): array
    {
        $shopUserIds = User::query()->where('shop_id', $shop->id)->pluck('id');
        $deduped = $this->trace->normalizeNotifications($shopUserIds->all());
        $archivedAlerts = OperationalAlert::query()
            ->where('shop_id', $shop->id)
            ->where('status', 'snoozed')
            ->whereRaw("JSON_EXTRACT(COALESCE(meta_json, '{}'), '$.snoozed_until') IS NOT NULL")
            ->get()
            ->filter(function (OperationalAlert $alert) {
                $until = data_get($alert->meta_json, 'snoozed_until');
                return $until && Carbon::parse($until)->isPast();
            });

        foreach ($archivedAlerts as $alert) {
            $alert->update(['status' => 'open']);
        }

        $this->trace->log($actor->id, $shop->id, 'notification', null, 'maintain_lifecycle', [
            'deduplicated' => $deduped,
            'reopened_alerts' => $archivedAlerts->count(),
        ], [], ['automation' => 'notification_lifecycle']);

        return [
            'message' => 'Notification lifecycle maintenance completed.',
            'deduplicated' => $deduped,
            'reopened_alerts' => $archivedAlerts->count(),
        ];
    }
}
