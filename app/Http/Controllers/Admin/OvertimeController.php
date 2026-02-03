<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OvertimeController extends Controller
{
    public function index()
    {
        $data = OvertimeRequest::with(['employee.shift'])->latest()->get();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'reason'      => 'required|string',
        ]);

        $employee = Employee::with('shift')->findOrFail($request->employee_id);
        
        // --- PRE-CALCULATION DURASI ---
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        if ($end->lessThan($start)) $end->addDay(); // Handle cross day if needed (though limited by FE)
        
        $duration = $start->diffInHours($end); // Integer hours (or float if using diffInMinutes/60)

        // LOGIC 1: JAM LEMBUR HARUS SETELAH SHIFT
        if ($employee->shift) {
            $shiftEndTime = Carbon::createFromFormat('H:i:s', $employee->shift->end_time);
            $reqStartTime = Carbon::parse($request->start_time);

            // Jika lembur dimulai sebelum shift berakhir -> TOLAK
            if ($reqStartTime->lt($shiftEndTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lembur hanya bisa dimulai setelah jam shift berakhir (' . $shiftEndTime->format('H:i') . ').'
                ], 422);
            }
        }

        // LOGIC 2: DURASI MAKSIMAL 4 JAM (Per Hari)
        if ($duration > 4) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Maksimal lembur adalah 4 Jam per hari sesuai peraturan.'
            ], 422);
        }

        // =================================================================
        // [NEW LOGIC] CEK LIMIT MINGGUAN (Maksimal 18 Jam / Minggu)
        // =================================================================
        $requestDate = Carbon::parse($request->date);
        $startOfWeek = $requestDate->copy()->startOfWeek(); // Senin
        $endOfWeek   = $requestDate->copy()->endOfWeek();   // Minggu

        // Hitung total jam lembur yang sudah ada di minggu ini (Submitted + Approved)
        // Kita exclude Rejected.
        $weeklyHours = OvertimeRequest::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->whereIn('status', ['SUBMITTED', 'APPROVED'])
            ->sum('duration');

        if (($weeklyHours + $duration) > 18) {
            return response()->json([
                'status' => 'error',
                'message' => "Batas lembur mingguan terlampaui! (Saat ini: $weeklyHours jam + Baru: $duration jam > 18 jam)"
            ], 422);
        }
        // =================================================================


        // LOGIC 3: VOUCHER (Minimal 4 Jam Pas)
        $isEligible = ($employee->shift && $employee->shift->allow_meal && $duration >= 4);

        $overtime = OvertimeRequest::create([
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'duration'    => $duration,
            'reason'      => $request->reason,
            'is_eligible_for_voucher' => $isEligible,
            'status'      => 'SUBMITTED',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil dibuat.',
            'data' => $overtime
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status === 'APPROVED') {
            return response()->json(['message' => 'Data disetujui tidak bisa diedit.'], 403);
        }

        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'reason'      => 'required|string',
        ]);

        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        if ($end->lessThan($start)) $end->addDay();
        
        $duration = $start->diffInHours($end);

        if ($duration > 4) {
            return response()->json(['message' => 'Maksimal lembur adalah 4 Jam.'], 422);
        }

        // [NEW LOGIC] RE-CHECK WEEKLY LIMIT ON UPDATE
        // Exclude current record from calculation
        $employee = Employee::with('shift')->find($overtime->employee_id);
        
        $requestDate = Carbon::parse($request->date);
        $startOfWeek = $requestDate->copy()->startOfWeek();
        $endOfWeek   = $requestDate->copy()->endOfWeek();

        $weeklyHours = OvertimeRequest::where('employee_id', $employee->id)
            ->where('id', '!=', $id) // Exclude diri sendiri
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->whereIn('status', ['SUBMITTED', 'APPROVED'])
            ->sum('duration');

        if (($weeklyHours + $duration) > 18) {
            return response()->json([
                'status' => 'error',
                'message' => "Update Gagal: Batas lembur mingguan akan terlampaui ($weeklyHours + $duration > 18)."
            ], 422);
        }

        $isEligible = ($employee->shift && $employee->shift->allow_meal && $duration >= 4);

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
            'message' => 'Data berhasil diperbarui.',
            'data' => $overtime
        ]);
    }

    public function pending()
    {
        // Hanya ambil yang statusnya SUBMITTED
        $data = OvertimeRequest::with(['employee.shift'])
            ->where('status', 'SUBMITTED')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function reject(Request $request, $id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Status tidak valid.'], 400);
        }

        $overtime->update([
            'status' => 'REJECTED'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur ditolak.',
            'data' => $overtime
        ]);
    }
    
    public function destroy($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);
        if ($overtime->status === 'APPROVED') {
            return response()->json(['message' => 'Data disetujui tidak bisa dihapus.'], 403);
        }
        $overtime->delete();
        return response()->json(['status' => 'success', 'message' => 'Data dihapus.']);
    }

    public function approve($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        // 1. Validasi Status Awal
        if ($overtime->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Status request tidak valid untuk diapprove.'], 400);
        }

        // --- PERSIAPAN WAKTU ---
        $approvalTime = Carbon::now();
        
        // Format tanggal bersih (Y-m-d) agar tidak error double time
        $dateString = $overtime->date->format('Y-m-d'); 
        
        // Tentukan Waktu Mulai Lembur yang Sebenarnya
        $overtimeStartTime = Carbon::parse($dateString . ' ' . $overtime->start_time);

        // ==========================================================
        // [LOGIC BARU] STRICT MODE: TOLAK JIKA LEMBUR SUDAH MULAI
        // ==========================================================
        // Jika waktu sekarang >= waktu mulai lembur, maka TOLAK.
        if ($approvalTime->greaterThanOrEqualTo($overtimeStartTime)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'GAGAL: Lembur sudah dimulai! Approval harus dilakukan sebelum jam ' . $overtime->start_time
            ], 400);
        }
        // ==========================================================

        // 2. Hitung Waktu Selesai & Expired Voucher
        $overtimeEndTime = Carbon::parse($dateString . ' ' . $overtime->end_time);
        
        // Cek Cross Day (Lembur lintas hari, misal mulai 23:00 selesai 03:00)
        $start = Carbon::parse($overtime->start_time);
        $end   = Carbon::parse($overtime->end_time);
        
        if ($start->gt($end)) {
            $overtimeEndTime->addDay();
        }

        // Karena Strict Mode (Pasti diapprove sebelum mulai), 
        // Logic expired voucher jadi simpel: Selalu "Jam Selesai + 4 Jam".
        $expiredAt = $overtimeEndTime->copy()->addHours(4);

        // 3. Update Status Overtime
        $overtime->update([
            'status'      => 'APPROVED',
            'approved_at' => $approvalTime,
            'expired_at'  => $expiredAt
        ]);

        // 4. GENERATE VOUCHER OTOMATIS (Jika Eligible)
        if ($overtime->is_eligible_for_voucher) {
            
            // Buat Kode Unik
            $uniqueCode = 'VCH-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            \App\Models\Voucher::create([
                'overtime_request_id' => $overtime->id,
                'employee_id'         => $overtime->employee_id,
                'code'                => $uniqueCode,
                'status'              => 'AVAILABLE',
                'expired_at'          => $expiredAt, // Expired sesuai perhitungan di atas
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Lembur disetujui & Voucher berhasil dikirim ke karyawan.',
            'data' => $overtime
        ]);
    }
    
    public function bulkStore(Request $request)
    {
        return response()->json(['message' => 'Bulk insert success'], 200);
    }
}