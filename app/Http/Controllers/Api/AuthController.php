<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', Rule::in(['client', 'owner'])],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ];

        if (Schema::hasColumn('users', 'phone')) {
            $payload['phone'] = $validated['phone'] ?? null;
        }
        if (Schema::hasColumn('users', 'role')) {
            $payload['role'] = $validated['role'] ?? 'client';
        }
        if (Schema::hasColumn('users', 'is_active')) {
            $payload['is_active'] = true;
        }

        $user = User::create($payload);
        $role = Schema::hasColumn('users', 'role') ? ($user->role ?: 'client') : 'client';

        if ($role === 'client') {
            ClientProfile::firstOrCreate(['user_id' => $user->id]);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
            'redirect_role' => $role,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if (Schema::hasColumn('users', 'is_active') && ! $user->is_active) {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        if (Schema::hasColumn('users', 'last_login_at')) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        $user->tokens()->delete();
        $token = $user->createToken('spa')->plainTextToken;
        $role = Schema::hasColumn('users', 'role') ? ($user->role ?: 'client') : 'client';

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
            'redirect_role' => $role,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->loadMissing('shop'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    protected function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => Schema::hasColumn('users', 'role') ? ($user->role ?: 'client') : 'client',
            'shop_id' => Schema::hasColumn('users', 'shop_id') ? $user->shop_id : null,
            'phone' => Schema::hasColumn('users', 'phone') ? $user->phone : null,
            'is_active' => Schema::hasColumn('users', 'is_active') ? (bool) $user->is_active : true,
        ];
    }
}
