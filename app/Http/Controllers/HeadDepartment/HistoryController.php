<?php

namespace App\Http\Controllers\HeadDepartment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Validasi Role
            if ($user->role !== 'head_dept') {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            $deptId = $user->department_id;

            // Ambil data riwayat (APPROVED atau REJECTED) sesuai departemen
            $history = OvertimeRequest::with(['employee' => function($q) {
                            $q->select('id', 'full_name'); // Hanya ambil data yang diperlukan
                        }])
                        ->whereHas('employee', function($q) use ($deptId) {
                            $q->where('department_id', $deptId);
                        })
                        ->whereIn('status', ['APPROVED', 'REJECTED']) // Hanya yang sudah diproses
                        ->orderBy('updated_at', 'desc') // Urutkan dari yang paling baru diproses
                        ->get()
                        ->map(function($ot) {
                            // Tentukan waktu proses (gunakan approved_at, jika null gunakan updated_at)
                            $processedAt = $ot->approved_at ? Carbon::parse($ot->approved_at) : Carbon::parse($ot->updated_at);

                            return [
                                'id' => $ot->id,
                                'employeeName' => $ot->employee->full_name ?? 'Unknown',
                                'date' => Carbon::parse($ot->date)->format('d M Y'),
                                'approvedAt' => $processedAt->format('d M Y H:i'),
                                'status' => $ot->status,
                                // Gunakan field reason sebagai notes, atau jika Anda punya field 'reject_reason', sesuaikan di sini
                                'notes' => $ot->reason ?? '-' 
                            ];
                        });

            return response()->json([
                'status' => 'success',
                'data' => $history
            ]);

        } catch (\Exception $e) {
            Log::error("Head History Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data riwayat.'
            ], 500);
        }
    }
}