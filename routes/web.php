<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\NodeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/nodes', [NodeController::class, 'index'])->name('nodes.index');
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

