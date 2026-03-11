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

        if (!$user->employee_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda tidak terhubung dengan data karyawan.'
            ], 403);
        }

        // 🌟 PERUBAHAN: Prioritaskan ON_BREAK (sedang dipakai) dan AVAILABLE (belum dipakai)
        $vouchers = Voucher::with(['overtimeRequest'])
            ->where('employee_id', $user->employee_id)
            ->orderByRaw("FIELD(status, 'ON_BREAK', 'AVAILABLE') DESC") 
            ->orderBy('created_at', 'desc')
            ->get();

        $now = Carbon::now();
        $vouchers->transform(function ($voucher) use ($now) {
            // Hanya expire-kan yang statusnya masih AVAILABLE
            // Yang sedang ON_BREAK aman dari auto-expired
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