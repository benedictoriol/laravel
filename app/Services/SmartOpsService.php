<?php

namespace App\Services;

use App\Models\BargainingOffer;
use App\Models\OperationalAlert;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\PlatformNotification;
use App\Models\Shop;
use Illuminate\Support\Collection;

class SmartOpsService
{
    public function shopSummary(int $shopId): array
    {
        $overdueAssignments = OrderAssignment::query()
            ->whereHas('order', fn ($q) => $q->where('shop_id', $shopId))
            ->whereIn('status', ['assigned', 'in_progress'])
            ->where(function ($q) {
                $q->whereDate('assigned_at', '<=', now()->subDays(2))
                  ->orWhereDate('updated_at', '<=', now()->subDays(2));
            })
            ->count();

        $stalledOrders = Order::query()
            ->where('shop_id', $shopId)
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->whereDate('updated_at', '<=', now()->subDays(3))
            ->count();

        $pendingProofs = \App\Models\DesignProof::query()
            ->where('status', 'pending_client')
            ->whereHas('customization.order', fn ($q) => $q->where('shop_id', $shopId))
            ->count();

        $expiringOffers = BargainingOffer::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(2)])
            ->whereHas('application', fn ($q) => $q->where('shop_id', $shopId))
            ->count();

        $alerts = OperationalAlert::query()
            ->where('shop_id', $shopId)
            ->where('status', 'open')
            ->latest('id')
            ->limit(10)
            ->get();

        return [
            'overdue_assignments' => $overdueAssignments,
            'stalled_orders' => $stalledOrders,
            'pending_proofs' => $pendingProofs,
            'expiring_bargains' => $expiringOffers,
            'alerts' => $alerts,
        ];
    }

    public function scanShop(int $shopId): Collection
    {
        $shop = Shop::findOrFail($shopId);
        $created = collect();

        $assignments = OrderAssignment::query()
            ->whereHas('order', fn ($q) => $q->where('shop_id', $shopId))
            ->whereIn('status', ['assigned', 'in_progress'])
            ->whereDate('assigned_at', '<=', now()->subDays(2))
            ->get();

        foreach ($assignments as $assignment) {
            $created->push($this->createAlertOnce(
                $shopId,
                $assignment->order_id,
                'assignment_overdue',
                'high',
                'Assignment overdue',
                'Assignment #'.$assignment->id.' is still '.$assignment->status.' beyond the expected window.',
                'assignment',
                $assignment->id,
                ['assigned_to' => $assignment->assigned_to, 'status' => $assignment->status]
            ));
        }

        $orders = Order::query()
            ->where('shop_id', $shopId)
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->whereDate('updated_at', '<=', now()->subDays(3))
            ->get();

        foreach ($orders as $order) {
            $created->push($this->createAlertOnce(
                $shopId,
                $order->id,
                'order_stalled',
                'medium',
                'Order stalled',
                'Order '.$order->order_number.' has not changed for at least 3 days.',
                'order',
                $order->id,
                ['current_stage' => $order->current_stage, 'status' => $order->status]
            ));
        }

        $proofs = \App\Models\DesignProof::query()
            ->where('status', 'pending_client')
            ->whereDate('created_at', '<=', now()->subDays(2))
            ->whereHas('customization.order', fn ($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($proofs as $proof) {
            $created->push($this->createAlertOnce(
                $shopId,
                $proof->customization?->order_id,
                'proof_waiting',
                'medium',
                'Client proof response pending',
                'Proof #'.$proof->proof_no.' is still waiting for client feedback.',
                'design_proof',
                $proof->id,
                ['customization_id' => $proof->design_customization_id]
            ));
        }

        $offers = BargainingOffer::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(2)])
            ->whereHas('application', fn ($q) => $q->where('shop_id', $shopId))
            ->get();

        foreach ($offers as $offer) {
            $created->push($this->createAlertOnce(
                $shopId,
                null,
                'bargain_expiring',
                'low',
                'Bargaining offer expiring soon',
                'Offer #'.$offer->id.' will expire within 48 hours.',
                'bargaining_offer',
                $offer->id,
                ['expires_at' => $offer->expires_at]
            ));
        }

        return $created->filter();
    }

    protected function createAlertOnce(?int $shopId, ?int $orderId, string $category, string $severity, string $title, string $message, ?string $referenceType, ?int $referenceId, array $meta = []): ?OperationalAlert
    {
        $existing = OperationalAlert::query()
            ->where('category', $category)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return null;
        }

        $alert = OperationalAlert::create([
            'shop_id' => $shopId,
            'order_id' => $orderId,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'status' => 'open',
            'meta_json' => $meta,
        ]);

        if ($shopId) {
            $shop = Shop::find($shopId);
            $recipientIds = collect([$shop?->owner_user_id])
                ->merge($shop ? $shop->members()->whereIn('member_role', ['hr'])->pluck('user_id') : [])
                ->filter()
                ->unique();

            foreach ($recipientIds as $userId) {
                PlatformNotification::create([
                    'user_id' => $userId,
                    'type' => 'operational_alert',
                    'title' => $title,
                    'message' => $message,
                    'reference_type' => 'operational_alert',
                    'reference_id' => $alert->id,
                    'channel' => 'web',
                ]);
            }
        }

        return $alert;
    }

    public function resolveAlert(OperationalAlert $alert): OperationalAlert
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return $alert;
    }
}
