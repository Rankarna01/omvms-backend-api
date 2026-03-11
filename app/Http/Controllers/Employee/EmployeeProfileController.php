<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\Voucher;
use Carbon\Carbon;

class EmployeeProfileController extends Controller
{
    /**
     * Ambil data profil lengkap dengan Departemen
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Load data employee beserta relasi department-nya
        $employee = Employee::with('department')->find($user->employee_id);

        if (!$employee) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan'], 404);
        }

        return response()->json([
            'status'  => 'success',
            'data'    => [
                'id'         => $employee->id,
                'nik'        => $employee->nik,
                'full_name'  => $employee->full_name,
                'email'      => $employee->email,
                'avatar'     => $employee->avatar ? asset('storage/' . $employee->avatar) : null,
                'department' => $employee->department ? $employee->department->dept_name : '-',
            ]
        ]);
    }

    /**
     * Update Avatar (Upload Foto)
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();
        $employee = Employee::find($user->employee_id);

        if ($request->hasFile('avatar')) {
            if ($employee->avatar) {
                Storage::disk('public')->delete($employee->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $employee->update(['avatar' => $path]);

            return response()->json([
                'status' => 'success',
                'url' => asset('storage/' . $path)
            ]);
        }
    }

    /**
     * Ubah Password Sendiri
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama tidak sesuai'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diubah'
        ]);
    }

    /**
     * Ambil data ringkasan untuk halaman Dashboard Karyawan
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $employee = Employee::with('department')->find($user->employee_id);

        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Data Karyawan tidak ditemukan.'], 404);
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        
        // 1. Cek Voucher Hari Ini (Untuk Timer Istirahat)
        $todayVoucher = Voucher::where('employee_id', $employee->id)
            ->whereDate('created_at', $today)
            ->first();

        // 2. Hitung Voucher Ready (Status AVAILABLE)
        $voucherReadyCount = Voucher::where('employee_id', $employee->id)
            ->where('status', 'AVAILABLE')
            ->count();

        // 3. Hitung Pengajuan Lembur Pending (SUBMITTED)
        $pendingApprovalCount = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'SUBMITTED')
            ->count();

        // 4. Hitung Total Jam Lembur Bulan Ini (APPROVED)
        $monthlyOvertimeHours = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$startOfMonth, Carbon::now()->endOfMonth()])
            ->sum('duration');

        // 5. Cari Tanggal Lembur Terakhir
        $lastOvertime = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'APPROVED')
            ->whereDate('date', '<', $today)
            ->orderBy('date', 'desc')
            ->first();

        // 6. Cari Jadwal Lembur Khusus HARI INI (Reset Harian)
        $todayOvertime = OvertimeRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['APPROVED', 'SUBMITTED'])
            ->whereDate('date', $today)
            ->orderBy('start_time', 'asc')
            ->first();

        $nextScheduleFormatted = null;
        if ($todayOvertime) {
            $nextScheduleFormatted = [
                'id' => $todayOvertime->id,
                'date' => Carbon::parse($todayOvertime->date)->format('Y-m-d'),
                'isToday' => true, 
                'startTime' => $todayOvertime->start_time ? Carbon::parse($todayOvertime->start_time)->format('H:i') : '-',
                'endTime' => $todayOvertime->end_time ? Carbon::parse($todayOvertime->end_time)->format('H:i') : '-',
                'duration' => $todayOvertime->duration . ' Jam',
                'isMealEligible' => $todayOvertime->duration >= 3,
                'status' => $todayOvertime->status,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'name' => $employee->full_name,
                    'role' => $employee->position ?? 'Staff',
                    'department' => $employee->department ? $employee->department->dept_name : 'Umum',
                ],
                'voucherReadyCount' => $voucherReadyCount,
                'activeVoucherStatus' => $todayVoucher ? $todayVoucher->status : null,
                'activeVoucherCheckinAt' => ($todayVoucher && $todayVoucher->checkin_at) 
                                            ? Carbon::parse($todayVoucher->checkin_at)->format('Y-m-d H:i:s') 
                                            : null,
                'pendingApprovalCount' => $pendingApprovalCount,
                'monthlyOvertimeHours' => (int) $monthlyOvertimeHours,
                'lastOvertimeDate' => $lastOvertime ? Carbon::parse($lastOvertime->date)->diffForHumans() : 'Belum ada',
                'nextSchedule' => $nextScheduleFormatted,
            ]
        ]);
    }
}