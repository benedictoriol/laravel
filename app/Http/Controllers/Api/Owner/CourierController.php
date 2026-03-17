<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\ShopCourier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ShopCourier::query()->where('shop_id', $request->user()->shop_id)->latest('id')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'service_type' => ['required', 'in:delivery,pickup,both'],
            'coverage_area' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $courier = ShopCourier::create(array_merge($validated, [
            'shop_id' => $request->user()->shop_id,
            'is_active' => $validated['is_active'] ?? true,
        ]));

        return response()->json($courier, 201);
    }
}
