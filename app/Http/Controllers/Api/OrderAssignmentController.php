<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use App\Models\ShopMember;
use App\Models\User;
use App\Services\AnalyticsAutomationService;
use App\Services\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderAssignmentController extends Controller
{
    public function __construct(protected OrderWorkflowService $workflowService)
    {
    }

    public function index(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (! $this->canViewOrder($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $query = $order->assignments()->with(['assignee', 'assigner'])->latest('id');

        if ($request->boolean('mine')) {
            $query->where('assigned_to', $user->id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (! $this->canManageAssignments($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_role' => ['required', Rule::in(['hr', 'staff'])],
            'assignment_type' => ['required', Rule::in(['digitizing', 'embroidery', 'quality_check', 'packing', 'delivery', 'other'])],
            'notes' => ['nullable', 'string'],
            'auto_select' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($validated, $order, $user) {
            $assignee = null;

            if (! empty($validated['assigned_to'])) {
                $assignee = User::findOrFail($validated['assigned_to']);
                $this->guardAssigneeBelongsToShop($order, $assignee, $validated['assignment_role']);
            } else {
                $assignee = $this->resolveBestAssignee($order, $validated['assignment_role']);
                if (! $assignee) {
                    abort(422, 'No active '.$validated['assignment_role'].' is available for assignment.');
                }
            }

            $assignment = OrderAssignment::create([
                'order_id' => $order->id,
                'assigned_to' => $assignee->id,
                'assigned_by' => $user->id,
                'assignment_role' => $validated['assignment_role'],
                'assignment_type' => $validated['assignment_type'],
                'status' => 'assigned',
                'assigned_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->workflowService->writeProgressLog(
                $order,
                'assignment_created',
                'Work assignment created',
                ucfirst(str_replace('_', ' ', $validated['assignment_type'])).' assigned to '.$assignee->name.'.',
                $user->id
            );

            $this->notifyAssignmentCreated($order, $assignment);

            return response()->json([
                'message' => 'Assignment created successfully.',
                'assignment' => $assignment->load(['assignee', 'assigner']),
            ], 201);
        });
    }

    public function update(Request $request, OrderAssignment $assignment): JsonResponse
    {
        $user = $request->user();
        $assignment->loadMissing('order');

        if (! $this->canManageAssignments($user, $assignment->order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_role' => ['nullable', Rule::in(['hr', 'staff'])],
            'assignment_type' => ['nullable', Rule::in(['digitizing', 'embroidery', 'quality_check', 'packing', 'delivery', 'other'])],
            'status' => ['nullable', Rule::in(['assigned', 'in_progress', 'done', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($validated['assigned_to'])) {
            $assignee = User::findOrFail($validated['assigned_to']);
            $role = $validated['assignment_role'] ?? $assignment->assignment_role;
            $this->guardAssigneeBelongsToShop($assignment->order, $assignee, $role);
        }

        if (($validated['status'] ?? null) === 'done') {
            $validated['completed_at'] = now();
        }

        $assignment->update($validated);

        return response()->json([
            'message' => 'Assignment updated successfully.',
            'assignment' => $assignment->fresh()->load(['assignee', 'assigner']),
        ]);
    }

    public function accept(Request $request, OrderAssignment $assignment): JsonResponse
    {
        $user = $request->user();
        $assignment->loadMissing('order');

        if ((int) $assignment->assigned_to !== (int) $user->id && ! $user->isAdmin() && ! $this->canManageAssignments($user, $assignment->order)) {
            abort(403, 'Unauthorized.');
        }

        if ($assignment->status !== 'assigned') {
            return response()->json([
                'message' => 'Only assignments in assigned status can be accepted.',
            ], 422);
        }

        $assignment->update([
            'status' => 'in_progress',
        ]);

        $this->workflowService->writeProgressLog(
            $assignment->order,
            'assignment_in_progress',
            'Assignment accepted',
            $user->name.' accepted '.str_replace('_', ' ', $assignment->assignment_type).' assignment.',
            $user->id
        );

        if ((int) $assignment->assigned_by !== (int) $user->id) {
            PlatformNotification::create([
                'user_id' => $assignment->assigned_by,
                'type' => 'order_assignment_accepted',
                'title' => 'Assignment accepted',
                'message' => $user->name.' accepted '.str_replace('_', ' ', $assignment->assignment_type).' for order '.$assignment->order->order_number.'.',
                'reference_type' => 'order',
                'reference_id' => $assignment->order_id,
                'channel' => 'web',
            ]);
        }

        app(AnalyticsAutomationService::class)->refreshForOrder($assignment->order->fresh(), 'assignment_accepted');

        return response()->json([
            'message' => 'Assignment accepted.',
            'assignment' => $assignment->fresh()->load(['assignee', 'assigner']),
        ]);
    }

    public function complete(Request $request, OrderAssignment $assignment): JsonResponse
    {
        $user = $request->user();
        $assignment->loadMissing(['order.shop', 'order.fulfillment', 'assignee', 'assigner']);

        if ((int) $assignment->assigned_to !== (int) $user->id && ! $user->isAdmin() && ! $this->canManageAssignments($user, $assignment->order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        if (! in_array($assignment->status, ['assigned', 'in_progress'], true)) {
            return response()->json([
                'message' => 'Only active assignments can be completed.',
            ], 422);
        }

        $assignment->update([
            'status' => 'done',
            'completed_at' => now(),
            'notes' => $validated['notes'] ?? $assignment->notes,
        ]);

        $this->workflowService->writeProgressLog(
            $assignment->order,
            'assignment_done',
            'Assignment completed',
            $user->name.' completed '.str_replace('_', ' ', $assignment->assignment_type).' assignment.',
            $user->id
        );

        if ((int) $assignment->assigned_by !== (int) $user->id) {
            PlatformNotification::create([
                'user_id' => $assignment->assigned_by,
                'type' => 'order_assignment_completed',
                'title' => 'Assignment completed',
                'message' => $user->name.' completed '.str_replace('_', ' ', $assignment->assignment_type).' for order '.$assignment->order->order_number.'.',
                'reference_type' => 'order',
                'reference_id' => $assignment->order_id,
                'channel' => 'web',
            ]);
        }

        $activeStage = OrderStageHistory::where('order_id', $assignment->order_id)
            ->where('stage_status', 'active')
            ->latest('id')
            ->first();

        $stageCode = $this->mapAssignmentTypeToStageCode($assignment->assignment_type);
        $autoAdvanced = false;
        $workflowResult = null;

        if ($activeStage && $stageCode === $activeStage->stage_code) {
            $remaining = OrderAssignment::where('order_id', $assignment->order_id)
                ->where('assignment_type', $assignment->assignment_type)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();

            if ($remaining === 0) {
                $workflowResult = $this->workflowService->completeStage(
                    $assignment->order,
                    $activeStage,
                    $user->id,
                    $validated['notes'] ?? null
                );

                if (! empty($workflowResult['next_stage'])) {
                    $this->autoAssignForStage(
                        $assignment->order->fresh(['shop', 'fulfillment']),
                        $workflowResult['next_stage']->stage_code,
                        $user->id
                    );
                }

                $autoAdvanced = true;
            }
        }

        app(AnalyticsAutomationService::class)->refreshForOrder($assignment->order->fresh(), 'assignment_completed');

        return response()->json([
            'message' => $autoAdvanced ? 'Assignment completed and stage auto-advanced.' : 'Assignment completed successfully.',
            'assignment' => $assignment->fresh()->load(['assignee', 'assigner']),
            'auto_advanced' => $autoAdvanced,
            'workflow' => $workflowResult,
        ]);
    }

    public function autoAssignForStage(Order $order, string $stageCode, int $actorUserId): ?OrderAssignment
    {
        $config = $this->assignmentConfigForStage($stageCode);
        if (! $config) {
            return null;
        }

        $existingActive = OrderAssignment::where('order_id', $order->id)
            ->where('assignment_type', $config['assignment_type'])
            ->whereIn('status', ['assigned', 'in_progress'])
            ->first();

        if ($existingActive) {
            return $existingActive;
        }

        $assignee = $this->resolveBestAssignee($order, $config['assignment_role']);
        if (! $assignee) {
            return null;
        }

        $assignment = OrderAssignment::create([
            'order_id' => $order->id,
            'assigned_to' => $assignee->id,
            'assigned_by' => $actorUserId,
            'assignment_role' => $config['assignment_role'],
            'assignment_type' => $config['assignment_type'],
            'status' => 'assigned',
            'assigned_at' => now(),
            'notes' => 'Auto-assigned when '.$stageCode.' started.',
        ]);

        $this->workflowService->writeProgressLog(
            $order,
            'assignment_auto_created',
            'Assignment auto-created',
            ucfirst(str_replace('_', ' ', $config['assignment_type'])).' auto-assigned to '.$assignee->name.'.',
            $actorUserId
        );

        $this->notifyAssignmentCreated($order, $assignment);

        return $assignment;
    }

    protected function assignmentConfigForStage(string $stageCode): ?array
    {
        return match ($stageCode) {
            'digitizing' => ['assignment_role' => 'staff', 'assignment_type' => 'digitizing'],
            'production' => ['assignment_role' => 'staff', 'assignment_type' => 'embroidery'],
            'quality_check' => ['assignment_role' => 'staff', 'assignment_type' => 'quality_check'],
            'packing' => ['assignment_role' => 'staff', 'assignment_type' => 'packing'],
            'shipping' => ['assignment_role' => 'staff', 'assignment_type' => 'delivery'],
            default => null,
        };
    }

    protected function mapAssignmentTypeToStageCode(string $assignmentType): ?string
    {
        return match ($assignmentType) {
            'digitizing' => 'digitizing',
            'embroidery' => 'production',
            'quality_check' => 'quality_check',
            'packing' => 'packing',
            'delivery' => 'shipping',
            default => null,
        };
    }

    protected function resolveBestAssignee(Order $order, string $assignmentRole): ?User
    {
        $activeMemberIds = ShopMember::query()
            ->where('shop_id', $order->shop_id)
            ->where('member_role', $assignmentRole)
            ->where('employment_status', 'active')
            ->pluck('user_id');

        if ($activeMemberIds->isEmpty() && $assignmentRole === 'staff') {
            $activeMemberIds = ShopMember::query()
                ->where('shop_id', $order->shop_id)
                ->where('member_role', 'hr')
                ->where('employment_status', 'active')
                ->pluck('user_id');
        }

        if ($activeMemberIds->isEmpty()) {
            return null;
        }

        $assigneeId = OrderAssignment::query()
            ->select('assigned_to')
            ->selectRaw("SUM(CASE WHEN status IN ('assigned', 'in_progress') THEN 1 ELSE 0 END) as active_load")
            ->whereIn('assigned_to', $activeMemberIds)
            ->groupBy('assigned_to')
            ->orderBy('active_load')
            ->orderBy('assigned_to')
            ->value('assigned_to');

        if ($assigneeId) {
            return User::find($assigneeId);
        }

        return User::whereIn('id', $activeMemberIds)->orderBy('id')->first();
    }

    protected function guardAssigneeBelongsToShop(Order $order, User $assignee, string $expectedRole): void
    {
        $member = ShopMember::where('shop_id', $order->shop_id)
            ->where('user_id', $assignee->id)
            ->where('employment_status', 'active')
            ->first();

        if (! $member) {
            abort(422, 'Selected assignee is not an active member of this shop.');
        }

        if ($member->member_role !== $expectedRole) {
            abort(422, 'Selected assignee does not match the required assignment role.');
        }
    }

    protected function notifyAssignmentCreated(Order $order, OrderAssignment $assignment): void
    {
        PlatformNotification::create([
            'user_id' => $assignment->assigned_to,
            'type' => 'order_assignment_created',
            'title' => 'New work assignment',
            'message' => 'You were assigned '.str_replace('_', ' ', $assignment->assignment_type).' for order '.$order->order_number.'.',
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'channel' => 'web',
        ]);

        $ownerId = optional($order->shop)->owner_user_id;
        if ($ownerId && (int) $ownerId !== (int) $assignment->assigned_to) {
            PlatformNotification::create([
                'user_id' => $ownerId,
                'type' => 'order_assignment_created',
                'title' => 'Order assignment created',
                'message' => ucfirst(str_replace('_', ' ', $assignment->assignment_type)).' was assigned for order '.$order->order_number.'.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        }
    }

    protected function canViewOrder($user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isClient() && (int) $order->client_user_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role, ['owner', 'hr', 'staff'], true) && (int) $user->shop_id === (int) $order->shop_id) {
            return true;
        }

        return false;
    }

    protected function canManageAssignments($user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return in_array($user->role, ['owner', 'hr'], true) && (int) $user->shop_id === (int) $order->shop_id;
    }
}
