<?php

use App\Http\Controllers\Tms\AuthController as TmsAuthController;
use App\Http\Controllers\Tms\BdeController as TmsBdeController;
use App\Http\Controllers\Tms\ReportController as TmsReportController;
use App\Http\Controllers\Tms\TaskController as TmsTaskController;
use Illuminate\Support\Facades\Route;

Route::redirect('/portfolio', 'https://tech4projects.online/')->name('portfolio');
Route::redirect('/', '/tms');

Route::prefix('tms')->name('tms.')->group(function (): void {
    Route::get('/', [TmsAuthController::class, 'create'])->name('login');
    Route::post('/login', [TmsAuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::post('/demo/{role}', [TmsAuthController::class, 'demo'])->middleware('throttle:login')->name('demo');

    Route::middleware('tms.auth')->group(function (): void {
        Route::post('/logout', [TmsAuthController::class, 'destroy'])->name('logout');
        Route::post('/bdes', [TmsBdeController::class, 'store'])->middleware('tms.role:AM')->name('bdes.store');
        Route::get('/verification', [TmsTaskController::class, 'verification'])->middleware('tms.role:GM,AM')->name('verification');
        Route::get('/reports', [TmsReportController::class, 'index'])->middleware('tms.role:GM,AM')->name('reports');
        Route::get('/reports/export', [TmsReportController::class, 'export'])->middleware('tms.role:GM,AM')->name('reports.export');
        Route::get('/tasks/create', [TmsTaskController::class, 'create'])->middleware('tms.role:GM,AM')->name('tasks.create');
        Route::resource('tasks', TmsTaskController::class)->only(['index', 'store', 'show', 'edit', 'update'])->parameters(['tasks' => 'task']);
        Route::post('/tasks/{task}/actions/{transition}', [TmsTaskController::class, 'transition'])->name('tasks.transition');
        Route::post('/tasks/{task}/comments', [TmsTaskController::class, 'comment'])->name('tasks.comments.store');
    });
});
