<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Admin\EmployeeAccount\EmployeeAccountController;
use App\Http\Controllers\Admin\DepartmentAccount\DepartmentAccountController;
use App\Http\Controllers\Admin\CanteenAccount\CanteenAccountController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\Admin\OvertimeController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/hr/departments-list', [EmployeeController::class, 'getDepartments']);
    Route::apiResource('/hr/departments', DepartmentController::class);
    Route::apiResource('/hr/employees', EmployeeController::class);
    // Route HR Management - Shifts
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::put('/shifts/{id}', [ShiftController::class, 'update']);
    Route::delete('/shifts/{id}', [ShiftController::class, 'destroy']);

    // === ROUTE OVERTIME (Tambahkan ini) ===
    Route::get('/overtime-requests', [OvertimeController::class, 'index']);
    Route::post('/overtime-requests', [OvertimeController::class, 'store']);
    Route::put('/overtime-requests/{id}', [OvertimeController::class, 'update']);
    Route::delete('/overtime-requests/{id}', [OvertimeController::class, 'destroy']);
    
    // Route khusus Bulk & Approve
    Route::post('/overtime-requests/bulk', [OvertimeController::class, 'bulkStore']);
    Route::post('/overtime-requests/{id}/approve', [OvertimeController::class, 'approve']);


    Route::prefix('admin-omvms')->group(function () {
        // 1. Manage Akun Karyawan (Link to Employee)
        Route::apiResource('employee-accounts', EmployeeAccountController::class);
        // 2. Manage Akun Departemen (Admin Dept & Head Dept)
        Route::apiResource('department-accounts', DepartmentAccountController::class);
        // 3. Manage Akun Kantin/POS (Standalone)
        Route::apiResource('canteen-accounts', CanteenAccountController::class);
    });
});
