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
            'duration'    => 'required|numeric|min:0.5', 
            'reason'      => 'required|string',
        ]);

        $hasExistingOvertime = OvertimeRequest::where('employee_id', $request->employee_id)
            ->where('date', $request->date)
            ->whereIn('status', ['SUBMITTED', 'APPROVED', 'REDEEMED'])
            ->exists();

        if ($hasExistingOvertime) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan ini sudah memiliki jadwal lembur pada tanggal tersebut. Anda hanya diperbolehkan membuat 1 jadwal lembur per karyawan dalam 1 hari.'
            ], 422);
        }
        // =================================================================

        $employee = Employee::with('shift')->findOrFail($request->employee_id);
        $durationInput = (float) $request->duration;
        $cleanDate = Carbon::parse($request->date)->format('Y-m-d');
        $start = Carbon::parse($cleanDate . ' ' . $request->start_time);
        $end = $start->copy()->addMinutes($durationInput * 60);

        // LOGIC 2: JAM LEMBUR HARUS SETELAH SHIFT
        if ($employee->shift) {
            $shiftEndTime = Carbon::parse($cleanDate . ' ' . $employee->shift->end_time);

            // Jika lembur dimulai sebelum shift berakhir -> TOLAK
            if ($start->lt($shiftEndTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lembur hanya bisa dimulai setelah jam shift berakhir (' . $shiftEndTime->format('H:i') . ').'
                ], 422);
            }
        }

        // =================================================================
        // LOGIC 3: CEK LIMIT MINGGUAN (Maksimal 40 Jam / Minggu)
        // =================================================================
        $requestDate = Carbon::parse($request->date);
        $startOfWeek = $requestDate->copy()->startOfWeek();
        $endOfWeek   = $requestDate->copy()->endOfWeek();

        $weeklyHours = OvertimeRequest::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->whereIn('status', ['SUBMITTED', 'APPROVED'])
            ->sum('duration');

        if (($weeklyHours + $durationInput) > 40) {
            return response()->json([
                'status' => 'error',
                'message' => "Batas lembur mingguan terlampaui! (Saat ini: $weeklyHours jam + Baru: $durationInput jam > 40 jam)"
            ], 422);
        }
        // =================================================================

        // =================================================================
        // LOGIC 4: GENERATE UNIQUE CODE (CORPORATE SEQUENCE)
        // Format: OVT-[YYMMDD]-[0001] (Contoh: OVT-260311-0001)
        // =================================================================

        // 1. Ambil format tanggal YYMMDD (Tahun 2 digit, Bulan 2 digit, Hari 2 digit)
        $datePrefix = Carbon::parse($request->date)->format('ymd');

        // 2. Hitung total pengajuan lembur (dari semua orang) di tanggal tersebut
        $countToday = OvertimeRequest::whereDate('date', $request->date)->count();

        // 3. Tambah 1 untuk mendapatkan urutan selanjutnya
        $nextSequence = $countToday + 1;

        // 4. Format urutan menjadi 4 digit (misal: "1" menjadi "0001")
        $sequenceFormatted = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        // 5. Rangkai kode utuhnya
        $overtimeCode = "OVT-{$datePrefix}-{$sequenceFormatted}";
        // =================================================================

        // LOGIC 5: VOUCHER ELIGIBILITY (Tetap minimal 4 jam untuk dapat makan)
        $isEligible = ($employee->shift && $employee->shift->allow_meal && $durationInput >= 4);

        $overtime = OvertimeRequest::create([
            'overtime_code' => $overtimeCode, // Masukkan kode yang sudah di-generate dengan format baru
            'employee_id'   => $request->employee_id,
            'date'          => $request->date,
            'start_time'    => $start->format('H:i:s'),
            'end_time'      => $end->format('H:i:s'), // Hasil kalkulasi end_time
            'duration'      => $durationInput,
            'reason'        => $request->reason,
            'is_eligible_for_voucher' => $isEligible,
            'status'        => 'SUBMITTED',
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

        // Sama seperti store, validasi durasi
        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'duration'    => 'required|numeric|min:0.5',
            'reason'      => 'required|string',
        ]);

        $durationInput = (float) $request->duration;
        $start = Carbon::parse($request->date . ' ' . $request->start_time);
        $end = $start->copy()->addMinutes($durationInput * 60);

        // RE-CHECK WEEKLY LIMIT ON UPDATE
        $employee = Employee::with('shift')->find($overtime->employee_id);

        $requestDate = Carbon::parse($request->date);
        $startOfWeek = $requestDate->copy()->startOfWeek();
        $endOfWeek   = $requestDate->copy()->endOfWeek();

        $weeklyHours = OvertimeRequest::where('employee_id', $employee->id)
            ->where('id', '!=', $id)
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->whereIn('status', ['SUBMITTED', 'APPROVED'])
            ->sum('duration');

        if (($weeklyHours + $durationInput) > 40) {
            return response()->json([
                'status' => 'error',
                'message' => "Update Gagal: Batas lembur mingguan terlampaui ($weeklyHours + $durationInput > 40)."
            ], 422);
        }

        $isEligible = ($employee->shift && $employee->shift->allow_meal && $durationInput >= 4);

        $overtime->update([
            'date'        => $request->date,
            'start_time'  => $start->format('H:i:s'),
            'end_time'    => $end->format('H:i:s'),
            'duration'    => $durationInput,
            'reason'      => $request->reason,
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

        if ($overtime->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Status request tidak valid untuk diapprove.'], 400);
        }

        $approvalTime = Carbon::now();
        $dateString = $overtime->date;

        $cleanDate = Carbon::parse($dateString)->format('Y-m-d');
        $overtimeStartTime = Carbon::parse($cleanDate . ' ' . $overtime->start_time);

        // STRICT MODE
        if ($approvalTime->greaterThanOrEqualTo($overtimeStartTime)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'GAGAL: Lembur sudah dimulai! Approval harus dilakukan sebelum jam ' . $overtime->start_time
            ], 400);
        }

        // Hitung End Time
        $overtimeEndTime = Carbon::parse($cleanDate . ' ' . $overtime->end_time);

        $start = Carbon::parse($overtime->start_time);
        $end   = Carbon::parse($overtime->end_time);

        // Handle Cross Day (jika selesai besok harinya)
        if ($end->lt($start)) {
            $overtimeEndTime->addDay();
        }

        $expiredAt = $overtimeEndTime->copy()->addHours(4);

        $overtime->update([
            'status'      => 'APPROVED',
            'approved_at' => $approvalTime,
            'expired_at'  => $expiredAt
        ]);

        // ==========================================================
        // CREATE VOUCHER OTOMATIS JIKA ELIGIBLE
        // ==========================================================
        if ($overtime->is_eligible_for_voucher) {

            // 1. Dapatkan ID Karyawan dan pastikan formatnya 2 digit
            $employeeIdFormatted = str_pad($overtime->employee_id, 2, '0', STR_PAD_LEFT);

            // 2. Dapatkan Tanggal Generate Voucher format DDMMYY
            $dateFormatted = Carbon::now()->format('dmy');

            // 3. Rangkai kode utuh: VCH-ID-TANGGAL-OVERTIME_ID
            // Menambahkan ID Overtime menjamin voucher ini 100% unik
            $voucherCode = "VCH-{$employeeIdFormatted}-{$dateFormatted}-{$overtime->id}";

            \App\Models\Voucher::create([
                'overtime_request_id' => $overtime->id,
                'employee_id'         => $overtime->employee_id,
                'code'                => $voucherCode, // <-- Kode unik terupdate
                'status'              => 'AVAILABLE',
                'expired_at'          => $expiredAt,
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

    public function getEmployeesForForm(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['admin_dept', 'head_dept']) && $user->department_id) {
            $employees = Employee::where('department_id', $user->department_id)->get();
        } else {
            if ($user->role === 'admin_system') {
                $employees = Employee::all();
            } else {
                return response()->json(['message' => 'Unauthorized access.'], 403);
            }
        }

        return response()->json(['status' => 'success', 'data' => $employees]);
    }
}