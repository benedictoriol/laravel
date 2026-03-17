<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Welcome');
    }

    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $user->tokens()->delete();
        $token = $user->createToken('spa')->plainTextToken;

        $redirect = match ($user->role) {
            'owner' => '/owner-dashboard',
            'client' => '/client-dashboard',
            default => '/dashboard',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful.',
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
            ]);
        }

        return redirect()->intended($redirect);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()?->tokens()?->delete();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
