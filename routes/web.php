<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Manager\BatchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
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

        Route::get('/manager/batches/create', [BatchController::class, 'create'])
            ->name('manager.batches.create');
        Route::post('/manager/batches', [BatchController::class, 'store'])
            ->name('manager.batches.store');
    });

    Route::middleware('role:system_admin')->group(function () {
        Route::get('/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');

        Route::get('/admin/users/create', [RegisteredUserController::class, 'create'])
            ->name('admin.users.create');
        Route::post('/admin/users', [RegisteredUserController::class, 'store'])
            ->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])
            ->name('admin.users.edit');
        Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])
            ->name('admin.users.update');
    });

    Route::middleware('role:shoe_constructor')->group(function () {
        Route::get('/constructor', [DashboardController::class, 'constructor'])->name('dashboard.constructor');
        Route::get('/constructor/inspections/{inspection}', [DashboardController::class, 'showConstructorInspection'])
            ->name('constructor.inspections.show');
        Route::patch('/constructor/inspections/{inspection}/resolve', [DashboardController::class, 'resolveRework'])
            ->name('constructor.inspections.resolve');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
