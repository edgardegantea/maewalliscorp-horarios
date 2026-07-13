<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('two-factor-qr-code', [TwoFactorAuthenticationController::class, 'qrCode'])->name('two-factor.qr-code');
    Route::post('two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])->name('two-factor.enable');
    Route::post('two-factor-authentication/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])->name('two-factor.disable');

    require __DIR__.'/admin.php';
    require __DIR__.'/docente.php';
});

require __DIR__.'/auth.php';
