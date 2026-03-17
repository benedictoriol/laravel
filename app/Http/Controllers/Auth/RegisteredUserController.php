<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Welcome');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', Rule::in(['client', 'owner'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? 'client',
            'is_active' => true,
        ]);

        if ($user->role === 'client') {
            ClientProfile::firstOrCreate(['user_id' => $user->id]);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $token = $user->createToken('spa')->plainTextToken;
        $redirect = $user->role === 'owner' ? '/owner-dashboard' : '/client-dashboard';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Account created successfully.',
                'token' => $token,
                'redirect_role' => $user->role,
                'redirect' => $redirect,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'shop_id' => $user->shop_id,
                    'phone' => $user->phone,
                    'is_active' => $user->is_active,
                ],
            ], 201);
        }

        return redirect($redirect);
    }
}
