<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use App\Models\Employee;
use App\Models\Voucher;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $deptId = $user->department_id;

            if (!$deptId) {
                return response()->json(['status' => 'error', 'message' => 'User ini tidak terasosiasi dengan departemen manapun.'], 403);
            }

            $department = Department::find($deptId);
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Stats Logic (Ditambah overbreak hari ini)
            $stats = [
                'pendingApprovals' => OvertimeRequest::whereHas('employee', function($q) use ($deptId) {
                                        $q->where('department_id', $deptId);
                                      })->where('status', 'SUBMITTED')->count(),
                                      
                'todaysPortions'   => OvertimeRequest::whereHas('employee', function($q) use ($deptId) {
                                        $q->where('department_id', $deptId);
                                      })->whereDate('date', $today)
                                        ->where('duration', '>=', 3)
                                        ->where('status', 'APPROVED')->count(),
                                        
                'monthlyVouchers'  => Voucher::whereHas('employee', function($q) use ($deptId) {
                                        $q->where('department_id', $deptId);
                                      })->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                                      
                'activeEmployees'  => Employee::where('department_id', $deptId)->where('is_active', 1)->count(),

                // STATISTIK BARU: Karyawan Overbreak Hari Ini
                'overbreakToday'   => Voucher::whereHas('employee', function($q) use ($deptId) {
                                        $q->where('department_id', $deptId);
                                      })->whereDate('checkin_at', $today)
                                        ->where('status', 'OVERBREAK')->count(),
            ];

            // List Lembur Hari Ini
            $todayList = OvertimeRequest::with('employee')
                ->whereHas('employee', function($q) use ($deptId) { $q->where('department_id', $deptId); })
                ->whereDate('date', $today)
                ->get()
                ->map(function($ot) {
                    return [
                        'id' => $ot->id,
                        'employeeName' => $ot->employee->full_name ?? 'Unknown',
                        'position' => $ot->employee->position ?? '-',
                        'startTime' => $ot->start_time ? Carbon::parse($ot->start_time)->format('H:i') : '-',
                        'endTime' => $ot->end_time ? Carbon::parse($ot->end_time)->format('H:i') : '-',
                        'duration' => $ot->duration . ' Jam',
                        'mealStatus' => $ot->duration >= 3 ? 'Eligible' : 'Not Eligible'
                    ];
                });

            // DATA BARU: List Karyawan Telat Istirahat Hari Ini
            $overbreakList = Voucher::with('employee')
                ->whereHas('employee', function($q) use ($deptId) { $q->where('department_id', $deptId); })
                ->whereDate('checkin_at', $today)
                ->where('status', 'OVERBREAK')
                ->get()
                ->map(function($v) {
                    $checkin = Carbon::parse($v->checkin_at);
                    $checkout = Carbon::parse($v->checkout_at);
                    return [
                        'voucher_code' => $v->code,
                        'employeeName' => $v->employee->full_name ?? 'Unknown',
                        'checkinTime' => $checkin->format('H:i'),
                        'checkoutTime' => $checkout->format('H:i'),
                        'durationMinutes' => $checkin->diffInMinutes($checkout) . ' Menit'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'departmentName' => $department ? $department->dept_name : 'Departemen',
                    'stats' => $stats,
                    'todayList' => $todayList,
                    'overbreakList' => $overbreakList // Tampilkan di tabel dashboard frontend
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Dashboard Error: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal server.',
                'error_detail' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}