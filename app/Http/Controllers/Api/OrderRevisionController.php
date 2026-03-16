<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderAssignment;
use App\Models\OrderProgressLog;
use App\Models\OrderRevision;
use App\Models\PlatformNotification;
use App\Services\AnalyticsAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderRevisionController extends Controller
{
    public function index(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrderAccess($request->user(), $order);

        return response()->json(
            $order->revisions()->with(['requestedBy', 'handledBy', 'item'])->latest('id')->get()
        );
    }

    public function show(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $this->authorizeRevisionAccess($request->user(), $order, $revision);

        return response()->json($revision->load(['requestedBy', 'handledBy', 'item', 'order']));
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $this->authorizeOrderAccess($user, $order);

        $validated = $request->validate([
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'revision_type' => ['required', 'in:design_change,size_change,color_change,placement_change,text_change,file_fix,other'],
            'request_notes' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order, $user) {
            $nextRevisionNo = (int) ($order->revisions()->max('revision_no') ?? 0) + 1;

            $revision = OrderRevision::create([
                'order_id' => $order->id,
                'order_item_id' => $validated['order_item_id'] ?? null,
                'revision_no' => $nextRevisionNo,
                'requested_by' => $user->id,
                'revision_type' => $validated['revision_type'],
                'request_notes' => $validated['request_notes'],
                'status' => 'requested',
            ]);

            $this->writeProgressLog(
                $order,
                'revision_requested',
                'Revision requested',
                'Revision #'.$revision->revision_no.' was requested: '.$validated['request_notes'],
                $user->id
            );

            $this->notifyRevisionCreated($order, $revision, $user->id);
            $this->autoCreateRevisionAssignment($order, $revision, $user->id);

            return response()->json($revision->load(['requestedBy', 'handledBy', 'item']), 201);
        });
    }

    public function update(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if ($user->isClient()) {
            abort(403, 'Clients cannot finalize revision handling through the generic update endpoint.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:requested,in_review,preview_uploaded,approved,rejected,implemented,cancelled'],
            'response_notes' => ['nullable', 'string'],
            'preview_file_path' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($validated, $order, $revision, $user) {
            $updates = [
                'status' => $validated['status'],
                'response_notes' => $validated['response_notes'] ?? $revision->response_notes,
                'preview_file_path' => $validated['preview_file_path'] ?? $revision->preview_file_path,
                'handled_by' => $user->id,
            ];

            if ($validated['status'] === 'approved') {
                $updates['approved_at'] = now();
            } elseif ($validated['status'] === 'rejected') {
                $updates['rejected_at'] = now();
            } elseif ($validated['status'] === 'implemented') {
                $updates['completed_at'] = now();
            }

            $revision->update($updates);
            $this->writeProgressLog(
                $order,
                'revision_'.$revision->status,
                'Revision updated',
                'Revision #'.$revision->revision_no.' is now '.$revision->status.'.',
                $user->id
            );
            $this->notifyRevisionUpdated($order, $revision, $user->id);

            return response()->json($revision->fresh(['requestedBy', 'handledBy', 'item']));
        });
    }

    public function claim(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if ($user->isClient()) {
            abort(403, 'Clients cannot claim revisions.');
        }

        return DB::transaction(function () use ($order, $revision, $user) {
            if ($revision->status === 'implemented') {
                abort(422, 'Implemented revisions can no longer be claimed.');
            }

            $revision->update([
                'handled_by' => $user->id,
                'status' => 'in_review',
            ]);

            $this->writeProgressLog(
                $order,
                'revision_in_review',
                'Revision claimed',
                $user->name.' claimed revision #'.$revision->revision_no.' for review.',
                $user->id
            );

            $this->syncOpenRevisionAssignment($order, $revision, $user->id);
            $this->notifyRevisionUpdated($order, $revision, $user->id);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'revision_claimed');

            return response()->json([
                'message' => 'Revision claimed successfully.',
                'revision' => $revision->fresh(['requestedBy', 'handledBy', 'item']),
            ]);
        });
    }

    public function uploadPreview(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if ($user->isClient()) {
            abort(403, 'Clients cannot upload revision previews.');
        }

        $validated = $request->validate([
            'preview_file_path' => ['required', 'string', 'max:255'],
            'response_notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order, $revision, $user) {
            $revision->update([
                'handled_by' => $revision->handled_by ?? $user->id,
                'status' => 'preview_uploaded',
                'preview_file_path' => $validated['preview_file_path'],
                'response_notes' => $validated['response_notes'] ?? $revision->response_notes,
            ]);

            $this->writeProgressLog(
                $order,
                'revision_preview_uploaded',
                'Revision preview uploaded',
                'Preview uploaded for revision #'.$revision->revision_no.'.',
                $user->id
            );

            $this->notifyRevisionUpdated($order, $revision, $user->id);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'revision_preview_uploaded');

            return response()->json([
                'message' => 'Revision preview uploaded.',
                'revision' => $revision->fresh(['requestedBy', 'handledBy', 'item']),
            ]);
        });
    }

    public function approve(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if (! $user->isAdmin() && ! $user->isClient()) {
            abort(403, 'Only the client or admin can approve revision previews.');
        }

        if ($user->isClient() && (int) $order->client_user_id !== (int) $user->id) {
            abort(403, 'Unauthorized revision approval.');
        }

        $validated = $request->validate([
            'response_notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order, $revision, $user) {
            $revision->update([
                'status' => 'approved',
                'approved_at' => now(),
                'response_notes' => $validated['response_notes'] ?? $revision->response_notes,
            ]);

            $this->writeProgressLog(
                $order,
                'revision_approved',
                'Revision approved',
                'Revision #'.$revision->revision_no.' was approved.',
                $user->id
            );

            $this->notifyRevisionUpdated($order, $revision, $user->id);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'revision_approved');

            return response()->json([
                'message' => 'Revision approved successfully.',
                'revision' => $revision->fresh(['requestedBy', 'handledBy', 'item']),
            ]);
        });
    }

    public function reject(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if (! $user->isAdmin() && ! $user->isClient()) {
            abort(403, 'Only the client or admin can reject revision previews.');
        }

        if ($user->isClient() && (int) $order->client_user_id !== (int) $user->id) {
            abort(403, 'Unauthorized revision rejection.');
        }

        $validated = $request->validate([
            'response_notes' => ['required', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order, $revision, $user) {
            $revision->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'response_notes' => $validated['response_notes'],
            ]);

            $this->writeProgressLog(
                $order,
                'revision_rejected',
                'Revision rejected',
                'Revision #'.$revision->revision_no.' was rejected: '.$validated['response_notes'],
                $user->id
            );

            $this->notifyRevisionUpdated($order, $revision, $user->id);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'revision_rejected');

            return response()->json([
                'message' => 'Revision rejected successfully.',
                'revision' => $revision->fresh(['requestedBy', 'handledBy', 'item']),
            ]);
        });
    }

    public function implement(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if ($user->isClient()) {
            abort(403, 'Clients cannot mark revisions as implemented.');
        }

        if ($revision->status !== 'approved') {
            abort(422, 'Only approved revisions can be implemented.');
        }

        $validated = $request->validate([
            'response_notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order, $revision, $user) {
            $revision->update([
                'handled_by' => $revision->handled_by ?? $user->id,
                'status' => 'implemented',
                'completed_at' => now(),
                'response_notes' => $validated['response_notes'] ?? $revision->response_notes,
            ]);

            $this->closeOpenRevisionAssignment($order, $revision, $user->id);

            $this->writeProgressLog(
                $order,
                'revision_implemented',
                'Revision implemented',
                'Revision #'.$revision->revision_no.' was implemented.',
                $user->id
            );

            $this->notifyRevisionUpdated($order, $revision, $user->id);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'revision_implemented');

            return response()->json([
                'message' => 'Revision implemented successfully.',
                'revision' => $revision->fresh(['requestedBy', 'handledBy', 'item']),
            ]);
        });
    }

    public function cancel(Request $request, Order $order, OrderRevision $revision): JsonResponse
    {
        $user = $request->user();
        $this->authorizeRevisionAccess($user, $order, $revision);

        if (! $user->isAdmin() && (int) $revision->requested_by !== (int) $user->id && (int) $order->shop_id !== (int) $user->shop_id) {
            abort(403, 'Unauthorized revision cancellation.');
        }

        if ($revision->status === 'implemented') {
            abort(422, 'Implemented revisions cannot be cancelled.');
        }

        $validated = $request->validate([
            'response_notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $order, $revision, $user) {
            $revision->update([
                'status' => 'cancelled',
                'response_notes' => $validated['response_notes'] ?? $revision->response_notes,
            ]);

            $this->closeOpenRevisionAssignment($order, $revision, $user->id, true);

            $this->writeProgressLog(
                $order,
                'revision_cancelled',
                'Revision cancelled',
                'Revision #'.$revision->revision_no.' was cancelled.',
                $user->id
            );

            $this->notifyRevisionUpdated($order, $revision, $user->id);

            app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'revision_cancelled');

            return response()->json([
                'message' => 'Revision cancelled successfully.',
                'revision' => $revision->fresh(['requestedBy', 'handledBy', 'item']),
            ]);
        });
    }

    private function autoCreateRevisionAssignment(Order $order, OrderRevision $revision, int $actorUserId): ?OrderAssignment
    {
        $assigneeId = $revision->handled_by;

        if (! $assigneeId) {
            $candidate = $order->shop
                ? $order->shop->members()
                    ->where('employment_status', 'active')
                    ->whereIn('member_role', ['staff', 'hr', 'owner'])
                    ->orderByRaw("FIELD(member_role, 'staff', 'hr', 'owner')")
                    ->orderBy('id')
                    ->first()
                : null;

            $assigneeId = optional($candidate)->user_id;
        }

        if (! $assigneeId) {
            return null;
        }

        return OrderAssignment::create([
            'order_id' => $order->id,
            'assigned_to' => $assigneeId,
            'assigned_by' => $actorUserId,
            'assignment_role' => 'staff',
            'assignment_type' => 'other',
            'status' => 'assigned',
            'assigned_at' => now(),
            'notes' => 'Revision #'.$revision->revision_no.' handling: '.$revision->revision_type,
        ]);
    }

    private function syncOpenRevisionAssignment(Order $order, OrderRevision $revision, int $actorUserId): void
    {
        $assignment = OrderAssignment::where('order_id', $order->id)
            ->where('assignment_type', 'other')
            ->where('notes', 'like', 'Revision #'.$revision->revision_no.'%')
            ->whereIn('status', ['assigned', 'in_progress'])
            ->latest('id')
            ->first();

        if (! $assignment) {
            $assignment = $this->autoCreateRevisionAssignment($order, $revision, $actorUserId);
        }

        if ($assignment && $revision->handled_by) {
            $assignment->update([
                'assigned_to' => $revision->handled_by,
                'status' => 'in_progress',
            ]);
        }
    }

    private function closeOpenRevisionAssignment(Order $order, OrderRevision $revision, int $actorUserId, bool $cancel = false): void
    {
        $assignment = OrderAssignment::where('order_id', $order->id)
            ->where('assignment_type', 'other')
            ->where('notes', 'like', 'Revision #'.$revision->revision_no.'%')
            ->whereIn('status', ['assigned', 'in_progress'])
            ->latest('id')
            ->first();

        if (! $assignment) {
            return;
        }

        $assignment->update([
            'status' => $cancel ? 'cancelled' : 'done',
            'completed_at' => now(),
            'assigned_by' => $assignment->assigned_by ?: $actorUserId,
        ]);
    }

    private function notifyRevisionCreated(Order $order, OrderRevision $revision, int $actorUserId): void
    {
        $recipients = array_unique(array_filter([
            $order->client_user_id,
            optional($order->shop)->owner_user_id,
            $revision->handled_by,
        ]));

        foreach ($recipients as $recipientId) {
            if ((int) $recipientId === (int) $actorUserId) {
                continue;
            }

            PlatformNotification::create([
                'user_id' => $recipientId,
                'type' => 'revision_requested',
                'title' => 'Revision requested',
                'message' => 'Revision #'.$revision->revision_no.' was requested for order '.$order->order_number.'.',
                'reference_type' => 'order_revision',
                'reference_id' => $revision->id,
                'channel' => 'web',
            ]);
        }
    }

    private function notifyRevisionUpdated(Order $order, OrderRevision $revision, int $actorUserId): void
    {
        $recipients = array_unique(array_filter([
            $order->client_user_id,
            optional($order->shop)->owner_user_id,
            $revision->handled_by,
        ]));

        foreach ($recipients as $recipientId) {
            if ((int) $recipientId === (int) $actorUserId) {
                continue;
            }

            PlatformNotification::create([
                'user_id' => $recipientId,
                'type' => 'revision_updated',
                'title' => 'Revision updated',
                'message' => 'Revision #'.$revision->revision_no.' for order '.$order->order_number.' is now '.$revision->status.'.',
                'reference_type' => 'order_revision',
                'reference_id' => $revision->id,
                'channel' => 'web',
            ]);
        }
    }

    private function writeProgressLog(Order $order, string $status, string $title, ?string $description, ?int $actorUserId): void
    {
        OrderProgressLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'actor_user_id' => $actorUserId,
        ]);
    }

    private function authorizeRevisionAccess($user, Order $order, OrderRevision $revision): void
    {
        $this->authorizeOrderAccess($user, $order);
        abort_if((int) $revision->order_id !== (int) $order->id, 404, 'Revision does not belong to this order.');
    }

    private function authorizeOrderAccess($user, Order $order): void
    {
        if ($user->isAdmin()) {
            return;
        }
        if ($user->isClient() && $order->client_user_id === $user->id) {
            return;
        }
        if (! $user->isClient() && $order->shop_id === $user->shop_id) {
            return;
        }
        abort(403, 'Unauthorized order access.');
    }
}
