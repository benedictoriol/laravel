<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use App\Services\AnalyticsAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FulfillmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Fulfillment::query()->with(['order.client', 'order.shop']);

        if ($user->isClient()) {
            $query->whereHas('order', fn ($q) => $q->where('client_user_id', $user->id));
        } elseif (! $user->isAdmin()) {
            $query->whereHas('order', fn ($q) => $q->where('shop_id', $user->shop_id ?? 0));
        }

        return response()->json($query->latest('id')->get());
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeAccess($request->user(), $order);

        return response()->json($this->getOrCreateFulfillment($order)->load('order'));
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        return $this->save($request, $order);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        return $this->save($request, $order);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user, $order);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,scheduled,ready,shipped,delivered,picked_up,failed,cancelled'],
            'notes' => ['nullable', 'string'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'courier_name' => ['nullable', 'string', 'max:120'],
        ]);

        $fulfillment = $this->getOrCreateFulfillment($order);

        return DB::transaction(function () use ($validated, $fulfillment, $order, $user) {
            $updates = [
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? $fulfillment->notes,
            ];

            if (array_key_exists('tracking_number', $validated)) {
                $updates['tracking_number'] = $validated['tracking_number'];
            }

            if (array_key_exists('courier_name', $validated)) {
                $updates['courier_name'] = $validated['courier_name'];
            }

            $this->applyTimestampFromStatus($updates, $validated['status']);
            $fulfillment->update($updates);
            $this->syncOrderFromFulfillmentStatus($order, $validated['status']);
            $this->writeFulfillmentProgress($order, $validated['status'], $validated['notes'] ?? null, $user->id);
            $this->notifyFulfillmentStatus($order, $validated['status']);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'fulfillment_status_updated');

            return response()->json([
                'message' => 'Fulfillment status updated successfully.',
                'fulfillment' => $fulfillment->fresh(),
                'order' => $order->fresh(),
            ]);
        });
    }

    public function markReady(Request $request, Order $order): JsonResponse
    {
        return $this->applyActionStatus($request, $order, 'ready', 'Fulfillment marked ready successfully.');
    }

    public function markShipped(Request $request, Order $order): JsonResponse
    {
        return $this->applyActionStatus($request, $order, 'shipped', 'Fulfillment marked shipped successfully.');
    }

    public function markDelivered(Request $request, Order $order): JsonResponse
    {
        return $this->applyActionStatus($request, $order, 'delivered', 'Fulfillment marked delivered successfully.');
    }

    public function markPickedUp(Request $request, Order $order): JsonResponse
    {
        return $this->applyActionStatus($request, $order, 'picked_up', 'Fulfillment marked picked up successfully.');
    }

    protected function save(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user, $order);

        $validated = $request->validate([
            'receiver_name' => ['nullable', 'string', 'max:150'],
            'receiver_contact' => ['nullable', 'string', 'max:50'],
            'cavite_location_id' => ['nullable', 'integer', 'exists:cavite_locations,id'],
            'delivery_address' => ['nullable', 'string'],
            'courier_name' => ['nullable', 'string', 'max:120'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'pickup_schedule_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $fulfillment = $this->getOrCreateFulfillment($order);
        $fulfillment->update($validated);

        if (! empty($validated['delivery_address']) && $order->delivery_address !== $validated['delivery_address']) {
            $order->update(['delivery_address' => $validated['delivery_address']]);
        }

        return response()->json([
            'message' => 'Fulfillment saved successfully.',
            'fulfillment' => $fulfillment->fresh(),
        ]);
    }

    protected function applyActionStatus(Request $request, Order $order, string $status, string $message): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user, $order);

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'courier_name' => ['nullable', 'string', 'max:120'],
        ]);

        $fulfillment = $this->getOrCreateFulfillment($order);

        return DB::transaction(function () use ($validated, $fulfillment, $order, $user, $status, $message) {
            $updates = ['status' => $status];
            if (array_key_exists('notes', $validated)) {
                $updates['notes'] = $validated['notes'];
            }
            if (array_key_exists('tracking_number', $validated)) {
                $updates['tracking_number'] = $validated['tracking_number'];
            }
            if (array_key_exists('courier_name', $validated)) {
                $updates['courier_name'] = $validated['courier_name'];
            }
            $this->applyTimestampFromStatus($updates, $status);

            $fulfillment->update($updates);
            $this->syncOrderFromFulfillmentStatus($order, $status);
            $this->syncStageFromFulfillmentStatus($order, $status, $user->id, $validated['notes'] ?? null);
            $this->writeFulfillmentProgress($order, $status, $validated['notes'] ?? null, $user->id);
            $this->notifyFulfillmentStatus($order, $status);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'fulfillment_action');

            return response()->json([
                'message' => $message,
                'fulfillment' => $fulfillment->fresh(),
                'order' => $order->fresh(),
            ]);
        });
    }

    protected function syncStageFromFulfillmentStatus(Order $order, string $status, int $actorUserId, ?string $notes): void
    {
        $stageCode = match ($status) {
            'ready' => $order->fulfillment_type === 'delivery' ? 'shipping' : 'pickup_ready',
            'shipped' => 'shipping',
            'delivered' => 'delivered',
            'picked_up' => 'completed',
            default => null,
        };

        if (! $stageCode) {
            return;
        }

        $existing = OrderStageHistory::where('order_id', $order->id)
            ->where('stage_code', $stageCode)
            ->latest('id')
            ->first();

        if (! $existing) {
            OrderStageHistory::create([
                'order_id' => $order->id,
                'stage_code' => $stageCode,
                'stage_status' => $stageCode === 'completed' ? 'done' : 'active',
                'started_at' => now(),
                'ended_at' => in_array($stageCode, ['delivered', 'completed'], true) ? now() : null,
                'actor_user_id' => $actorUserId,
                'notes' => $notes,
            ]);
            return;
        }

        $update = [
            'actor_user_id' => $actorUserId,
            'notes' => $notes ?? $existing->notes,
        ];

        if ($stageCode === 'completed') {
            $update['stage_status'] = 'done';
            $update['ended_at'] = now();
        } elseif ($existing->stage_status === 'pending') {
            $update['stage_status'] = 'active';
            $update['started_at'] = $existing->started_at ?? now();
        }

        if ($stageCode === 'delivered') {
            $update['stage_status'] = 'done';
            $update['ended_at'] = now();
        }

        $existing->update($update);
    }

    protected function syncOrderFromFulfillmentStatus(Order $order, string $status): void
    {
        $updates = match ($status) {
            'ready' => [
                'status' => $order->fulfillment_type === 'delivery' ? 'shipped' : 'ready_for_pickup',
                'current_stage' => $order->fulfillment_type === 'delivery' ? 'shipping' : 'pickup_ready',
            ],
            'shipped' => [
                'status' => 'shipped',
                'current_stage' => 'shipping',
            ],
            'delivered' => [
                'status' => 'shipped',
                'current_stage' => 'delivered',
            ],
            'picked_up' => [
                'status' => 'completed',
                'current_stage' => 'completed',
                'completed_at' => now(),
            ],
            'cancelled' => [
                'status' => 'cancelled',
                'current_stage' => 'cancelled',
                'cancelled_at' => $order->cancelled_at ?? now(),
            ],
            default => null,
        };

        if ($updates) {
            $order->update($updates);
        }
    }

    protected function getOrCreateFulfillment(Order $order): Fulfillment
    {
        return $order->fulfillment ?: Fulfillment::create([
            'order_id' => $order->id,
            'fulfillment_type' => $order->fulfillment_type,
            'delivery_address' => $order->delivery_address,
            'status' => 'pending',
        ]);
    }

    protected function applyTimestampFromStatus(array &$updates, string $status): void
    {
        if ($status === 'shipped') {
            $updates['shipped_at'] = now();
        }

        if ($status === 'delivered') {
            $updates['delivered_at'] = now();
        }

        if ($status === 'picked_up') {
            $updates['received_at'] = now();
        }
    }

    protected function writeFulfillmentProgress(Order $order, string $status, ?string $notes, int $actorUserId): void
    {
        $title = match ($status) {
            'ready' => 'Fulfillment ready',
            'shipped' => 'Order shipped',
            'delivered' => 'Order delivered',
            'picked_up' => 'Order picked up',
            'failed' => 'Fulfillment failed',
            'cancelled' => 'Fulfillment cancelled',
            default => 'Fulfillment updated',
        };

        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $notes,
            'actor_user_id' => $actorUserId,
        ]);
    }

    protected function notifyFulfillmentStatus(Order $order, string $status): void
    {
        $payload = match ($status) {
            'ready' => [
                'type' => 'order_ready_for_pickup',
                'title' => $order->fulfillment_type === 'delivery' ? 'Order ready for shipment' : 'Order ready for pickup',
                'message' => $order->fulfillment_type === 'delivery'
                    ? 'Order '.$order->order_number.' is packed and ready for shipment.'
                    : 'Order '.$order->order_number.' is ready for pickup.',
            ],
            'shipped' => [
                'type' => 'order_shipped',
                'title' => 'Order shipped',
                'message' => 'Order '.$order->order_number.' has been shipped.',
            ],
            'delivered' => [
                'type' => 'order_delivered',
                'title' => 'Order delivered',
                'message' => 'Order '.$order->order_number.' has been delivered.',
            ],
            'picked_up' => [
                'type' => 'order_completed',
                'title' => 'Order completed',
                'message' => 'Order '.$order->order_number.' was picked up and marked completed.',
            ],
            default => null,
        };

        if (! $payload) {
            return;
        }

        PlatformNotification::create([
            'user_id' => $order->client_user_id,
            'type' => $payload['type'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);
    }

    protected function authorizeAccess($user, Order $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isClient() && (int) $order->client_user_id === (int) $user->id) {
            return;
        }

        if (in_array($user->role, ['owner', 'hr', 'staff'], true) && (int) $user->shop_id === (int) $order->shop_id) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    protected function authorizeManage($user, Order $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (in_array($user->role, ['owner', 'hr', 'staff'], true) && (int) $user->shop_id === (int) $order->shop_id) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
