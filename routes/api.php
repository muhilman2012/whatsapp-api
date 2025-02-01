<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\AuthController;

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

// Route untuk autentikasi
Route::post('/register', [AuthController::class, 'register']); // Register user baru
Route::post('/login', [AuthController::class, 'login']);       // Login user
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']); // Logout user

// Routes untuk laporan
Route::middleware('auth:sanctum')->prefix('laporan')->group(function () {
    Route::post('/kirim', [LaporanController::class, 'store']); // Kirim laporan
    Route::post('/status', [LaporanController::class, 'getStatus']); // Cek status laporan (bisa dengan nomor_tiket atau nik)
    Route::patch('/status/{nomor_tiket}', [LaporanController::class, 'updateStatus']); // Update status laporan
    Route::get('/validasi-nik/{nik}', [LaporanController::class, 'validateNik']); // Validasi NIK
    Route::post('/dokumen-tambahan', [LaporanController::class, 'kirimDokumenTambahan']); // Kirim dokumen tambahan
});