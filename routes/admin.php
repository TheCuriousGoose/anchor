<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminBoardController;
use App\Http\Controllers\Admin\AdminMetricsController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminMetricsController::class, 'index'])->name('metrics');

    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::patch('users/{user}/suspension', [AdminUserController::class, 'updateSuspension'])->name('users.suspension');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('boards', [AdminBoardController::class, 'index'])->name('boards.index');
    Route::get('boards/{board}', [AdminBoardController::class, 'show'])->name('boards.show');

    Route::get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
});
