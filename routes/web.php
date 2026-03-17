<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => true,
        'canRegister' => true,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();
        if ($user?->role === 'client') {
            return redirect()->route('client.dashboard');
        }
        if ($user?->role === 'owner') {
            return redirect()->route('owner.dashboard');
        }

        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/client-dashboard', function () {
        return Inertia::render('Client/Workspace');
    })->name('client.dashboard');

    Route::get('/owner-dashboard', function () {
        return Inertia::render('Owner/Workspace');
    })->name('owner.dashboard');


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
