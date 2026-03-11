<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;

class PosScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $code = $request->code;

        // Tambahkan relasi overtimeRequest agar kita bisa mengecek jamnya
        $voucher = Voucher::with(['employee.department', 'overtimeRequest'])->where('code', $code)->first();

        if (!$voucher) {
            return response()->json(['status' => 'error', 'message' => 'Kode Voucher Tidak Ditemukan.'], 404);
        }

        // Cek kadaluarsa HANYA jika voucher masih AVAILABLE
        if ($voucher->status === 'AVAILABLE' && Carbon::now()->greaterThan($voucher->expired_at)) {
             $voucher->update(['status' => 'EXPIRED']);
             return response()->json(['status' => 'error', 'message' => 'Voucher Sudah Kadaluarsa.'], 400);
        }

        // Blokir status yang sudah selesai
        if (in_array($voucher->status, ['REDEEMED', 'OVERBREAK', 'EXPIRED'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher Tidak Valid (Status: ' . $voucher->status . ').',
            ], 400);
        }

        // ====================================================================
        // ✨ VALIDASI JAM LEMBUR ✨
        // Hanya divalidasi saat Karyawan mau Check-In (Status AVAILABLE)
        // ====================================================================
        if ($voucher->status === 'AVAILABLE' && $voucher->overtimeRequest) {
            $now = Carbon::now();
            $overtime = $voucher->overtimeRequest;
            
            // Gabungkan tanggal dengan jam lembur
            $startTime = Carbon::parse($overtime->date . ' ' . $overtime->start_time);
            $endTime = Carbon::parse($overtime->date . ' ' . $overtime->end_time);

            // Handle jika jam lembur melewati tengah malam (misal 22:00 - 02:00)
            if ($endTime->lessThan($startTime)) {
                $endTime->addDay();
            }

            // Jika waktu scan (sekarang) berada DI LUAR rentang jam lembur
            if (!$now->between($startTime, $endTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Di luar jam kerja! Voucher hanya aktif pada: ' . $startTime->format('H:i') . ' - ' . $endTime->format('H:i') . ' WIB.',
                ], 400);
            }
        }
        // ====================================================================

        $employee = $voucher->employee;
        $photoUrl = ($employee && !empty($employee->avatar)) 
            ? asset('storage/' . $employee->avatar) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($employee->full_name ?? 'User') . '&background=0D8ABC&color=fff&size=256';

        // Tentukan ini proses Check-In atau Check-Out
        $actionType = ($voucher->status === 'AVAILABLE') ? 'CHECK_IN' : 'CHECK_OUT';
        $message = ($actionType === 'CHECK_IN') 
            ? 'Voucher Valid. Silakan verifikasi untuk mulai istirahat.' 
            : 'Karyawan sedang istirahat. Silakan verifikasi untuk Check-Out.';

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'voucher_id' => $voucher->id,
                'code'       => $voucher->code,
                'action_type'=> $actionType,
                'name'       => $employee->full_name ?? 'Unknown',
                'nik'        => $employee->nik ?? '-',
                'dept'       => $employee->department->dept_name ?? 'Unknown Dept',
                'photoUrl'   => $photoUrl,
                'checkin_at' => $voucher->checkin_at ? Carbon::parse($voucher->checkin_at)->format('H:i:s') : null
            ]
        ]);
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'status'     => 'required|in:valid,invalid', 
        ]);

        if ($request->status === 'invalid') {
            return response()->json(['status' => 'success', 'message' => 'Verifikasi Ditolak Oleh Petugas.']);
        }

        $voucher = Voucher::with(['employee.department'])->find($request->voucher_id);
        $now = Carbon::now();

        // LOGIKA CHECK-IN (Mulai Istirahat)
        if ($voucher->status === 'AVAILABLE') {
            $voucher->update([
                'status' => 'ON_BREAK',
                'checkin_at' => $now
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Check-in Berhasil. Waktu istirahat 1 Jam dimulai.',
                'data' => [
                    'action' => 'CHECK_IN',
                    'name' => $voucher->employee->full_name ?? 'Unknown',
                    'time' => $now->format('H:i:s'),
                ]
            ]);
        } 
        
        // LOGIKA CHECK-OUT (Selesai Istirahat)
        elseif ($voucher->status === 'ON_BREAK') {
            $checkinTime = Carbon::parse($voucher->checkin_at);
            $diffInMinutes = $checkinTime->diffInMinutes($now);
            
            // Tentukan apakah telat (> 60 menit)
            $isLate = $diffInMinutes > 60;
            $newStatus = $isLate ? 'OVERBREAK' : 'REDEEMED';

            $voucher->update([
                'status' => $newStatus,
                'checkout_at' => $now,
                'redeemed_at' => $now,
                'is_late' => $isLate
            ]);

            $message = $isLate 
                ? "Check-out Telat! Durasi istirahat: {$diffInMinutes} menit." 
                : "Check-out Tepat Waktu. Durasi: {$diffInMinutes} menit.";

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'action' => 'CHECK_OUT',
                    'name' => $voucher->employee->full_name ?? 'Unknown',
                    'duration_minutes' => $diffInMinutes,
                    'is_late' => $isLate,
                    'time' => $now->format('H:i:s'),
                ]
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Status voucher tidak valid untuk diproses.'], 400);
    }

    public function history(Request $request)
    {
        $vouchers = Voucher::with(['employee.department'])
            ->whereIn('status', ['ON_BREAK', 'REDEEMED', 'OVERBREAK', 'REJECTED'])
            ->orderBy('updated_at', 'desc') 
            ->get()
            ->map(function ($v) {
                $checkin = $v->checkin_at ? Carbon::parse($v->checkin_at) : null;
                $checkout = $v->checkout_at ? Carbon::parse($v->checkout_at) : null;
                
                $duration = null;
                if ($checkin && $checkout) {
                    $duration = $checkin->diffInMinutes($checkout);
                }

                return [
                    'id' => $v->id,
                    'checkin' => $checkin ? $checkin->format('Y-m-d H:i') : null,
                    'checkout' => $checkout ? $checkout->format('Y-m-d H:i') : null,
                    'duration' => $duration,
                    'voucher' => $v->code,
                    'name' => $v->employee->full_name ?? 'Unknown',
                    'dept' => $v->employee->department->dept_name ?? '-',
                    'status' => $v->status,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }
}