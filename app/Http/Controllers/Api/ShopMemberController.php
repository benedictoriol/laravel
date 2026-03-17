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
        $query = ShopMember::query()->with(['shop', 'user', 'creator', 'reviewer']);

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
        $actor = $request->user();
        abort_unless(in_array($actor->role, ['owner', 'hr', 'admin'], true), 403);

        $mode = $request->input('mode', 'create_account');

        if ($mode === 'promote_client') {
            $validated = $request->validate([
                'shop_id' => ['required', 'integer', 'exists:shops,id'],
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'member_role' => ['required', Rule::in(['hr', 'staff'])],
                'position' => ['nullable', 'string', 'max:120'],
            ]);

            $user = User::findOrFail($validated['user_id']);
            abort_if($user->shop_id && $user->shop_id !== (int) $validated['shop_id'], 422, 'This user already belongs to another shop.');

            $approvalStatus = $actor->role === 'owner' || $actor->role === 'admin' ? 'approved' : 'pending_owner_approval';
            if ($approvalStatus === 'approved') {
                $user->update([
                    'role' => $validated['member_role'],
                    'shop_id' => $validated['shop_id'],
                    'is_active' => true,
                ]);
            }

            $member = ShopMember::updateOrCreate(
                ['shop_id' => $validated['shop_id'], 'user_id' => $user->id],
                [
                    'member_role' => $validated['member_role'],
                    'position' => $validated['position'] ?? null,
                    'approval_status' => $approvalStatus,
                    'employment_status' => $approvalStatus === 'approved' ? 'active' : 'pending',
                    'created_by_user_id' => $actor->id,
                    'joined_at' => $approvalStatus === 'approved' ? now() : null,
                ]
            );

            return response()->json($member->load(['shop', 'user', 'creator', 'reviewer']), 201);
        }

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'member_role' => ['required', Rule::in(['hr', 'staff'])],
            'position' => ['nullable', 'string', 'max:120'],
        ]);

        $approvalStatus = $actor->role === 'owner' || $actor->role === 'admin' ? 'approved' : 'pending_owner_approval';
        $roleForUser = $approvalStatus === 'approved' ? $validated['member_role'] : 'client';
        $shopIdForUser = $approvalStatus === 'approved' ? $validated['shop_id'] : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $roleForUser,
            'shop_id' => $shopIdForUser,
            'is_active' => true,
        ]);

        $member = ShopMember::create([
            'shop_id' => $validated['shop_id'],
            'user_id' => $user->id,
            'member_role' => $validated['member_role'],
            'position' => $validated['position'] ?? null,
            'approval_status' => $approvalStatus,
            'employment_status' => $approvalStatus === 'approved' ? 'active' : 'pending',
            'created_by_user_id' => $actor->id,
            'joined_at' => $approvalStatus === 'approved' ? now() : null,
        ]);

        return response()->json($member->load(['shop', 'user', 'creator', 'reviewer']), 201);
    }

    public function update(Request $request, ShopMember $shopMember): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->role === 'admin' || ($actor->role === 'owner' && $actor->shop_id === $shopMember->shop_id), 403);

        $validated = $request->validate([
            'member_role' => ['nullable', Rule::in(['hr', 'staff'])],
            'position' => ['nullable', 'string', 'max:120'],
            'approval_status' => ['nullable', Rule::in(['approved', 'rejected', 'pending_owner_approval'])],
            'employment_status' => ['nullable', 'string', 'max:50'],
            'review_notes' => ['nullable', 'string'],
        ]);

        $shopMember->fill($validated);
        if (array_key_exists('approval_status', $validated)) {
            $shopMember->reviewed_by_user_id = $actor->id;
            $shopMember->reviewed_at = now();
        }
        $shopMember->save();

        if ($shopMember->user) {
            if (($validated['approval_status'] ?? null) === 'approved') {
                $shopMember->user->update([
                    'role' => $validated['member_role'] ?? $shopMember->member_role,
                    'shop_id' => $shopMember->shop_id,
                    'is_active' => true,
                ]);
            }
            if (($validated['approval_status'] ?? null) === 'rejected') {
                $shopMember->user->update([
                    'role' => 'client',
                    'shop_id' => null,
                ]);
            }
        }

        if (($validated['member_role'] ?? null) && $shopMember->approval_status === 'approved' && $shopMember->user) {
            $shopMember->user->update(['role' => $validated['member_role']]);
        }

        return response()->json($shopMember->fresh()->load(['shop', 'user', 'creator', 'reviewer']));
    }
}
