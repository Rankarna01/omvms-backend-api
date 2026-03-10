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

        $voucher = Voucher::with(['employee.department'])->where('code', $code)->first();

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
                'action_type'=> $actionType, // PENTING UNTUK FRONTEND
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
                'redeemed_at' => $now, // Tetap isi kapan voucher di-redeem penuh
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

    /**
     * Langkah 3: Ambil Riwayat Scan untuk Dashboard POS
     */
    public function history(Request $request)
    {
        // Ambil semua voucher yang sudah diproses di kantin (Bukan AVAILABLE dan Bukan EXPIRED yang belum diapa-apain)
        $vouchers = Voucher::with(['employee.department'])
            ->whereIn('status', ['ON_BREAK', 'REDEEMED', 'OVERBREAK', 'REJECTED'])
            ->orderBy('updated_at', 'desc') // Urutkan dari aktivitas terbaru
            ->get()
            ->map(function ($v) {
                // Parsing waktu
                $checkin = $v->checkin_at ? Carbon::parse($v->checkin_at) : null;
                $checkout = $v->checkout_at ? Carbon::parse($v->checkout_at) : null;
                
                // Hitung durasi jika ada checkin dan checkout
                $duration = null;
                if ($checkin && $checkout) {
                    $duration = $checkin->diffInMinutes($checkout);
                }

                return [
                    'id' => $v->id,
                    // Format waktu menjadi YYYY-MM-DD HH:mm agar mudah difilter di frontend
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