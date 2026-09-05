<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest endpoints
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1');
Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:6,1');

// Signed link opened from the verification e-mail; redirects back to the SPA.
Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Authenticated endpoints (Sanctum bearer token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::patch('user/profile', [ProfileController::class, 'update']);
    Route::delete('user', [ProfileController::class, 'destroy']);
    Route::put('user/password', [PasswordController::class, 'update']);

    Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1');

    Route::prefix('user/two-factor')->group(function () {
        Route::post('/', [TwoFactorController::class, 'enable']);
        Route::post('confirm', [TwoFactorController::class, 'confirm']);
        Route::delete('/', [TwoFactorController::class, 'disable']);
        Route::get('qr-code', [TwoFactorController::class, 'qrCode']);
        Route::get('secret-key', [TwoFactorController::class, 'secretKey']);
        Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
        Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
    });
});
