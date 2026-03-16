<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Shop::query()->with(['owner', 'approver']);

        if ($user && $user->role !== 'admin') {
            if ($user->role === 'owner') {
                $query->where('owner_user_id', $user->id);
            } elseif ($user->role === 'client') {
                $query->where('verification_status', 'approved')->where('is_active', true);
            } else {
                $query->where('id', $user->shop_id ?? 0);
            }
        }

        return response()->json($query->latest('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'cavite_location_id' => ['required', 'integer', 'exists:cavite_locations,id'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'service_radius_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();

        $shop = Shop::create([
            'owner_user_id' => $user->id,
            'cavite_location_id' => $validated['cavite_location_id'],
            'shop_name' => $validated['name'],
            'slug' => Str::slug($validated['name'].'-'.Str::lower(Str::random(6))),
            'description' => $validated['description'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'service_radius_km' => $validated['service_radius_km'] ?? null,
            'verification_status' => 'pending',
            'is_active' => true,
        ]);

        if (! $user->shop_id) {
            $user->forceFill(['shop_id' => $shop->id])->save();
        }

        ShopMember::firstOrCreate(
            ['shop_id' => $shop->id, 'user_id' => $user->id],
            ['member_role' => 'owner', 'employment_status' => 'active']
        );

        return response()->json([
            'message' => 'Shop created.',
            'shop' => $shop->fresh(['owner', 'approver']),
        ], 201);
    }

    public function show(Shop $shop): JsonResponse
    {
        return response()->json($shop->load(['owner', 'approver', 'members.user', 'services']));
    }

    public function update(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'cavite_location_id' => ['nullable', 'integer', 'exists:cavite_locations,id'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'service_radius_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updates = [
            'shop_name' => $validated['name'] ?? $shop->shop_name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $shop->description,
            'cavite_location_id' => $validated['cavite_location_id'] ?? $shop->cavite_location_id,
            'email' => $validated['email'] ?? $shop->email,
            'phone' => $validated['phone'] ?? $shop->phone,
            'address_line' => $validated['address_line'] ?? $shop->address_line,
            'postal_code' => $validated['postal_code'] ?? $shop->postal_code,
            'service_radius_km' => $validated['service_radius_km'] ?? $shop->service_radius_km,
        ];

        if (isset($validated['name'])) {
            $updates['slug'] = Str::slug($validated['name'].'-'.$shop->id);
        }

        $shop->update($updates);

        return response()->json($shop->fresh(['owner', 'approver']));
    }

    public function approve(Request $request, Shop $shop): JsonResponse
    {
        $shop->update([
            'verification_status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_notes' => null,
        ]);

        return response()->json([
            'message' => 'Shop approved.',
            'shop' => $shop->fresh(['owner', 'approver']),
        ]);
    }

    public function reject(Request $request, Shop $shop): JsonResponse
    {
        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string'],
        ]);

        $shop->update([
            'verification_status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Shop rejected.',
            'shop' => $shop->fresh(['owner', 'approver']),
        ]);
    }
}
