<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ShopMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = ShopMember::query()->with(['shop', 'user']);

        if ($user->role === 'admin') {
            return response()->json($query->latest('id')->get());
        }

        if ($user->shop_id) {
            $query->where('shop_id', $user->shop_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        return response()->json($query->latest('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'member_role' => ['required', Rule::in(['hr', 'staff'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['member_role'],
            'shop_id' => $validated['shop_id'],
            'is_active' => true,
        ]);

        $member = ShopMember::create([
            'shop_id' => $validated['shop_id'],
            'user_id' => $user->id,
            'member_role' => $validated['member_role'],
            'employment_status' => 'active',
        ]);

        return response()->json($member->load(['shop', 'user']), 201);
    }
}
