<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public Route (Bisa diakses tanpa token)
Route::post('/login', [AuthController::class, 'login']);

// Protected Route (Harus kirim Header Authorization: Bearer <token>)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Nanti endpoint employee ditaruh disini
});