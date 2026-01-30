<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;

class PosScanController extends Controller
{
    /**
     * Langkah 1: Scan Kode Voucher
     * Mengecek validitas voucher dan mengembalikan data karyawan untuk verifikasi visual.
     */
    public function scan(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->code;

        // 2. Cari Voucher & Load Relasi Karyawan
        $voucher = Voucher::with(['employee.department'])
            ->where('code', $code)
            ->first();

        // 3. Cek Ketersediaan Voucher
        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode Voucher Tidak Ditemukan.',
            ], 404);
        }

        // Cek apakah sudah dipakai
        if ($voucher->status === 'REDEEMED') {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher Sudah Digunakan.',
                'data' => [
                    'redeemed_at' => $voucher->redeemed_at,
                ]
            ], 400);
        }

        // Cek apakah kadaluarsa
        if ($voucher->status === 'EXPIRED' || Carbon::now()->greaterThan($voucher->expired_at)) {
             return response()->json([
                'status' => 'error',
                'message' => 'Voucher Sudah Kadaluarsa.',
            ], 400);
        }
        
        // Cek status lain (misal CANCELLED)
         if ($voucher->status !== 'AVAILABLE') {
             return response()->json([
                'status' => 'error',
                'message' => 'Voucher Tidak Valid (Status: ' . $voucher->status . ').',
            ], 400);
        }

        // 4. Return Data Valid untuk Verifikasi Wajah di Frontend
        return response()->json([
            'status' => 'success',
            'message' => 'Voucher Valid. Silakan Verifikasi Wajah.',
            'data' => [
                'voucher_id' => $voucher->id,
                'code'      => $voucher->code,
                // Menggunakan null coalescing operator (??) untuk keamanan data
                'name'      => $voucher->employee->name ?? 'Unknown',
                'nik'       => $voucher->employee->nik ?? '-',
                'email'     => $voucher->employee->email ?? '-',
                'dept'      => $voucher->employee->department->name ?? 'Unknown Dept',
                // Placeholder avatar jika foto kosong
                'photoUrl'  => $voucher->employee->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($voucher->employee->name ?? 'User') . '&background=0D8ABC&color=fff'
            ]
        ]);
    }

    /**
     * Langkah 2: Redeem / Approve Voucher
     * Dipanggil setelah petugas POS menekan tombol "SESUAI (Approve)".
     */
    public function redeem(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'status'     => 'required|in:valid,invalid', // valid = approve, invalid = reject
        ]);

        // Cari voucher & load relasi untuk response
        $voucher = Voucher::with(['employee.department'])->find($request->voucher_id);

        // 2. Proses Redeem
        if ($request->status === 'valid') {
            
            // Pastikan voucher belum diredeem orang lain di detik yang sama (Race condition check)
            if ($voucher->status !== 'AVAILABLE') {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal! Voucher status telah berubah menjadi ' . $voucher->status,
                ], 400);
            }

            // Update Database
            $voucher->status = 'REDEEMED';
            $voucher->redeemed_at = Carbon::now();
            $voucher->redeemed_by = auth()->id(); // ID akun POS yang login
            $voucher->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Voucher Berhasil Di-redeem.',
                'data' => [
                     'name' => $voucher->employee->name ?? 'Unknown',
                     'dept' => $voucher->employee->department->name ?? 'Unknown',
                     'time' => $voucher->redeemed_at->format('H:i:s'),
                ]
            ]);
        } else {
            // 3. Proses Reject (Wajah Tidak Sesuai)
            // Kita tidak mengubah status voucher (tetap AVAILABLE) agar pemilik asli bisa pakai,
            // tapi kita return success agar UI POS kembali standby.
            return response()->json([
                'status' => 'success',
                'message' => 'Verifikasi Ditolak Oleh Petugas.',
            ]);
        }
    }
}