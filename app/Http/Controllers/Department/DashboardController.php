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
            
            // Berdasarkan SQL: User Anda memiliki department_id langsung di tabel users
            $deptId = $user->department_id;

            if (!$deptId) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'User ini tidak terasosiasi dengan departemen manapun.'
                ], 403);
            }

            // Gunakan find() biasa, hindari findOrFail agar tidak melempar exception jika dept tidak ada
            $department = Department::find($deptId);
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Stats Logic - Menggunakan relasi ke employee yang memiliki department_id
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
                                      
                'activeEmployees'  => Employee::where('department_id', $deptId)
                                      ->where('is_active', 1)->count(),
            ];

            // List Hari Ini
            $todayList = OvertimeRequest::with('employee')
                ->whereHas('employee', function($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                })
                ->whereDate('date', $today)
                ->get()
                ->map(function($ot) {
                    return [
                        'id' => $ot->id,
                        'employeeName' => $ot->employee->full_name ?? 'Unknown',
                        'position' => $ot->employee->position ?? '-',
                        // Tambahkan fallback jika start_time atau end_time bernilai null
                        'startTime' => $ot->start_time ? Carbon::parse($ot->start_time)->format('H:i') : '-',
                        'endTime' => $ot->end_time ? Carbon::parse($ot->end_time)->format('H:i') : '-',
                        'duration' => $ot->duration . ' Jam',
                        'mealStatus' => $ot->duration >= 3 ? 'Eligible' : 'Not Eligible'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'departmentName' => $department ? $department->dept_name : 'Departemen',
                    'stats' => $stats,
                    'todayList' => $todayList
                ]
            ]);

        } catch (\Exception $e) {
            // Log error untuk mempermudah debugging
            Log::error("Dashboard Error: " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal server.',
                'error_detail' => env('APP_DEBUG') ? $e->getMessage() : null // Tampilkan detail error jika mode debug aktif
            ], 500);
        }
    }
}