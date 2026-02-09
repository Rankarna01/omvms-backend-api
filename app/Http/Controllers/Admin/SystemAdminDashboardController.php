<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Voucher; // <--- Pastikan Model Voucher ADA
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SystemAdminDashboardController extends Controller
{
    public function index()
    {
        try {
            // 1. STATS CARDS
            // Pastikan tabel employees ada kolom 'status' atau hapus where-nya jika belum ada
            $totalEmployees = Employee::count(); 
            $totalDepartments = Department::count();
            
            // Pastikan tabel users punya kolom 'role'
            $totalPosDevices = User::where('role', 'admin_pos')->count(); 

            // 2. TRAFFIC CHART (Voucher Redeemed per Month)
            // Cek apakah Model Voucher sudah dibuat?
            if (class_exists(Voucher::class)) {
                $trafficData = Voucher::select(
                        DB::raw('MONTH(redeemed_at) as month'), 
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('redeemed_at', Carbon::now()->year)
                    ->where('status', 'REDEEMED')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
            } else {
                $trafficData = collect([]); // Fallback jika Voucher belum ada
            }

            // Format Data Chart
            $formattedTraffic = collect(range(1, 12))->map(function ($month) use ($trafficData) {
                // Handle jika trafficData kosong
                $found = $trafficData instanceof \Illuminate\Support\Collection 
                    ? $trafficData->firstWhere('month', $month) 
                    : null;
                
                return [
                    'name' => Carbon::create()->month($month)->format('M'),
                    'val' => $found ? $found->total : 0
                ];
            })->values();

            // 3. SYSTEM HEALTH (Simulasi)
            $systemHealth = 98;

            // 4. LOGS (Dummy Data Aman)
            $logs = [
                [
                    'id' => 1, 'action' => 'System Backup Completed', 'user' => 'System Auto', 
                    'time' => '10 min ago', 'icon' => 'backup', 'color' => 'bg-purple-100 text-purple-600'
                ],
                [
                    'id' => 2, 'action' => 'New Department Created', 'user' => 'Super Admin', 
                    'time' => '2 hours ago', 'icon' => 'domain_add', 'color' => 'bg-blue-100 text-blue-600'
                ],
                [
                    'id' => 3, 'action' => 'POS Login detected', 'user' => 'POS Admin', 
                    'time' => '5 hours ago', 'icon' => 'login', 'color' => 'bg-green-100 text-green-600'
                ],
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'total_employees' => $totalEmployees,
                        'total_departments' => $totalDepartments,
                        'total_pos' => $totalPosDevices,
                    ],
                    'traffic' => $formattedTraffic,
                    'health' => $systemHealth,
                    'logs' => $logs
                ]
            ]);

        } catch (\Exception $e) {
            // TAMPILKAN ERROR ASLI BIAR GAMPANG DEBUG
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(), // <--- Ini akan memberitahu errornya apa
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}