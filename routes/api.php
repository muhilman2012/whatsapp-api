<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\LaporanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('laporan')->group(function () {
    Route::post('/kirim', [LaporanController::class, 'store']); // Kirim laporan
    Route::get('/status/{nomor_tiket}', [LaporanController::class, 'getStatus']); // Cek status
    Route::patch('/status/{nomor_tiket}', [LaporanController::class, 'updateStatus']); // Update status
});