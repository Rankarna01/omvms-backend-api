<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    /**
     * GET: Menampilkan semua data lembur (untuk Tabel Frontend)
     */
    public function index()
    {
        // Load data lembur beserta data karyawan dan shift-nya
        // Diurutkan dari yang paling baru
        $data = OvertimeRequest::with(['employee.shift'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * POST: Membuat pengajuan lembur baru (Admin Dept)
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'reason'      => 'required|string',
        ]);

        // 1. Ambil Data Karyawan & Shift
        $employee = Employee::with('shift')->findOrFail($request->employee_id);
        
        // --- LOGIC 1: STRICT PRE-APPROVAL (Cek Lock Request) ---
        // Jika Shift mengaktifkan lock_request, Admin tidak boleh input setelah jam pulang shift.
        if ($employee->shift && $employee->shift->lock_request) {
            
            // Asumsi: Kita cek jam pulang shift hari ini
            $shiftEndTime = Carbon::createFromFormat('H:i:s', $employee->shift->end_time);
            
            // Jika request untuk hari ini, cek jamnya
            if ($request->date == Carbon::now()->format('Y-m-d')) {
                if (Carbon::now()->gt($shiftEndTime)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'GAGAL: Waktu pengajuan habis! Shift berakhir pukul ' . $shiftEndTime->format('H:i') . '.'
                    ], 422);
                }
            }
            
            // Jika request untuk tanggal lampau (kemarin dll), otomatis tolak jika strict mode
            if ($request->date < Carbon::now()->format('Y-m-d')) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'GAGAL: Tidak bisa mengajukan lembur mundur (Strict Mode Aktif).'
                ], 422);
            }
        }

        // --- LOGIC 2: VOUCHER ELIGIBILITY (Cek Allow Meal) ---
        // Hitung Durasi (Jam)
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        
        // Handle shift malam (Start 23:00 - End 03:00)
        if ($end->lessThan($start)) {
            $end->addDay();
        }
        
        $duration = $start->diffInHours($end); // Integer, misal 4

        // Syarat: Shift allow_meal = TRUE  DAN  Durasi > 4 Jam
        $isEligible = false;
        if ($employee->shift && $employee->shift->allow_meal && $duration >= 4) {
            $isEligible = true;
        }

        // --- SIMPAN DATA ---
        $overtime = OvertimeRequest::create([
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'duration'    => $duration,
            'reason'      => $request->reason,
            'is_eligible_for_voucher' => $isEligible,
            'status'      => 'SUBMITTED', // Default status
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil dibuat.',
            'data' => $overtime
        ], 201);
    }

    /**
     * PUT: Update data lembur
     */
    public function update(Request $request, $id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        // Validasi (Boleh diedit kalau status masih DRAFT / SUBMITTED / REJECTED)
        if ($overtime->status === 'APPROVED') {
            return response()->json(['message' => 'Data yang sudah disetujui tidak bisa diedit.'], 403);
        }

        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'reason'      => 'required|string',
        ]);

        // Recalculate Duration & Voucher
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        if ($end->lessThan($start)) $end->addDay();
        
        $duration = $start->diffInHours($end);

        // Cek eligibility ulang (Ambil data shift fresh)
        $employee = Employee::with('shift')->find($overtime->employee_id);
        $isEligible = false;
        if ($employee->shift && $employee->shift->allow_meal && $duration > 4) {
            $isEligible = true;
        }

        $overtime->update([
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'duration'   => $duration,
            'reason'     => $request->reason,
            'is_eligible_for_voucher' => $isEligible
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembur berhasil diperbarui.',
            'data' => $overtime
        ]);
    }

    /**
     * DELETE: Hapus data lembur
     */
    public function destroy($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);
        
        if ($overtime->status === 'APPROVED') {
            return response()->json(['message' => 'Data yang sudah disetujui tidak bisa dihapus.'], 403);
        }

        $overtime->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembur berhasil dihapus.'
        ]);
    }

    /**
     * POST: Approve Request (Head Dept) - Logic Dynamic Countdown
     */
    public function approve($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Status request tidak valid untuk diapprove.'], 400);
        }

        // --- LOGIC 3: DYNAMIC COUNTDOWN (Aturan Expired Voucher) ---
        $approvalTime = Carbon::now();
        
        // Gabungkan Tanggal Lembur + Jam Selesai Lembur
        $overtimeEndTime = Carbon::parse($overtime->date . ' ' . $overtime->end_time);
        if (Carbon::parse($overtime->start_time)->gt(Carbon::parse($overtime->end_time))) {
            // Jika lembur lintas hari (mulai 23:00 selesai 03:00), tambah 1 hari ke end time
            $overtimeEndTime->addDay();
        }

        $normalWindow = 4; // Jam expired normal
        $lateWindow = 2;   // Jam expired jika telat approve

        if ($approvalTime->greaterThanOrEqualTo($overtimeEndTime)) {
            // SKENARIO TELAT: Head Dept approve setelah lembur selesai
            // Rumus: Jam Approval + 2 Jam
            $expiredAt = $approvalTime->copy()->addHours($lateWindow);
        } else {
            // SKENARIO NORMAL: Head Dept approve saat karyawan masih kerja
            // Rumus: Jam Selesai Lembur + 4 Jam
            $expiredAt = $overtimeEndTime->copy()->addHours($normalWindow);
        }

        $overtime->update([
            'status'      => 'APPROVED',
            'approved_at' => $approvalTime,
            'expired_at'  => $expiredAt
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lembur disetujui. Voucher expired pada: ' . $expiredAt->format('d M Y H:i'),
            'data' => $overtime
        ]);
    }
    
    /**
     * POST: Bulk Create (Opsional untuk fitur Import Excel)
     */
    public function bulkStore(Request $request)
    {
        // Placeholder untuk fitur bulk import nanti
        // Looping store logic di sini
        return response()->json(['message' => 'Bulk insert success'], 200);
    }
}