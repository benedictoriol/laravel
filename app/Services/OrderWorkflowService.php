<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use Illuminate\Support\Facades\DB;

class OrderWorkflowService
{
    public function completeStage(Order $order, OrderStageHistory $stage, int $actorUserId, ?string $notes = null): array
    {
        $order->loadMissing(['shop', 'fulfillment']);

        return DB::transaction(function () use ($order, $stage, $actorUserId, $notes) {
            $stage->update([
                'stage_status' => 'done',
                'ended_at' => now(),
                'actor_user_id' => $actorUserId,
                'notes' => $notes ?? $stage->notes,
            ]);

            $this->writeProgressLog(
                $order,
                $stage->stage_code,
                'Stage completed',
                $notes ?? $stage->notes,
                $actorUserId
            );

            $this->notifyStageCompleted($order, $stage->stage_code);

            $nextStageCode = $this->getNextStageCode($stage->stage_code, $order);

            if ($nextStageCode) {
                $nextStage = OrderStageHistory::create([
                    'order_id' => $order->id,
                    'stage_code' => $nextStageCode,
                    'stage_status' => 'active',
                    'started_at' => now(),
                    'actor_user_id' => $actorUserId,
                    'notes' => null,
                ]);

                $this->applyOrderStatusFromStage($order, $nextStageCode);
                $this->syncFulfillmentFromStage($order, $nextStageCode);

                $this->writeProgressLog(
                    $order,
                    $nextStageCode,
                    'Stage auto-started',
                    null,
                    $actorUserId
                );

                $this->notifyStageStarted($order, $nextStageCode);
                app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'stage_auto_started');

                return [
                    'completed_stage' => $stage->fresh(),
                    'next_stage' => $nextStage,
                    'order' => $order->fresh(),
                ];
            }

            $order->update([
                'status' => 'completed',
                'current_stage' => 'completed',
                'completed_at' => now(),
            ]);
            $this->syncFulfillmentFromStage($order, 'completed');

            $this->writeProgressLog(
                $order,
                'completed',
                'Order completed',
                null,
                $actorUserId
            );

            $this->notifyOrderCompleted($order);
            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'order_completed');

            return [
                'completed_stage' => $stage->fresh(),
                'next_stage' => null,
                'order' => $order->fresh(),
            ];
        });
    }

    public function getNextStageCode(string $current, Order $order): ?string
    {
        if ($current === 'packing') {
            return $order->fulfillment_type === 'delivery' ? 'shipping' : 'pickup_ready';
        }

        $flow = [
            'digitizing' => 'mockup',
            'mockup' => 'client_approval',
            'client_approval' => 'production',
            'production' => 'quality_check',
            'quality_check' => 'packing',
            'pickup_ready' => 'completed',
            'shipping' => 'delivered',
            'delivered' => 'completed',
        ];

        return $flow[$current] ?? null;
    }

    public function applyOrderStatusFromStage(Order $order, string $stageCode): void
    {
        $map = [
            'digitizing' => ['status' => 'in_production', 'current_stage' => 'digitizing'],
            'mockup' => ['status' => 'in_production', 'current_stage' => 'mockup'],
            'client_approval' => ['status' => 'approved', 'current_stage' => 'client_approval'],
            'production' => ['status' => 'in_production', 'current_stage' => 'production'],
            'quality_check' => ['status' => 'in_production', 'current_stage' => 'quality_check'],
            'packing' => ['status' => 'in_production', 'current_stage' => 'packing'],
            'pickup_ready' => ['status' => 'ready_for_pickup', 'current_stage' => 'pickup_ready'],
            'shipping' => ['status' => 'shipped', 'current_stage' => 'shipping'],
            'delivered' => ['status' => 'shipped', 'current_stage' => 'delivered'],
            'completed' => ['status' => 'completed', 'current_stage' => 'completed', 'completed_at' => now()],
        ];

        if (isset($map[$stageCode])) {
            $order->update($map[$stageCode]);
        }
    }

    public function syncFulfillmentFromStage(Order $order, string $stageCode): void
    {
        $fulfillment = $order->fulfillment;

        if (! $fulfillment) {
            return;
        }

        $updates = match ($stageCode) {
            'packing' => ['status' => 'scheduled'],
            'pickup_ready' => ['status' => 'ready'],
            'shipping' => ['status' => 'shipped', 'shipped_at' => $fulfillment->shipped_at ?? now()],
            'delivered' => ['status' => 'delivered', 'delivered_at' => $fulfillment->delivered_at ?? now()],
            'completed' => $order->fulfillment_type === 'pickup'
                ? ['status' => 'picked_up', 'received_at' => $fulfillment->received_at ?? now()]
                : ['status' => 'delivered', 'delivered_at' => $fulfillment->delivered_at ?? now()],
            'cancelled' => ['status' => 'cancelled'],
            default => null,
        };

        if ($updates) {
            $fulfillment->update($updates);
        }
    }

    public function writeProgressLog(Order $order, string $status, string $title, ?string $description, ?int $actorUserId): void
    {
        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'actor_user_id' => $actorUserId,
        ]);
    }

    public function notifyStageCompleted(Order $order, string $stageCode): void
    {
        $stageLabel = $this->humanizeStage($stageCode);

        PlatformNotification::create([
            'user_id' => $order->client_user_id,
            'type' => 'order_stage_completed',
            'title' => 'Order stage completed',
            'message' => 'The "'.$stageLabel.'" stage for order '.$order->order_number.' has been completed.',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);

        $shopOwnerId = optional($order->shop)->owner_user_id;
        if ($shopOwnerId) {
            PlatformNotification::create([
                'user_id' => $shopOwnerId,
                'type' => 'order_stage_completed',
                'title' => 'Order stage completed',
                'message' => 'The "'.$stageLabel.'" stage for order '.$order->order_number.' has been completed.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        }
    }

    public function notifyStageStarted(Order $order, string $stageCode): void
    {
        $stageLabel = $this->humanizeStage($stageCode);

        PlatformNotification::create([
            'user_id' => $order->client_user_id,
            'type' => 'order_stage_started',
            'title' => 'Order moved to next stage',
            'message' => 'Order '.$order->order_number.' is now in "'.$stageLabel.'".',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);

        $shopOwnerId = optional($order->shop)->owner_user_id;
        if ($shopOwnerId) {
            PlatformNotification::create([
                'user_id' => $shopOwnerId,
                'type' => 'order_stage_started',
                'title' => 'Order moved to next stage',
                'message' => 'Order '.$order->order_number.' is now in "'.$stageLabel.'".',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        }

        if ($stageCode === 'pickup_ready') {
            PlatformNotification::create([
                'user_id' => $order->client_user_id,
                'type' => 'order_ready_for_pickup',
                'title' => 'Order ready for pickup',
                'message' => 'Your order '.$order->order_number.' is now ready for pickup.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        }

        if ($stageCode === 'shipping') {
            PlatformNotification::create([
                'user_id' => $order->client_user_id,
                'type' => 'order_shipping',
                'title' => 'Order shipping started',
                'message' => 'Your order '.$order->order_number.' is now being prepared for delivery.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        }
    }

    public function notifyOrderCompleted(Order $order): void
    {
        PlatformNotification::create([
            'user_id' => $order->client_user_id,
            'type' => 'order_completed',
            'title' => 'Order completed',
            'message' => 'Your order '.$order->order_number.' has been completed.',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);

        $shopOwnerId = optional($order->shop)->owner_user_id;
        if ($shopOwnerId) {
            PlatformNotification::create([
                'user_id' => $shopOwnerId,
                'type' => 'order_completed',
                'title' => 'Order completed',
                'message' => 'Order '.$order->order_number.' has been completed.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        }
    }

    public function humanizeStage(string $stageCode): string
    {
        return match ($stageCode) {
            'digitizing' => 'Digitizing',
            'mockup' => 'Mockup Preparation',
            'client_approval' => 'Client Approval',
            'production' => 'Production',
            'quality_check' => 'Quality Check',
            'packing' => 'Packing',
            'pickup_ready' => 'Ready for Pickup',
            'shipping' => 'Shipping',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            default => ucwords(str_replace('_', ' ', $stageCode)),
        };
    }
}
