<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- AUTH CONTROLLERS ---
use App\Http\Controllers\AuthController;

// --- MASTER DATA CONTROLLERS ---
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ShiftController;

// --- TRANSACTION CONTROLLERS ---
use App\Http\Controllers\Admin\OvertimeController;
use App\Http\Controllers\Admin\OvertimeSummaryController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\HrDashboardController;
use App\Http\Controllers\Api\PosScanController;

// --- EMPLOYEE SPECIFIC CONTROLLERS ---
use App\Http\Controllers\Employee\EmployeeVoucherController;
use App\Http\Controllers\Employee\EmployeeOvertimeController;

// --- SYSTEM ACCOUNT CONTROLLERS ---
use App\Http\Controllers\Admin\EmployeeAccount\EmployeeAccountController;
use App\Http\Controllers\Admin\DepartmentAccount\DepartmentAccountController;
use App\Http\Controllers\Admin\CanteenAccount\CanteenAccountController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);

// 2. PROTECTED ROUTES (Need Token)
Route::middleware('auth:sanctum')->group(function () {

    // --- AUTH & PROFILE ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ==========================================================
    // MODULE: HR SYSTEM (HR Manager & System Admin)
    // ==========================================================
    
    // Group 1: Dashboard HR
    Route::prefix('hr-system')->group(function () {
        Route::get('/dashboard', [HrDashboardController::class, 'index']);
    });

    // Group 2: Master Data (Prefix: /hr/...)
    Route::prefix('hr')->group(function () {
        Route::get('/departments-list', [EmployeeController::class, 'getDepartments']);
        Route::apiResource('/departments', DepartmentController::class);
        Route::apiResource('/employees', EmployeeController::class);
    });

    // Group 3: Shift Management
    Route::prefix('shifts')->group(function () {
        Route::get('/', [ShiftController::class, 'index']);
        Route::post('/', [ShiftController::class, 'store']);
        Route::put('/{id}', [ShiftController::class, 'update']);
        Route::delete('/{id}', [ShiftController::class, 'destroy']);
    });

    // ==========================================================
    // MODULE: ADMIN DEPARTMENT (Input Lembur)
    // ==========================================================
    
    // Group Dashboard & Summary Admin Dept
    Route::prefix('admin-dept')->group(function () {
        // Route::get('/dashboard', ...); // Jika nanti ada dashboard khusus admin dept
        Route::get('/weekly-summary', [OvertimeSummaryController::class, 'index']);
    });

    // CRUD Overtime Requests (Biasanya diakses Admin Dept)
    // Note: Tidak di-group prefix agar frontend lama tetap jalan (/overtime-requests)
    Route::get('/overtime-requests', [OvertimeController::class, 'index']);
    Route::post('/overtime-requests', [OvertimeController::class, 'store']);
    Route::put('/overtime-requests/{id}', [OvertimeController::class, 'update']);
    Route::delete('/overtime-requests/{id}', [OvertimeController::class, 'destroy']);
    Route::post('/overtime-requests/bulk', [OvertimeController::class, 'bulkStore']);

    // ==========================================================
    // MODULE: HEAD DEPARTMENT (Approval)
    // ==========================================================
    Route::prefix('head')->group(function () {
        Route::get('/overtime-pending', [OvertimeController::class, 'pending']);
    });
    
    // Action Approval/Reject/View Voucher (Head Dept)
    Route::post('/overtime-requests/{id}/approve', [OvertimeController::class, 'approve']);
    Route::post('/overtime-requests/{id}/reject', [OvertimeController::class, 'reject']);
    Route::get('/vouchers', [VoucherController::class, 'index']); // List semua voucher dept

    // ==========================================================
    // MODULE: EMPLOYEE (Karyawan)
    // ==========================================================
    Route::prefix('employee')->group(function () {
        Route::get('/my-vouchers', [EmployeeVoucherController::class, 'index']);
        Route::get('/my-overtime-requests', [EmployeeOvertimeController::class, 'index']);
    });

    // ==========================================================
    // MODULE: POS / CANTEEN (Kantin)
    // ==========================================================
    Route::prefix('pos')->group(function () {
        Route::post('/scan', [PosScanController::class, 'scan']);
        Route::post('/redeem', [PosScanController::class, 'redeem']);
    });

    // ==========================================================
    // MODULE: SYSTEM ADMIN (Account Management)
    // ==========================================================
    Route::prefix('admin-omvms')->group(function () {
        Route::apiResource('employee-accounts', EmployeeAccountController::class);
        Route::apiResource('department-accounts', DepartmentAccountController::class);
        Route::apiResource('canteen-accounts', CanteenAccountController::class);
    });

});