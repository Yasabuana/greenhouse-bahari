<?php

use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless dan tanpa CSRF, siap dipanggil langsung oleh bridge
| Python serial/MQTT maupun perangkat lain di jaringan lokal.
|
*/

Route::post('/sensor', SensorController::class)->name('api.sensor');
Route::get('/dashboard', [DashboardController::class, 'data'])->name('api.dashboard');
Route::get('/nodes', [NodeController::class, 'data'])->name('api.nodes');
