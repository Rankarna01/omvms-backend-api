<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\OvertimeRequest;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HrDashboardController extends Controller
{
    public function index()
    {
        try {
            $now = Carbon::now();
            
            // UBAH DISINI: Ambil range dari AWAL TAHUN, bukan awal bulan
            // Agar data Januari tetap masuk meskipun sekarang Februari
            $startDate = $now->copy()->startOfYear(); 
            $endDate   = $now->copy()->endOfYear();
            $today     = $now->copy()->format('Y-m-d');

            // 1. STATS CARDS
            $totalEmployees = Employee::count(); 
            $totalDepartments = Department::count(); 
            
            // Hitung jam lembur BULAN INI saja (untuk KPI bulanan)
            $monthlyOvertimeHours = OvertimeRequest::whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->where('status', 'APPROVED')
                ->sum('duration');

            $vouchersRedeemedToday = Voucher::whereDate('updated_at', $today)
                ->where('status', 'REDEEMED')
                ->count();

            // 2. CHART DATA (Top Dept)
            // UBAH LOGIC QUERY: Gunakan LEFT JOIN & COALESCE
            $topDepartments = OvertimeRequest::leftJoin('employees', 'overtime_requests.employee_id', '=', 'employees.id')
                ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                // Filter TAHUN INI (Jan - Des)
                ->whereBetween('overtime_requests.date', [$startDate, $endDate]) 
                ->where('overtime_requests.status', 'APPROVED')
                ->select(
                    // Jika dept_name NULL (karyawan tanpa dept), ganti jadi 'Unassigned'
                    DB::raw('COALESCE(departments.dept_name, "Unassigned") as name'), 
                    DB::raw('SUM(overtime_requests.duration) as total_hours')
                )
                ->groupBy('name') // Group by alias 'name'
                ->orderByDesc('total_hours')
                ->limit(5)
                ->get();

            // 3. RECENT ACTIVITIES
            $recentOvertimes = OvertimeRequest::with(['employee.department'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'employee_name' => $item->employee->full_name ?? 'Unknown',
                        'dept_name' => $item->employee->department->dept_name ?? 'General',
                        'date' => Carbon::parse($item->date)->format('d M Y'),
                        'duration' => $item->duration . ' Jam',
                        'status' => $item->status,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data loaded',
                'meta' => [
                    'filter_start' => $startDate->format('Y-m-d'),
                    'filter_end' => $endDate->format('Y-m-d')
                ],
                'data' => [
                    'stats' => [
                        'total_employees' => $totalEmployees,
                        'total_departments' => $totalDepartments,
                        'monthly_overtime_hours' => (float) $monthlyOvertimeHours,
                        'vouchers_redeemed_today' => $vouchersRedeemedToday,
                    ],
                    'charts' => [
                        'top_departments' => $topDepartments
                    ],
                    'recent_activities' => $recentOvertimes
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'SERVER ERROR: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
}