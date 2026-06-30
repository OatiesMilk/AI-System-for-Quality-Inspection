<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:quality_inspector')->group(function () {
        Route::get('/inspector', [DashboardController::class, 'inspector'])->name('dashboard.inspector');
        Route::get('/inspector/inspections/{inspection}', [DashboardController::class, 'showInspection'])->name('inspector.inspections.show');
        Route::patch('/inspector/inspections/{inspection}', [DashboardController::class, 'updateInspection'])->name('inspector.inspections.update');
    });

    Route::middleware('role:product_manager')->group(function () {
        Route::get('/manager', [DashboardController::class, 'manager'])->name('dashboard.manager');
    });

    Route::middleware('role:system_admin')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
    });

    Route::middleware('role:shoe_constructor')->group(function () {
        Route::get('/constructor', [DashboardController::class, 'constructor'])->name('dashboard.constructor');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
