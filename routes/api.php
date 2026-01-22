<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// Controller HR System
use App\Http\Controllers\DepartmentController; 
use App\Http\Controllers\EmployeeController;
// Controller Admin OMVMS (Import namespace yang baru kita buat)
use App\Http\Controllers\Admin\EmployeeAccount\EmployeeAccountController;
use App\Http\Controllers\Admin\DepartmentAccount\DepartmentAccountController;
use App\Http\Controllers\Admin\CanteenAccount\CanteenAccountController;

// Public Route
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // --- COMMON ROUTES ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- HR SYSTEM ROUTES (Manage Data Master) ---
    Route::get('/hr/departments-list', [EmployeeController::class, 'getDepartments']);
    Route::apiResource('/hr/departments', DepartmentController::class);
    Route::apiResource('/hr/employees', EmployeeController::class);

    // --- ADMIN OMVMS SYSTEM ROUTES (Manage Akun Login) ---
    // Prefix URL: /api/admin-omvms/...
    Route::prefix('admin-omvms')->group(function () {
        // 1. Manage Akun Karyawan (Link to Employee)
        Route::apiResource('employee-accounts', EmployeeAccountController::class);
        // 2. Manage Akun Departemen (Admin Dept & Head Dept)
        Route::apiResource('department-accounts', DepartmentAccountController::class);
        // 3. Manage Akun Kantin/POS (Standalone)
        Route::apiResource('canteen-accounts', CanteenAccountController::class);
    });

});