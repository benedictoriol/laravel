<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PlatformNotification;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::query()->with(['order', 'shop', 'client']);
        $user = $request->user();

        if ($user->isClient()) {
            $query->where('client_user_id', $user->id);
        } elseif (! $user->isAdmin()) {
            $query->where('shop_id', $user->shop_id ?? 0);
        }

        return response()->json($query->latest('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isClient()) {
            abort(403, 'Only clients can submit reviews.');
        }

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'review_title' => ['nullable', 'string', 'max:180'],
            'review_text' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $order = Order::with('shop')->findOrFail($validated['order_id']);
        if ($order->client_user_id !== $user->id) {
            abort(403, 'You can only review your own orders.');
        }
        if ($order->status !== 'completed') {
            abort(422, 'Only completed orders can be reviewed.');
        }

        $review = Review::updateOrCreate(
            ['order_id' => $order->id, 'client_user_id' => $user->id],
            [
                'shop_id' => $order->shop_id,
                'rating' => $validated['rating'],
                'review_title' => $validated['review_title'] ?? null,
                'review_text' => $validated['review_text'] ?? null,
                'is_public' => $validated['is_public'] ?? true,
            ]
        );

        PlatformNotification::create([
            'user_id' => $order->shop->owner_user_id,
            'type' => 'review_received',
            'title' => 'New review received',
            'message' => 'A client reviewed order '.$order->order_number.'.',
            'reference_type' => 'review',
            'reference_id' => $review->id,
            'channel' => 'web',
        ]);

        return response()->json($review->load(['order', 'shop', 'client']), 201);
    }
}
