<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\PlatformNotification;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            SupportTicket::with(['user:id,name', 'shop:id,shop_name', 'order:id,order_number'])
                ->where('shop_id', $request->user()->shop_id)
                ->latest('id')
                ->get()
        );
    }

    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_unless((int) $supportTicket->shop_id === (int) $request->user()->shop_id, 403);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:open,in_progress,awaiting_response,resolved,closed'],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (($validated['status'] ?? null) === 'resolved') {
            $validated['resolved_at'] = now();
        }

        $supportTicket->update($validated);

        PlatformNotification::create([
            'user_id' => $supportTicket->user_id,
            'type' => 'support_ticket_owner_update',
            'title' => 'Support ticket updated',
            'message' => $supportTicket->subject,
            'reference_type' => 'support_ticket',
            'reference_id' => $supportTicket->id,
            'channel' => 'web',
            'category' => 'support',
            'priority' => $supportTicket->priority,
            'action_label' => 'View status',
        ]);

        return response()->json($supportTicket->fresh(['user:id,name', 'shop:id,shop_name', 'order:id,order_number']));
    }
}
