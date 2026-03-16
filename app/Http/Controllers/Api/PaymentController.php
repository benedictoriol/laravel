<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\Payment;
use App\Models\PlatformNotification;
use App\Services\AnalyticsAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['order', 'client', 'shop', 'confirmer']);
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
            abort(403, 'Only clients can submit payments.');
        }

        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'payment_type' => ['nullable', 'in:downpayment,partial,full,refund,adjustment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
            'payer_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $order = Order::with('shop')->findOrFail($validated['order_id']);

        if ((int) $order->client_user_id !== (int) $user->id) {
            abort(403, 'You can only pay for your own orders.');
        }

        $payment = DB::transaction(function () use ($validated, $order, $user) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'client_user_id' => $user->id,
                'shop_id' => $order->shop_id,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'payment_type' => $validated['payment_type'] ?? 'partial',
                'amount' => $validated['amount'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payer_name' => $validated['payer_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'payment_status' => 'submitted',
                'paid_at' => now(),
            ]);

            PlatformNotification::create([
                'user_id' => $order->shop->owner_user_id,
                'type' => 'payment_submitted',
                'title' => 'Payment submitted',
                'message' => 'A payment was submitted for order '.$order->order_number.'.',
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'channel' => 'web',
            ]);

            return $payment;
        });

        return response()->json($payment->load(['order', 'client', 'shop']), 201);
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && ($user->shop_id !== $payment->shop_id || ! in_array($user->role, ['owner', 'hr'], true))) {
            abort(403, 'Unauthorized payment confirmation.');
        }

        DB::transaction(function () use ($payment, $user) {
            $payment->update([
                'payment_status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
                'rejection_reason' => null,
            ]);

            $order = $payment->order()->lockForUpdate()->first();

            OrderProgressLog::create([
                'order_id' => $payment->order_id,
                'status' => 'payment_verified',
                'title' => 'Payment verified',
                'description' => 'Payment was confirmed and production workflow is ready.',
                'actor_user_id' => $user->id,
            ]);

            if ($order && ! OrderStageHistory::where('order_id', $order->id)->where('stage_code', 'digitizing')->exists()) {
                OrderStageHistory::create([
                    'order_id' => $order->id,
                    'stage_code' => 'digitizing',
                    'stage_status' => 'active',
                    'started_at' => now(),
                    'actor_user_id' => $user->id,
                    'notes' => 'Production started automatically after payment confirmation.',
                ]);

                $order->update([
                    'status' => 'in_production',
                    'current_stage' => 'digitizing',
                ]);

                OrderProgressLog::create([
                    'order_id' => $order->id,
                    'status' => 'digitizing',
                    'title' => 'Production started',
                    'description' => 'Digitizing stage started automatically after payment confirmation.',
                    'actor_user_id' => $user->id,
                ]);
            }

            $confirmedTotal = (float) $order->payments()->where('payment_status', 'confirmed')->sum('amount');
            $paymentStatus = $confirmedTotal >= (float) $order->total_amount
                ? 'paid'
                : ($confirmedTotal > 0 ? 'partial' : 'unpaid');

            $order->update([
                'payment_status' => $paymentStatus,
            ]);

            PlatformNotification::create([
                'user_id' => $payment->client_user_id,
                'type' => 'payment_confirmed',
                'title' => 'Payment confirmed',
                'message' => 'Your payment for order '.$order->order_number.' has been confirmed.',
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'channel' => 'web',
            ]);
        });

        app(AnalyticsAutomationService::class)->refreshForOrder($payment->order()->first(), 'payment_confirmed');


        return response()->json($payment->fresh(['order', 'client', 'shop', 'confirmer']));
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && ($user->shop_id !== $payment->shop_id || ! in_array($user->role, ['owner', 'hr'], true))) {
            abort(403, 'Unauthorized payment rejection.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $payment->update([
            'payment_status' => 'rejected',
            'confirmed_by' => $user->id,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        PlatformNotification::create([
            'user_id' => $payment->client_user_id,
            'type' => 'payment_rejected',
            'title' => 'Payment rejected',
            'message' => 'A payment for your order was rejected. Reason: '.$validated['rejection_reason'],
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
            'channel' => 'web',
        ]);

        app(AnalyticsAutomationService::class)->refreshForOrder($payment->order()->first(), 'payment_rejected');

        return response()->json($payment->fresh(['order', 'client', 'shop', 'confirmer']));
    }
}