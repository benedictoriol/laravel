<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopServiceController extends Controller
{
    private const ALLOWED_CATEGORIES = [
        'logo_embroidery',
        'name_embroidery',
        'patch_embroidery',
        'uniform_embroidery',
        'cap_embroidery',
        'custom_design_embroidery',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = ShopService::query()->with('shop');
        $user = $request->user();

        if ($user && $user->role !== 'admin') {
            $query->where('shop_id', $user->shop_id ?? 0);
        }

        return response()->json($query->latest('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'service_name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(self::ALLOWED_CATEGORIES)],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', 'string'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'stitch_range' => ['nullable', 'string', 'max:50'],
            'complexity_multiplier' => ['nullable', 'numeric', 'min:0'],
            'rush_fee_allowed' => ['nullable', 'boolean'],
            'turnaround_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $service = ShopService::create([
            'shop_id' => $validated['shop_id'],
            'service_name' => $validated['service_name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'base_price' => $validated['base_price'] ?? 0,
            'price_type' => $validated['price_type'] ?? 'quoted',
            'min_order_qty' => $validated['min_order_qty'] ?? 1,
            'unit_price' => $validated['unit_price'] ?? ($validated['base_price'] ?? 0),
            'stitch_range' => $validated['stitch_range'] ?? null,
            'complexity_multiplier' => $validated['complexity_multiplier'] ?? 1,
            'rush_fee_allowed' => $validated['rush_fee_allowed'] ?? true,
            'turnaround_days' => $validated['turnaround_days'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($service->load('shop'), 201);
    }

    public function update(Request $request, ShopService $shopService): JsonResponse
    {
        $validated = $request->validate([
            'service_name' => ['sometimes', 'required', 'string', 'max:150'],
            'category' => ['sometimes', 'required', 'string', Rule::in(self::ALLOWED_CATEGORIES)],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['nullable', 'string'],
            'min_order_qty' => ['nullable', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'stitch_range' => ['nullable', 'string', 'max:50'],
            'complexity_multiplier' => ['nullable', 'numeric', 'min:0'],
            'rush_fee_allowed' => ['nullable', 'boolean'],
            'turnaround_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $shopService->update($validated);
        return response()->json($shopService->fresh('shop'));
    }

    public function destroy(ShopService $shopService): JsonResponse
    {
        $shopService->delete();
        return response()->json(['message' => 'Shop service deleted.']);
    }
}
