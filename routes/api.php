<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GenerationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\UsageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:auth')
        ->name('auth.register');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('usage', [UsageController::class, 'summary'])->name('usage.summary');

        Route::apiResource('projects', ProjectController::class);

        Route::get('generations', [GenerationController::class, 'index'])->name('generations.index');
        Route::post('projects/{project}/generations', [GenerationController::class, 'store'])
            ->middleware('throttle:generations')
            ->name('generations.store');
        Route::get('generations/{uuid}', [GenerationController::class, 'show'])->name('generations.show');
        Route::post('generations/{uuid}/cancel', [GenerationController::class, 'cancel'])->name('generations.cancel');

        Route::get('assets', [AssetController::class, 'index'])->name('assets.index');
        Route::get('assets/{uuid}/download', [AssetController::class, 'download'])->name('assets.download');
    });
});
