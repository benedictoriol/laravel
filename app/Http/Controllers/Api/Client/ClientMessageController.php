<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Order;
use App\Models\PlatformNotification;
use App\Models\Shop;
use App\Models\ShopProject;
use App\Models\DesignPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ClientMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shopIds = Order::where('client_user_id', $request->user()->id)->pluck('shop_id')->filter()->unique();

        $hasParticipantJson = Schema::hasColumn('message_threads', 'participant_user_ids_json');

        $query = MessageThread::with(['messages.sender:id,name'])
            ->where(function ($builder) use ($shopIds, $request, $hasParticipantJson) {
                $builder->whereIn('shop_id', $shopIds);

                if ($hasParticipantJson) {
                    $builder->orWhereJsonContains('participant_user_ids_json', $request->user()->id);
                }
            })
            ->where(function ($builder) use ($request, $hasParticipantJson) {
                if ($hasParticipantJson) {
                    $builder->whereJsonContains('participant_user_ids_json', $request->user()->id)
                        ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('client_user_id', $request->user()->id));

                    return;
                }

                $builder->whereHas('order', fn ($orderQuery) => $orderQuery->where('client_user_id', $request->user()->id));
            });

        return response()->json($query->latest('last_message_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string'],
            'context_type' => ['nullable', 'in:shop_project,design_post'],
            'context_id' => ['nullable', 'integer'],
        ]);

        $shop = Shop::findOrFail($validated['shop_id']);
        $hasRelationship = Order::where('client_user_id', $request->user()->id)
            ->where('shop_id', $shop->id)
            ->when(! empty($validated['order_id']), fn ($query) => $query->where('id', $validated['order_id']))
            ->exists();

        $allowedByContext = false;
        if (($validated['context_type'] ?? null) === 'shop_project' && ! empty($validated['context_id'])) {
            $project = ShopProject::find($validated['context_id']);
            $allowedByContext = $project && (int) $project->shop_id === (int) $shop->id;
        }
        if (($validated['context_type'] ?? null) === 'design_post' && ! empty($validated['context_id'])) {
            $designPost = DesignPost::find($validated['context_id']);
            $allowedByContext = $designPost && ((int) $designPost->selected_shop_id === (int) $shop->id || $designPost->applications()->where('shop_id', $shop->id)->exists());
        }

        abort_unless($hasRelationship || $allowedByContext, 403, 'You can only message related shops.');

        $threadType = ! empty($validated['order_id'])
            ? 'order_chat'
            : (($validated['context_type'] ?? null) === 'shop_project' ? 'project_inquiry' : (($validated['context_type'] ?? null) === 'design_post' ? 'design_post_inquiry' : 'direct_message'));

        $thread = MessageThread::firstOrCreate(
            [
                'shop_id' => $shop->id,
                'order_id' => $validated['order_id'] ?? null,
                'title' => $validated['title'],
            ],
            array_filter([
                'type' => $threadType,
                'participant_user_ids_json' => Schema::hasColumn('message_threads', 'participant_user_ids_json')
                    ? array_values(array_filter([$request->user()->id, $shop->owner_user_id]))
                    : null,
                'last_message_at' => now(),
            ], fn ($value) => $value !== null)
        );

        $message = Message::create([
            'thread_id' => $thread->id,
            'sender_user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        $thread->forceFill(['last_message_at' => now()])->save();

        if ($shop->owner_user_id) {
            PlatformNotification::create([
                'user_id' => $shop->owner_user_id,
                'type' => 'client_message_received',
                'title' => 'New client message',
                'message' => $validated['title'],
                'reference_type' => 'message_thread',
                'reference_id' => $thread->id,
                'channel' => 'web',
                'category' => 'support',
                'priority' => 'medium',
                'action_label' => 'Open chat',
            ]);
        }

        return response()->json($thread->load(['messages.sender:id,name']), 201);
    }

    public function postMessage(Request $request, MessageThread $thread): JsonResponse
    {
        abort_unless((Schema::hasColumn('message_threads', 'participant_user_ids_json') && in_array($request->user()->id, $thread->participant_user_ids_json ?? [], true)) || optional($thread->order)->client_user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'message' => ['required', 'string'],
            'attachments_json' => ['nullable', 'array'],
        ]);

        $message = Message::create([
            'thread_id' => $thread->id,
            'sender_user_id' => $request->user()->id,
            'message' => $validated['message'],
            'attachments_json' => $validated['attachments_json'] ?? null,
        ]);

        $thread->forceFill(['last_message_at' => now()])->save();

        if (optional($thread->shop)->owner_user_id) {
            PlatformNotification::create([
                'user_id' => $thread->shop->owner_user_id,
                'type' => 'client_message_reply',
                'title' => 'New client reply',
                'message' => mb_strimwidth($validated['message'], 0, 80, '...'),
                'reference_type' => 'message_thread',
                'reference_id' => $thread->id,
                'channel' => 'web',
                'category' => 'support',
                'priority' => 'medium',
                'action_label' => 'Reply',
            ]);
        }

        return response()->json($message->load('sender:id,name'), 201);
    }
}
