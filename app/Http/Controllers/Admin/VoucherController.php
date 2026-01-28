<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        // Ambil semua voucher dengan relasi employee
        $vouchers = Voucher::with('employee')->latest()->get();

        // LOGIC CHECK EXPIRED ON-THE-FLY
        // Sebelum dikirim ke frontend, kita cek dulu apakah ada yang sudah expired tapi status masih AVAILABLE
        $now = Carbon::now();
        
        $vouchers->transform(function ($voucher) use ($now) {
            // Jika status masih AVAILABLE tapi waktu sekarang sudah lewat expired_at
            if ($voucher->status === 'AVAILABLE' && $now->gt($voucher->expired_at)) {
                $voucher->status = 'EXPIRED';
                $voucher->save(); // Update database
            }
            return $voucher;
        });

        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }
}