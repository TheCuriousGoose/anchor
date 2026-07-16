<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardShareController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WorkspaceController::class, 'home'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'dashboard'])->name('dashboard');
    Route::get('boards', [WorkspaceController::class, 'index'])->name('boards.index');
    Route::get('boards/{board}', [WorkspaceController::class, 'show'])->name('boards.show');

    Route::post('boards/import', [BoardController::class, 'import'])->name('boards.import');
    Route::post('boards', [BoardController::class, 'store'])->name('boards.store');
    Route::patch('boards/{board}', [BoardController::class, 'update'])->name('boards.update');
    Route::delete('boards/{board}', [BoardController::class, 'destroy'])->name('boards.destroy');
    Route::post('boards/{board}/tasks', [BoardController::class, 'storeTask'])->name('tasks.store');
    Route::patch('boards/{board}/tasks/reorder', [BoardController::class, 'reorderTasks'])->name('tasks.reorder');
    Route::patch('tasks/{task}', [BoardController::class, 'updateTask'])->name('tasks.update');
    Route::delete('tasks/{task}', [BoardController::class, 'destroyTask'])->name('tasks.destroy');
    Route::post('tasks/{task}/images', [BoardController::class, 'storeTaskImage'])->name('tasks.images.store');

    Route::post('boards/{board}/share', [BoardShareController::class, 'store'])->name('boards.share.store');
    Route::patch('boards/{board}/share/{user}', [BoardShareController::class, 'update'])->name('boards.share.update');
    Route::delete('boards/{board}/share/{user}', [BoardShareController::class, 'destroy'])->name('boards.share.destroy');

    Route::post('boards/{board}/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::patch('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('notes/{note}/images', [NoteController::class, 'storeImage'])->name('notes.images.store');

    Route::post('boards/{board}/labels', [LabelController::class, 'store'])->name('labels.store');
    Route::patch('labels/{label}', [LabelController::class, 'update'])->name('labels.update');
    Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
});

require __DIR__.'/settings.php';
