<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderProgressLog;
use App\Models\PlatformNotification;
use App\Models\ShopProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ShopProject::with(['shop', 'creator'])->where('is_active', true)->latest('id');
        if ($request->filled('shop_id')) {
            $query->where('shop_id', (int) $request->input('shop_id'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', (float) $request->input('max_price'));
        }
        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isOwner() || $user->isHr(), 403);
        abort_unless($user->shop_id, 422, 'Shop context is required.');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'turnaround_days' => ['nullable', 'integer', 'min:1'],
            'is_customizable' => ['nullable', 'boolean'],
            'preview_image_path' => ['nullable', 'string', 'max:255'],
            'default_fulfillment_type' => ['nullable', 'in:pickup,delivery'],
            'automation_profile_json' => ['nullable', 'array'],
            'tags_json' => ['nullable', 'array'],
        ]);
        $project = ShopProject::create(array_merge($validated, [
            'shop_id' => $user->shop_id,
            'created_by' => $user->id,
            'is_active' => true,
        ]));
        return response()->json($project->load(['shop', 'creator']), 201);
    }

    public function update(Request $request, ShopProject $shopProject): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->shop_id && $user->shop_id === $shopProject->shop_id && ($user->isOwner() || $user->isHr())), 403);
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'required', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'turnaround_days' => ['nullable', 'integer', 'min:1'],
            'is_customizable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'preview_image_path' => ['nullable', 'string', 'max:255'],
            'default_fulfillment_type' => ['nullable', 'in:pickup,delivery'],
            'automation_profile_json' => ['nullable', 'array'],
            'tags_json' => ['nullable', 'array'],
        ]);
        $shopProject->update($validated);
        return response()->json($shopProject->fresh(['shop', 'creator']));
    }

    public function order(Request $request, ShopProject $shopProject): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isClient(), 403);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'fulfillment_type' => ['nullable', 'in:pickup,delivery'],
            'customer_notes' => ['nullable', 'string'],
            'delivery_address' => ['nullable', 'string'],
            'customization_notes' => ['nullable', 'string'],
        ]);

        $order = DB::transaction(function () use ($validated, $user, $shopProject) {
            $profile = ClientProfile::firstOrCreate(['user_id' => $user->id]);
            $quantity = max((int) $validated['quantity'], (int) ($shopProject->min_order_qty ?? 1));
            $subtotal = round((float) $shopProject->base_price * $quantity, 2);
            $fulfillmentType = $validated['fulfillment_type'] ?? ($profile->preferred_fulfillment_type ?: $shopProject->default_fulfillment_type ?: 'pickup');
            $deliveryAddress = $validated['delivery_address'] ?? ($fulfillmentType === 'delivery' ? $profile->default_address : null);
            $order = Order::create([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                'client_user_id' => $user->id,
                'shop_id' => $shopProject->shop_id,
                'order_type' => 'direct_order',
                'status' => 'pending',
                'current_stage' => 'intake',
                'payment_status' => 'unpaid',
                'fulfillment_type' => $fulfillmentType,
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'due_date' => now()->addDays((int) ($shopProject->turnaround_days ?? 7))->toDateString(),
                'delivery_address' => $deliveryAddress,
                'customer_notes' => $validated['customer_notes'] ?? null,
                'internal_notes' => 'Created from shop project #'.$shopProject->id,
            ]);
            OrderItem::create([
                'order_id' => $order->id,
                'item_name' => $shopProject->title,
                'garment_type' => $shopProject->category,
                'quantity' => $quantity,
                'unit_price' => $shopProject->base_price,
                'line_total' => $subtotal,
                'customization_notes' => $validated['customization_notes'] ?? null,
            ]);
            OrderProgressLog::create([
                'order_id' => $order->id,
                'status' => 'project_order_created',
                'title' => 'Order created from shop project',
                'description' => 'Client ordered '.$shopProject->title.' from shop catalog.',
                'actor_user_id' => $user->id,
            ]);
            PlatformNotification::create([
                'user_id' => $shopProject->shop->owner_user_id,
                'type' => 'shop_project_ordered',
                'title' => 'Catalog project ordered',
                'message' => 'A client ordered “'.$shopProject->title.'”.',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'channel' => 'web',
            ]);
            return $order;
        });

        return response()->json($order->load('items'), 201);
    }
}
