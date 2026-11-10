<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'landing'])->name('landing');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [SessionController::class, 'create'])->name('login');
    Route::post('login', [SessionController::class, 'store'])->name('login.store');
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/generate', [DashboardController::class, 'store'])
        ->middleware('throttle:generations')
        ->name('dashboard.generate');

    Route::get('studio', [PageController::class, 'studio'])->name('studio');
    Route::get('history', [PageController::class, 'history'])->name('history');
    Route::get('library', [PageController::class, 'library'])->name('library');
    Route::get('projects', [PageController::class, 'projects'])->name('projects');
    Route::get('usage', [PageController::class, 'usage'])->name('usage');

    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('media/{uuid}', [MediaController::class, 'show'])->name('media.show');

    Route::post('logout', [SessionController::class, 'destroy'])->name('logout');
});
