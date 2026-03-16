<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderException;
use Illuminate\Http\Request;

class OrderExceptionController extends Controller
{
    public function index(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isClient() && ($user->shop_id !== $order->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isClient() && $order->client_user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        return response()->json($order->exceptions()->latest('id')->get());
    }

    public function store(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $user->isAdmin() && ($user->shop_id !== $order->shop_id)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'exception_type' => ['required', 'string', 'max:100'],
            'severity' => ['nullable', 'in:low,medium,high,critical'],
            'notes' => ['nullable', 'string'],
            'assigned_handler_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $exception = OrderException::create([
            'order_id' => $order->id,
            'exception_type' => $validated['exception_type'],
            'severity' => $validated['severity'] ?? 'medium',
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
            'assigned_handler_id' => $validated['assigned_handler_id'] ?? null,
        ]);

        return response()->json($exception, 200);
    }

    public function update(Request $request, OrderException $exception)
    {
        $validated = $request->validate([
            'severity' => ['nullable', 'in:low,medium,high,critical'],
            'status' => ['nullable', 'in:open,in_progress,escalated,resolved,dismissed'],
            'notes' => ['nullable', 'string'],
            'assigned_handler_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (($validated['status'] ?? null) === 'escalated' && empty($exception->escalated_at)) {
            $validated['escalated_at'] = now();
        }

        if (($validated['status'] ?? null) === 'resolved' && empty($exception->resolved_at)) {
            $validated['resolved_at'] = now();
        }

        $exception->update($validated);

        return response()->json($exception->fresh());
    }

    public function resolve(OrderException $exception)
    {
        $exception->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json($exception->fresh());
    }
}
