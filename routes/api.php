<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\NewPasswordController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store']);
Route::post('forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('reset-password', [NewPasswordController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', MeController::class);
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::put('password', [PasswordController::class, 'update']);
    Route::get('dashboard', DashboardController::class);
});
