<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\DisputeCase;
use App\Models\Order;
use App\Models\PlatformNotification;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            SupportTicket::with(['shop:id,shop_name', 'order:id,order_number'])
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'subject' => ['required', 'string', 'max:180'],
            'category' => ['required', 'in:orders,quotes,payments,production,inventory,delivery,support,disputes,exceptions'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'message' => ['required', 'string'],
            'attachments_json' => ['nullable', 'array'],
        ]);

        if (! empty($validated['order_id'])) {
            $order = Order::findOrFail($validated['order_id']);
            abort_if($order->client_user_id !== $request->user()->id, 403);
            $validated['shop_id'] = $validated['shop_id'] ?? $order->shop_id;
        }

        $ticket = SupportTicket::create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]));

        if (! empty($ticket->shop_id)) {
            $shopOwnerId = optional($ticket->shop)->owner_user_id;
            if ($shopOwnerId) {
                PlatformNotification::create([
                    'user_id' => $shopOwnerId,
                    'type' => 'support_ticket_created',
                    'title' => 'New support ticket',
                    'message' => $ticket->subject,
                    'reference_type' => 'support_ticket',
                    'reference_id' => $ticket->id,
                    'channel' => 'web',
                    'category' => 'support',
                    'priority' => $ticket->priority,
                    'action_label' => 'Open ticket',
                ]);
            }
        }

        if (in_array($ticket->category, ['delivery', 'payments', 'disputes', 'exceptions'], true) || in_array($ticket->priority, ['high', 'critical'], true)) {
            DisputeCase::firstOrCreate(
                ['shop_id' => $ticket->shop_id, 'order_id' => $ticket->order_id, 'title' => $ticket->subject],
                [
                    'complainant_user_id' => $ticket->user_id,
                    'issue_type' => $ticket->category,
                    'summary' => $ticket->message,
                    'status' => 'open',
                ]
            );
        }

        return response()->json($ticket->load(['shop:id,shop_name', 'order:id,order_number']), 201);
    }

    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        abort_if($supportTicket->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'subject' => ['sometimes', 'string', 'max:180'],
            'category' => ['sometimes', 'in:orders,quotes,payments,production,inventory,delivery,support,disputes,exceptions'],
            'priority' => ['sometimes', 'in:low,medium,high,critical'],
            'message' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:open,in_progress,awaiting_response,resolved,closed'],
            'attachments_json' => ['nullable', 'array'],
        ]);

        if (($validated['status'] ?? null) === 'resolved') {
            $validated['resolved_at'] = now();
        }

        $supportTicket->update($validated);

        $shopOwnerId = optional($supportTicket->shop)->owner_user_id;
        if ($shopOwnerId) {
            PlatformNotification::create([
                'user_id' => $shopOwnerId,
                'type' => 'support_ticket_updated',
                'title' => 'Support ticket updated',
                'message' => $supportTicket->subject,
                'reference_type' => 'support_ticket',
                'reference_id' => $supportTicket->id,
                'channel' => 'web',
                'category' => 'support',
                'priority' => $supportTicket->priority,
                'action_label' => 'Review',
            ]);
        }

        return response()->json($supportTicket->fresh(['shop:id,shop_name', 'order:id,order_number']));
    }
}
