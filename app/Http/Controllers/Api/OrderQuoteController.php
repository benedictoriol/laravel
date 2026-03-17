<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OrderQuoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\ProductionOrchestrationService;

class OrderQuoteController extends Controller
{
    public function __construct(protected ProductionOrchestrationService $production) {}

    public function index(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $this->canViewOrderQuotes($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $quotes = OrderQuote::query()
            ->where('order_id', $order->id)
            ->with('items')
            ->latest('id')
            ->get();

        return response()->json($quotes);
    }

    public function show(Request $request, Order $order, OrderQuote $quote)
    {
        $user = $request->user();

        if ($quote->order_id !== $order->id) {
            abort(404, 'Quote does not belong to this order.');
        }

        if (! $this->canViewOrderQuotes($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        return response()->json($quote->load('items'));
    }

    public function store(Request $request, Order $order)
    {
        $user = $request->user();

        if (! $this->canManageOrderQuotes($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'valid_until' => ['nullable', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'digitizing_fee' => ['nullable', 'numeric', 'min:0'],
            'material_fee' => ['nullable', 'numeric', 'min:0'],
            'labor_fee' => ['nullable', 'numeric', 'min:0'],
            'rush_fee' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'terms_and_notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.line_label' => ['required_with:items', 'string', 'max:180'],
            'items.*.line_type' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.order_item_id' => ['nullable', 'integer'],
        ]);

        $quote = DB::transaction(function () use ($validated, $order, $user) {
            $latestVersion = (int) OrderQuote::where('order_id', $order->id)->max('version_no');
            $version = $latestVersion + 1;

            // Supersede older draft/sent quotes if needed
            OrderQuote::where('order_id', $order->id)
                ->whereIn('status', ['draft', 'sent'])
                ->update(['status' => 'superseded']);

            $quote = OrderQuote::create([
                'order_id' => $order->id,
                'shop_id' => $order->shop_id,
                'quoted_by' => $user->id,
                'quote_number' => $this->generateQuoteNumber($order->id, $version),
                'version_no' => $version,
                'status' => 'sent',
                'valid_until' => $validated['valid_until'] ?? null,
                'subtotal' => $validated['subtotal'],
                'digitizing_fee' => $validated['digitizing_fee'] ?? 0,
                'material_fee' => $validated['material_fee'] ?? 0,
                'labor_fee' => $validated['labor_fee'] ?? 0,
                'rush_fee' => $validated['rush_fee'] ?? 0,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'terms_and_notes' => $validated['terms_and_notes'] ?? null,
            ]);

            foreach ($validated['items'] ?? [] as $item) {
                OrderQuoteItem::create([
                    'order_quote_id' => $quote->id,
                    'order_item_id' => $item['order_item_id'] ?? null,
                    'line_label' => $item['line_label'],
                    'line_type' => $item['line_type'] ?? 'item',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'line_total' => $item['line_total'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $order->update([
                'status' => 'quoted',
                'latest_quote_id' => $quote->id,
                'quoted_at' => now(),
            ]);

            return $quote->load('items');
        });

        return response()->json([
            'message' => 'Quote created successfully.',
            'quote' => $quote,
        ], 201);
    }

    public function update(Request $request, Order $order, OrderQuote $quote)
    {
        $user = $request->user();

        if ($quote->order_id !== $order->id) {
            abort(404, 'Quote does not belong to this order.');
        }

        if (! $this->canManageOrderQuotes($user, $order)) {
            abort(403, 'Unauthorized.');
        }

        if (in_array($quote->status, ['accepted', 'rejected'], true)) {
            return response()->json([
                'message' => 'Accepted or rejected quotes can no longer be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'valid_until' => ['nullable', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'digitizing_fee' => ['nullable', 'numeric', 'min:0'],
            'material_fee' => ['nullable', 'numeric', 'min:0'],
            'labor_fee' => ['nullable', 'numeric', 'min:0'],
            'rush_fee' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'terms_and_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,sent,expired,superseded'],
            'items' => ['nullable', 'array'],
            'items.*.line_label' => ['required_with:items', 'string', 'max:180'],
            'items.*.line_type' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.order_item_id' => ['nullable', 'integer'],
        ]);

        $quote = DB::transaction(function () use ($validated, $quote) {
            $quote->update([
                'valid_until' => $validated['valid_until'] ?? null,
                'subtotal' => $validated['subtotal'],
                'digitizing_fee' => $validated['digitizing_fee'] ?? 0,
                'material_fee' => $validated['material_fee'] ?? 0,
                'labor_fee' => $validated['labor_fee'] ?? 0,
                'rush_fee' => $validated['rush_fee'] ?? 0,
                'shipping_fee' => $validated['shipping_fee'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'terms_and_notes' => $validated['terms_and_notes'] ?? null,
                'status' => $validated['status'] ?? $quote->status,
            ]);

            if (array_key_exists('items', $validated)) {
                OrderQuoteItem::where('order_quote_id', $quote->id)->delete();

                foreach ($validated['items'] ?? [] as $item) {
                    OrderQuoteItem::create([
                        'order_quote_id' => $quote->id,
                        'order_item_id' => $item['order_item_id'] ?? null,
                        'line_label' => $item['line_label'],
                        'line_type' => $item['line_type'] ?? 'item',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit' => $item['unit'] ?? null,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'line_total' => $item['line_total'] ?? 0,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            return $quote->fresh()->load('items');
        });

        return response()->json([
            'message' => 'Quote updated successfully.',
            'quote' => $quote,
        ]);
    }

    public function accept(Request $request, Order $order, OrderQuote $quote)
    {
        $user = $request->user();

        if ($quote->order_id !== $order->id) {
            abort(404, 'Quote does not belong to this order.');
        }

        if ((int) $order->client_user_id !== (int) $user->id) {
            abort(403, 'Only the client can accept this quote.');
        }

        if (! in_array($quote->status, ['sent', 'draft'], true)) {
            return response()->json([
                'message' => 'Only a draft or sent quote can be accepted.',
            ], 422);
        }

        DB::transaction(function () use ($quote, $order, $user) {
            OrderQuote::where('order_id', $order->id)
                ->where('id', '!=', $quote->id)
                ->whereIn('status', ['draft', 'sent'])
                ->update(['status' => 'superseded']);

            $quote->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            $order->update([
                'status' => 'approved',
                'approved_quote_id' => $quote->id,
                'latest_quote_id' => $quote->id,
                'approved_at' => now(),
                'current_stage' => 'payment_waiting',
                'payment_due_date' => now()->addDays(2),
            ]);

            $paymentAmount = round(((float) $quote->total_amount) * 0.5, 2);
            if (! $order->payments()->where('payment_type', 'downpayment')->whereIn('payment_status', ['pending', 'submitted', 'confirmed'])->exists()) {
                $order->payments()->create([
                    'client_user_id' => $order->client_user_id,
                    'shop_id' => $order->shop_id,
                    'payment_type' => 'downpayment',
                    'method' => 'manual',
                    'amount' => $paymentAmount,
                    'payment_status' => 'pending',
                    'notes' => 'Auto-created after quote acceptance.',
                ]);
            }

            PlatformNotification::create([
                'user_id' => $order->client_user_id,
                'type' => 'quote_accepted',
                'category' => 'quotes',
                'priority' => 'medium',
                'title' => 'Quote accepted',
                'message' => 'You accepted quote '.$quote->quote_number.'. Please complete the required payment.',
                'action_label' => 'View payment',
                'reference_type' => 'order_quote',
                'reference_id' => $quote->id,
                'channel' => 'web',
            ]);

            PlatformNotification::create([
                'user_id' => optional($order->shop)->owner_user_id,
                'type' => 'quote_accepted',
                'category' => 'quotes',
                'priority' => 'medium',
                'title' => 'Client accepted quote',
                'message' => 'Order '.$order->order_number.' is ready for payment follow-up.',
                'action_label' => 'Open order',
                'reference_type' => 'order_quote',
                'reference_id' => $quote->id,
                'channel' => 'web',
            ]);
        });

        return response()->json([
            'message' => 'Quote accepted successfully.',
            'quote' => $quote->fresh()->load('items'),
        ]);
    }

    public function reject(Request $request, Order $order, OrderQuote $quote)
    {
        $user = $request->user();

        if ($quote->order_id !== $order->id) {
            abort(404, 'Quote does not belong to this order.');
        }

        if ((int) $order->client_user_id !== (int) $user->id) {
            abort(403, 'Only the client can reject this quote.');
        }

        if (! in_array($quote->status, ['sent', 'draft'], true)) {
            return response()->json([
                'message' => 'Only a draft or sent quote can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'client_response_notes' => ['nullable', 'string'],
        ]);

        $quote->update([
            'status' => 'rejected',
            'client_response_notes' => $validated['client_response_notes'] ?? null,
            'responded_at' => now(),
        ]);

        $order->update(['status' => 'quoted']);
        $this->production->routeException($order, 'quote_rejected', 'Client requested quote revision. '.($validated['client_response_notes'] ?? ''), 'medium');

        PlatformNotification::create([
            'user_id' => optional($order->shop)->owner_user_id,
            'type' => 'quote_rejected',
            'category' => 'quotes',
            'priority' => 'high',
            'title' => 'Quote needs revision',
            'message' => 'Client rejected quote '.$quote->quote_number.'.',
            'action_label' => 'Revise quote',
            'reference_type' => 'order_quote',
            'reference_id' => $quote->id,
            'channel' => 'web',
        ]);

        return response()->json([
            'message' => 'Quote rejected.',
            'quote' => $quote->fresh()->load('items'),
        ]);
    }

    protected function canViewOrderQuotes($user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'client' && (int) $order->client_user_id === (int) $user->id) {
            return true;
        }

        if (in_array($user->role, ['owner', 'hr', 'staff'], true) && (int) $order->shop_id === (int) $user->shop_id) {
            return true;
        }

        return false;
    }

    protected function canManageOrderQuotes($user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if (in_array($user->role, ['owner', 'hr'], true) && (int) $order->shop_id === (int) $user->shop_id) {
            return true;
        }

        return false;
    }

    protected function generateQuoteNumber(int $orderId, int $version): string
    {
        return 'QT-' . now()->format('Ymd') . '-' . $orderId . '-V' . $version . '-' . Str::upper(Str::random(4));
    }
}