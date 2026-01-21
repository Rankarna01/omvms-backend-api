<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;

// Public Route
Route::post('/login', [AuthController::class, 'login']);

// Protected Route (Harus Login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Module HR ---
    // API Dropdown Department
    Route::get('/hr/departments-list', [EmployeeController::class, 'getDepartments']);
    
    // API CRUD Employees (Otomatis index, store, update, destroy, show)
    Route::apiResource('/hr/employees', EmployeeController::class);
});