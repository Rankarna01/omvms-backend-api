<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
// Tambahkan baris ini 👇
use App\Http\Controllers\DepartmentController; 

// Public Route
Route::post('/login', [AuthController::class, 'login']);

// Protected Route
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Module HR ---
    
    // 1. Dropdown List (Pastikan method getDepartments ada di EmployeeController)
    Route::get('/hr/departments-list', [EmployeeController::class, 'getDepartments']);
    
    // 2. CRUD Departments (Pastikan File Controller sudah dibuat!)
    Route::apiResource('/hr/departments', DepartmentController::class);
    
    // 3. CRUD Employees
    Route::apiResource('/hr/employees', EmployeeController::class);
});