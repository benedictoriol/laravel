<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OperationalAlert;
use App\Models\PlatformNotification;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Collection;

class AutomationTraceService
{
    public function notify(?int $userId, string $type, string $title, string $message, ?string $referenceType = null, ?int $referenceId = null, array $meta = []): ?PlatformNotification
    {
        if (! $userId) {
            return null;
        }

        return PlatformNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'category' => $meta['category'] ?? $this->inferCategory($type, $referenceType),
            'priority' => $meta['priority'] ?? $this->inferPriority($type),
            'title' => $title,
            'message' => $message,
            'action_label' => $meta['action_label'] ?? $this->inferActionLabel($referenceType),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'channel' => $meta['channel'] ?? 'web',
            'is_read' => false,
        ]);
    }

    public function notifyShopLeads(Shop $shop, string $type, string $title, string $message, ?string $referenceType = null, ?int $referenceId = null, array $meta = []): Collection
    {
        $recipients = collect([$shop->owner_user_id])
            ->merge(User::query()->where('shop_id', $shop->id)->whereIn('role', ['hr'])->pluck('id'))
            ->filter()
            ->unique();

        return $recipients->map(fn ($userId) => $this->notify((int) $userId, $type, $title, $message, $referenceType, $referenceId, $meta))->filter();
    }

    public function log(?int $actorUserId, ?int $shopId, string $entityType, ?int $entityId, string $action, array $newValues = [], array $oldValues = [], array $context = []): AuditLog
    {
        return AuditLog::create([
            'actor_user_id' => $actorUserId,
            'shop_id' => $shopId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values_json' => $oldValues ?: null,
            'new_values_json' => array_merge($newValues, ['automation_context' => $context ?: null]),
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
        ]);
    }

    public function alertOnce(?int $shopId, ?int $orderId, string $category, string $severity, string $title, string $message, ?string $referenceType = null, ?int $referenceId = null, array $meta = []): ?OperationalAlert
    {
        $existing = OperationalAlert::query()
            ->where('shop_id', $shopId)
            ->where('category', $category)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $existing;
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
            'meta_json' => $meta ?: null,
        ]);

        if ($shopId) {
            $shop = Shop::find($shopId);
            if ($shop) {
                $this->notifyShopLeads($shop, 'operational_alert', $title, $message, 'operational_alert', $alert->id, [
                    'category' => 'exceptions',
                    'priority' => $severity,
                    'action_label' => 'Open',
                ]);
            }
        }

        return $alert;
    }


    public function normalizeNotifications(array $userIds): int
    {
        $userIds = collect($userIds)->filter()->unique()->values();
        if ($userIds->isEmpty()) {
            return 0;
        }

        $count = 0;
        foreach ($userIds as $userId) {
            $notifications = PlatformNotification::query()
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn (PlatformNotification $notification) => implode('|', [
                    $notification->type,
                    $notification->reference_type,
                    $notification->reference_id,
                    strtolower((string) $notification->title),
                ]));

            foreach ($notifications as $group) {
                $keep = $group->shift();
                foreach ($group as $duplicate) {
                    $duplicate->delete();
                    $count++;
                }
            }

            PlatformNotification::query()
                ->where('user_id', $userId)
                ->where('is_read', true)
                ->where('created_at', '<', now()->subDays(30))
                ->delete();
        }

        return $count;
    }

    protected function inferCategory(string $type, ?string $referenceType): string
    {
        $haystack = strtolower($type.' '.$referenceType);
        return match (true) {
            str_contains($haystack, 'quote') => 'quotes',
            str_contains($haystack, 'payment') => 'payments',
            str_contains($haystack, 'delivery') || str_contains($haystack, 'shipment') || str_contains($haystack, 'fulfillment') => 'delivery',
            str_contains($haystack, 'support') => 'support',
            str_contains($haystack, 'exception') || str_contains($haystack, 'alert') => 'exceptions',
            str_contains($haystack, 'proof') => 'production',
            str_contains($haystack, 'order') => 'orders',
            default => 'support',
        };
    }

    protected function inferPriority(string $type): string
    {
        $haystack = strtolower($type);
        return match (true) {
            str_contains($haystack, 'critical') => 'critical',
            str_contains($haystack, 'rejected'), str_contains($haystack, 'overdue'), str_contains($haystack, 'delay') => 'high',
            str_contains($haystack, 'payment'), str_contains($haystack, 'proof'), str_contains($haystack, 'quote') => 'medium',
            default => 'low',
        };
    }

    protected function inferActionLabel(?string $referenceType): string
    {
        return match ($referenceType) {
            'support_ticket' => 'Open ticket',
            'payment' => 'View payment',
            'order', 'order_quote', 'design_proof', 'operational_alert' => 'View',
            default => 'Open',
        };
    }
}
