<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => true,
            'canRegister' => true,
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'nullable|string|max:30',
            'role' => 'nullable|in:client,owner',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->input('role', 'client'),
            'is_active' => true,
            'password' => Hash::make($request->password),
        ]);

        if ($user->role === 'client') {
            ClientProfile::firstOrCreate(['user_id' => $user->id]);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $user->tokens()->delete();
        $token = $user->createToken('spa')->plainTextToken;
        $redirectPath = $user->role === 'owner' ? '/owner-dashboard' : '/client-dashboard';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'token' => $token,
                'user' => $user,
                'redirect_role' => $user->role,
                'redirect_path' => $redirectPath,
            ], 201);
        }

        return redirect($redirectPath);
    }
}
