<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Voucher;
use Carbon\Carbon;

class EmployeeVoucherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Validasi: Pastikan user terhubung dengan data employee
        if (!$user->employee_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda tidak terhubung dengan data karyawan.'
            ], 403);
        }

        // Ambil voucher milik karyawan ini
        // Kita pisahkan yang ACTIVE (Available) ditaruh paling atas
        $vouchers = Voucher::with(['overtimeRequest'])
            ->where('employee_id', $user->employee_id)
            ->orderByRaw("FIELD(status, 'AVAILABLE') DESC") // Prioritas status AVAILABLE
            ->orderBy('created_at', 'desc')
            ->get();

        // Logic Auto-Expired (Sama seperti Admin, kita cek on-the-fly)
        $now = Carbon::now();
        $vouchers->transform(function ($voucher) use ($now) {
            if ($voucher->status === 'AVAILABLE' && $now->gt($voucher->expired_at)) {
                $voucher->status = 'EXPIRED';
                $voucher->save();
            }
            return $voucher;
        });

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }
}