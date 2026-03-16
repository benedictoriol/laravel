<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Fulfillment;
use App\Models\OrderException;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use App\Services\OrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderStageController extends Controller
{
    public function __construct(protected OrderWorkflowService $workflowService)
    {
    }

    public function index(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $this->canViewOrder($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $stages = OrderStageHistory::query()
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        return response()->json($stages);
    }

    public function store(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $this->canManageStages($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'stage_code' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $stage = OrderStageHistory::create([
            'order_id' => $order->id,
            'stage_code' => $validated['stage_code'],
            'stage_status' => 'active',
            'started_at' => now(),
            'actor_user_id' => $user->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->writeProgressLog(
            $order,
            $validated['stage_code'],
            'Stage started',
            $validated['notes'] ?? null,
            $user->id
        );

        app(OrderAssignmentController::class)->autoAssignForStage($order->fresh(['shop', 'fulfillment']), $validated['stage_code'], $user->id);

        return response()->json([
            'message' => 'Stage created successfully.',
            'stage' => $stage,
        ], 201);
    }

    public function update(Request $request, Order $order, OrderStageHistory $stage)
    {
        $user = $request->user();

        if ($stage->order_id !== $order->id) {
            abort(404, 'Stage does not belong to this order.');
        }

        if (! $this->canManageStages($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'stage_status' => ['nullable', 'in:pending,active,done,failed,skipped'],
        ]);

        $stage->update([
            'notes' => $validated['notes'] ?? $stage->notes,
            'stage_status' => $validated['stage_status'] ?? $stage->stage_status,
        ]);

        return response()->json([
            'message' => 'Stage updated successfully.',
            'stage' => $stage->fresh(),
        ]);
    }

    public function complete(Request $request, Order $order, OrderStageHistory $stage)
    {
        $user = $request->user();

        if ($stage->order_id !== $order->id) {
            abort(404, 'Stage does not belong to this order.');
        }

        if (! $this->canManageStages($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        if ($stage->stage_status === 'done') {
            return response()->json([
                'message' => 'Stage is already completed.',
                'stage' => $stage,
            ]);
        }

        $result = $this->workflowService->completeStage($order, $stage, $user->id, $stage->notes);

        if (! empty($result['next_stage'])) {
            app(OrderAssignmentController::class)->autoAssignForStage(
                $order->fresh(['shop', 'fulfillment']),
                $result['next_stage']->stage_code,
                $user->id
            );
        }

        return response()->json([
            'message' => 'Stage completed successfully.',
            'data' => $result,
        ]);
    }

    protected function getNextStageCode(string $current, Order $order): ?string
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

    protected function applyOrderStatusFromStage(Order $order, string $stageCode): void
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
            'completed' => ['status' => 'completed', 'current_stage' => 'completed'],
        ];

        if (isset($map[$stageCode])) {
            $order->update($map[$stageCode]);
        }
    }


    protected function syncFulfillmentFromStage(Order $order, string $stageCode): void
    {
        if (! class_exists(Fulfillment::class)) {
            return;
        }

        $fulfillment = $order->fulfillment;
        if (! $fulfillment) {
            return;
        }

        $updates = match ($stageCode) {
            'pickup_ready' => ['status' => 'ready'],
            'shipping' => ['status' => 'shipped', 'shipped_at' => now()],
            'delivered' => ['status' => 'delivered', 'delivered_at' => now()],
            'completed' => [
                'status' => $order->fulfillment_type === 'delivery' ? 'delivered' : 'picked_up',
                'received_at' => $order->fulfillment_type === 'pickup' ? now() : $fulfillment->received_at,
                'delivered_at' => $order->fulfillment_type === 'delivery' ? ($fulfillment->delivered_at ?? now()) : $fulfillment->delivered_at,
            ],
            'cancelled' => ['status' => 'cancelled'],
            default => null,
        };

        if ($updates) {
            $fulfillment->update($updates);
        }
    }

    protected function writeProgressLog(
        Order $order,
        string $status,
        string $title,
        ?string $description,
        ?int $actorUserId
    ): void {
        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'actor_user_id' => $actorUserId,
        ]);
    }

    protected function canViewOrder($user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'client' && (int) $order->client_user_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role, ['owner', 'hr', 'staff'], true) && (int) $user->shop_id === (int) $order->shop_id) {
            return true;
        }

        return false;
    }

    protected function canManageStages($user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if (in_array($user->role, ['owner', 'hr', 'staff'], true) && (int) $user->shop_id === (int) $order->shop_id) {
            return true;
        }

        return false;
    }

    protected function notifyStageCompleted(Order $order, string $stageCode): void
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

    protected function notifyStageStarted(Order $order, string $stageCode): void
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

    protected function notifyOrderCompleted(Order $order): void
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

    protected function humanizeStage(string $stageCode): string
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

    public function fail(Request $request, Order $order, OrderStageHistory $stage)
    {
        return $this->failStage($request, $order, $stage);
    }

    public function failStage(Request $request, Order $order, OrderStageHistory $stage)
    {
        $user = $request->user();

        if ($stage->order_id !== $order->id) {
            abort(404, 'Stage does not belong to this order.');
        }

        if (! $this->canManageStages($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $stage->update([
            'stage_status' => 'failed',
            'ended_at' => now(),
            'actor_user_id' => $user->id,
            'notes' => $validated['notes'],
        ]);

        $this->writeProgressLog(
            $order,
            'stage_failed',
            'Stage failed',
            $validated['notes'],
            $user->id
        );

        if (class_exists(OrderException::class)) {
            OrderException::create([
                'order_id' => $order->id,
                'exception_type' => 'stage_failure',
                'severity' => 'high',
                'status' => 'open',
                'notes' => $validated['notes'],
            ]);
        }

        if ($order->fulfillment) {
            $order->fulfillment->update([
                'status' => 'failed',
                'notes' => $validated['notes'],
            ]);
        }

        PlatformNotification::create([
            'user_id' => $order->client_user_id,
            'type' => 'order_stage_failed',
            'title' => 'Order stage issue',
            'message' => 'There was an issue during "'.$this->humanizeStage($stage->stage_code).'" for order '.$order->order_number.'.',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);

        return response()->json([
            'message' => 'Stage marked as failed.',
            'stage' => $stage->fresh(),
        ]);
    }

    public function cancelOrder(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $this->canManageStages($user, $order) && ! $user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $order->update([
            'status' => 'cancelled',
            'current_stage' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_reason' => $validated['reason'],
        ]);

        OrderStageHistory::create([
            'order_id' => $order->id,
            'stage_code' => 'cancelled',
            'stage_status' => 'done',
            'started_at' => now(),
            'ended_at' => now(),
            'actor_user_id' => $user->id,
            'notes' => $validated['reason'],
        ]);

        $this->writeProgressLog(
            $order,
            'cancelled',
            'Order cancelled',
            $validated['reason'],
            $user->id
        );

        PlatformNotification::create([
            'user_id' => $order->client_user_id,
            'type' => 'order_cancelled',
            'title' => 'Order cancelled',
            'message' => 'Your order '.$order->order_number.' has been cancelled. Reason: '.$validated['reason'],
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => $order->fresh(),
        ]);
    }
}