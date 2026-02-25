<?php

namespace App\Http\Controllers\HeadDepartment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    /**
     * Mengambil data agregasi lembur (Harian & Mingguan)
     */
    public function index(Request $request)
    {
        try {
            $data = $this->getReportData($request->user());
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) {
            Log::error("Report Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal memuat laporan'], 500);
        }
    }

    /**
     * Generate dan Download PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $user = $request->user();
            $data = $this->getReportData($user);
            $department = Department::find($user->department_id);

            // Load view blade dan passing data
            $pdf = Pdf::loadView('exports.department_report', [
                'data' => $data,
                'dept_name' => $department->dept_name ?? 'Department',
                'date' => Carbon::now()->format('d M Y H:i')
            ]);

            // Return file PDF langsung
            return $pdf->download('Report_Lembur_' . Carbon::now()->format('Ymd') . '.pdf');

        } catch (\Exception $e) {
            Log::error("PDF Export Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat PDF'], 500);
        }
    }

    /**
     * PRIVATE METHOD: Logika inti untuk menghitung total jam & voucher
     */
    private function getReportData($user)
    {
        $deptId = $user->department_id;
        $department = Department::find($deptId);
        
        $today = Carbon::today()->format('Y-m-d');
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');

        // Query Efisien menggunakan Aggregates
        $employees = Employee::where('department_id', $deptId)
            ->withSum(['overtimeRequests as dailyDuration' => function($q) use ($today) {
                $q->where('status', 'APPROVED')->whereDate('date', $today);
            }], 'duration')
            ->withSum(['overtimeRequests as weeklyDuration' => function($q) use ($startOfWeek, $endOfWeek) {
                $q->where('status', 'APPROVED')->whereBetween('date', [$startOfWeek, $endOfWeek]);
            }], 'duration')
            ->withCount(['vouchers as vouchersIssued' => function($q) use ($startOfWeek, $endOfWeek) {
                // Asumsi voucher diterbitkan minggu ini
                $q->whereBetween('created_at', [$startOfWeek . ' 00:00:00', $endOfWeek . ' 23:59:59']);
            }])
            ->get()
            ->map(function($emp) use ($department) {
                return [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'dept' => $department->dept_name ?? '-',
                    'dailyDuration' => (int) $emp->dailyDuration ?? 0,
                    'weeklyDuration' => (int) $emp->weeklyDuration ?? 0,
                    'vouchersIssued' => $emp->vouchersIssued ?? 0,
                ];
            });

        return $employees;
    }
}