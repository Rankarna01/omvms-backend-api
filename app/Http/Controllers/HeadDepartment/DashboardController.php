<?php

namespace App\Http\Controllers\HeadDepartment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // 1. Ambil data user login
            $user = $request->user();
            
            // Validasi Role (Harus Head Dept)
            if ($user->role !== 'head_dept') {
                return response()->json(['message' => 'Akses ditolak. Khusus Head Department.'], 403);
            }

            $deptId = $user->department_id;
            $today = Carbon::today()->format('Y-m-d');

            // 2. LOGIC STATISTIK (Cards)
            // Mengambil data lembur khusus untuk departemen Head yang sedang login
            
            // A. Menunggu Persetujuan (SUBMITTED)
            $pendingCount = OvertimeRequest::whereHas('employee', function($q) use ($deptId) {
                                $q->where('department_id', $deptId);
                            })->where('status', 'SUBMITTED')->count();

            // B. Hak Voucher Terbit (APPROVED + >= 4 Jam + Eligible)
            $voucherCount = OvertimeRequest::whereHas('employee', function($q) use ($deptId) {
                                $q->where('department_id', $deptId);
                            })
                            ->where('status', 'APPROVED')
                            ->where('is_eligible_for_voucher', true)
                            ->whereDate('date', $today)
                            ->count();

            // C. Hanya Pencatatan (< 4 Jam atau Rejected)
            $recordOnlyCount = OvertimeRequest::whereHas('employee', function($q) use ($deptId) {
                                $q->where('department_id', $deptId);
                            })
                            ->whereDate('date', $today)
                            ->where(function($q) {
                                $q->where('status', 'REJECTED')
                                  ->orWhere('is_eligible_for_voucher', false);
                            })->count();

            // 3. LOGIC LIST DATA (Tabel Urgent Approval)
            // Menampilkan data lembur yang statusnya masih 'SUBMITTED' (Need Review)
            $urgentApprovals = OvertimeRequest::with(['employee' => function($q) {
                                    $q->select('id', 'full_name', 'position', 'department_id');
                                }])
                                ->whereHas('employee', function($q) use ($deptId) {
                                    $q->where('department_id', $deptId);
                                })
                                ->where('status', 'SUBMITTED')
                                ->orderBy('date', 'desc')
                                ->orderBy('start_time', 'desc')
                                ->take(10) // Ambil 10 terbaru agar dashboard tidak berat
                                ->get()
                                ->map(function($ot) {
                                    // Hitung status kelayakan voucher sementara untuk ditampilkan
                                    // Anggap eligible jika shift allow_meal = true dan durasi >= 4
                                    return [
                                        'id' => $ot->id,
                                        'employee' => $ot->employee->full_name ?? 'Unknown',
                                        'dept' => $ot->employee->position ?? '-', // Bisa ganti posisi atau nama dept
                                        // Format tanggal: jika hari ini tulis 'Hari Ini', jika lain tulis tanggal
                                        'date' => $ot->date->format('Y-m-d') == Carbon::today()->format('Y-m-d') 
                                                  ? 'Hari Ini' 
                                                  : $ot->date->format('d M Y'),
                                        'time' => Carbon::parse($ot->start_time)->format('H:i') . ' - ' . Carbon::parse($ot->end_time)->format('H:i'),
                                        'eligible' => $ot->duration >= 4 // Logika sederhana untuk UI
                                    ];
                                });

            // 4. RETURN GABUNGAN RESPONSE
            return response()->json([
                'status' => 'success',
                'data' => [
                    'managerName' => $user->name,
                    'stats' => [
                        'pending' => $pendingCount,
                        'voucher' => $voucherCount,
                        'record_only' => $recordOnlyCount
                    ],
                    'urgentApprovals' => $urgentApprovals
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Head Dashboard Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data dashboard.'
            ], 500);
        }
    }
}