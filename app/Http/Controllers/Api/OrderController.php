<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Fulfillment;
use App\Models\OrderException;
use App\Models\OrderItem;
use App\Models\OrderProgressLog;
use App\Models\OrderStageHistory;
use App\Models\PlatformNotification;
use App\Models\Shop;
use App\Services\AnalyticsAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->with(['client', 'shop', 'service', 'items', 'fulfillment']);
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
            abort(403, 'Only clients can create orders.');
        }

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'service_id' => ['nullable', 'integer', 'exists:shop_services,id'],
            'source_design_post_id' => ['nullable', 'integer', 'exists:design_posts,id'],
            'order_type' => ['nullable', 'in:direct_order,custom_order,marketplace_job'],
            'fulfillment_type' => ['nullable', 'in:pickup,delivery'],
            'customer_notes' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'customization_fee' => ['nullable', 'numeric', 'min:0'],
            'rush_fee' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:180'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.garment_type' => ['nullable', 'string', 'max:100'],
            'items.*.size_label' => ['nullable', 'string', 'max:50'],
            'items.*.fabric_type' => ['nullable', 'string', 'max:100'],
            'items.*.placement_area' => ['nullable', 'string', 'max:100'],
            'items.*.placement_notes' => ['nullable', 'string', 'max:255'],
            'items.*.embroidery_type' => ['nullable', 'in:flat,3d_puff,patch,applique,digitized,other'],
            'items.*.backing_type' => ['nullable', 'string', 'max:100'],
            'items.*.width_mm' => ['nullable', 'numeric', 'min:0'],
            'items.*.height_mm' => ['nullable', 'numeric', 'min:0'],
            'items.*.stitch_count' => ['nullable', 'integer', 'min:0'],
            'items.*.thread_colors' => ['nullable', 'integer', 'min:0'],
            'items.*.color_notes' => ['nullable', 'string', 'max:255'],
            'items.*.customization_notes' => ['nullable', 'string'],
        ]);

        $shop = Shop::with('owner')->findOrFail($validated['shop_id']);
        if ($shop->verification_status !== 'approved') {
            abort(422, 'Orders can only be sent to approved shops.');
        }

        $customizationFee = (float) ($validated['customization_fee'] ?? 0);
        $rushFee = (float) ($validated['rush_fee'] ?? 0);
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);

        $order = DB::transaction(function () use ($validated, $user, $shop, $customizationFee, $rushFee, $discountAmount) {
            $subtotal = 0;
            $itemsPayload = [];
            foreach ($validated['items'] ?? [] as $item) {
                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;
                $itemsPayload[] = [
                    'item_name' => $item['item_name'],
                    'garment_type' => $item['garment_type'] ?? null,
                    'size_label' => $item['size_label'] ?? null,
                    'fabric_type' => $item['fabric_type'] ?? null,
                    'placement_area' => $item['placement_area'] ?? null,
                    'placement_notes' => $item['placement_notes'] ?? null,
                    'embroidery_type' => $item['embroidery_type'] ?? 'flat',
                    'backing_type' => $item['backing_type'] ?? null,
                    'width_mm' => $item['width_mm'] ?? null,
                    'height_mm' => $item['height_mm'] ?? null,
                    'stitch_count' => $item['stitch_count'] ?? null,
                    'thread_colors' => $item['thread_colors'] ?? null,
                    'color_notes' => $item['color_notes'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'customization_notes' => $item['customization_notes'] ?? null,
                    'mockup_approved' => false,
                ];
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'client_user_id' => $user->id,
                'shop_id' => $validated['shop_id'],
                'source_design_post_id' => $validated['source_design_post_id'] ?? null,
                'service_id' => $validated['service_id'] ?? null,
                'order_type' => $validated['order_type'] ?? (($validated['source_design_post_id'] ?? null) ? 'marketplace_job' : 'custom_order'),
                'status' => 'pending',
                'current_stage' => 'intake',
                'payment_status' => 'unpaid',
                'fulfillment_type' => $validated['fulfillment_type'] ?? 'pickup',
                'subtotal' => $subtotal,
                'customization_fee' => $customizationFee,
                'rush_fee' => $rushFee,
                'discount_amount' => $discountAmount,
                'total_amount' => max(0, $subtotal + $customizationFee + $rushFee - $discountAmount),
                'due_date' => $validated['due_date'] ?? null,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
            ]);

            foreach ($itemsPayload as $itemPayload) {
                $order->items()->create($itemPayload);
            }

            Fulfillment::create([
                'order_id' => $order->id,
                'fulfillment_type' => $order->fulfillment_type,
                'delivery_address' => $order->delivery_address,
                'status' => 'pending',
            ]);

            OrderStageHistory::create([
                'order_id' => $order->id,
                'stage_code' => 'intake',
                'stage_status' => 'active',
                'started_at' => now(),
                'actor_user_id' => $user->id,
                'notes' => 'Order created by client.',
            ]);

            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'title' => 'Order created',
                'description' => 'Client submitted a new order.',
                'actor_user_id' => $user->id,
            ]);

            if ($shop->owner_user_id) {
                PlatformNotification::create([
                    'user_id' => $shop->owner_user_id,
                    'type' => 'order_created',
                    'title' => 'New order received',
                    'message' => 'A new order '.$order->order_number.' was submitted.',
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'channel' => 'web',
                ]);
            }

            return $order;
        });

        app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'order_created');

        return response()->json($order->load(['client', 'shop', 'service', 'items', 'fulfillment']), 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeAccess($request->user(), $order);

        return response()->json($order->load([
            'client',
            'shop',
            'service',
            'items',
            'payments',
            'progressLogs.actor',
            'stageHistory.actor',
            'revisions.requestedBy',
            'revisions.handledBy',
            'fulfillment',
        ]));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        $this->authorizeAccess($user, $order);

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,quoted,approved,in_production,ready_for_pickup,shipped,completed,cancelled,rejected'],
            'current_stage' => ['nullable', 'in:intake,quotation,payment_waiting,digitizing,mockup,client_approval,production,quality_check,packing,pickup_ready,shipping,delivered,completed,cancelled'],
            'payment_status' => ['nullable', 'in:unpaid,partial,paid,refunded'],
            'customer_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'cancelled_reason' => ['nullable', 'string'],
        ]);

        if ($user->isClient() && array_diff(array_keys($validated), ['customer_notes', 'delivery_address'])) {
            abort(403, 'Clients can only update their own notes and delivery address.');
        }

        DB::transaction(function () use ($validated, $order, $user) {
            $originalStatus = $order->status;
            $originalStage = $order->current_stage;

            $updates = $validated;
            if (($validated['status'] ?? null) === 'completed' && empty($validated['completed_at'])) {
                $updates['completed_at'] = now();
            }
            if (($validated['status'] ?? null) === 'cancelled' && $order->cancelled_at === null) {
                $updates['cancelled_at'] = now();
            }

            $order->update($updates);

            if (($validated['status'] ?? null) && $validated['status'] !== $originalStatus) {
                OrderProgressLog::create([
                    'order_id' => $order->id,
                    'status' => $validated['status'],
                    'title' => 'Order status updated',
                    'description' => 'Status changed from '.$originalStatus.' to '.$validated['status'].'.',
                    'actor_user_id' => $user->id,
                ]);
            }

            if (($validated['current_stage'] ?? null) && $validated['current_stage'] !== $originalStage) {
                OrderStageHistory::create([
                    'order_id' => $order->id,
                    'stage_code' => $validated['current_stage'],
                    'stage_status' => 'active',
                    'started_at' => now(),
                    'actor_user_id' => $user->id,
                    'notes' => 'Stage changed from '.$originalStage.' to '.$validated['current_stage'].'.',
                ]);
            }

            PlatformNotification::create([
                'user_id' => $order->client_user_id,
                'type' => 'order_updated',
                'title' => 'Order updated',
                'message' => 'Your order '.$order->order_number.' has been updated.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        });

        app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'order_updated');

        return response()->json($order->fresh(['client', 'shop', 'service', 'items', 'payments']));
    }


    public function cancel(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! in_array($user->role, ['admin', 'owner', 'hr'], true)) {
            abort(403, 'Unauthorized.');
        }

        $this->authorizeAccess($user, $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($order, $user, $validated) {
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

            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'title' => 'Order cancelled',
                'description' => $validated['reason'],
                'actor_user_id' => $user->id,
            ]);

            if (class_exists(OrderException::class)) {
                OrderException::create([
                    'order_id' => $order->id,
                    'exception_type' => 'order_cancelled',
                    'severity' => 'medium',
                    'status' => 'resolved',
                    'notes' => $validated['reason'],
                    'resolved_at' => now(),
                ]);
            }

            PlatformNotification::create([
                'user_id' => $order->client_user_id,
                'type' => 'order_cancelled',
                'title' => 'Order cancelled',
                'message' => 'Your order '.$order->order_number.' has been cancelled. Reason: '.$validated['reason'],
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
        });

        app(AnalyticsAutomationService::class)->refreshForOrder($order->fresh(), 'order_cancelled');

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => $order->fresh(['client', 'shop', 'service', 'items', 'payments']),
        ]);
    }

    private function authorizeAccess($user, Order $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isClient() && $order->client_user_id === $user->id) {
            return;
        }

        if (! $user->isClient() && $user->shop_id && $order->shop_id === $user->shop_id) {
            return;
        }

        abort(403, 'Unauthorized order access.');
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
