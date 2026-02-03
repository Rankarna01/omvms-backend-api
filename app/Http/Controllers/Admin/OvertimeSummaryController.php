<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Carbon\Carbon;

class OvertimeSummaryController extends Controller
{
    /**
     * Menampilkan ringkasan jam lembur mingguan semua karyawan
     * untuk monitoring batas 18 jam (UU Ketenagakerjaan).
     */
    public function index(Request $request)
    {
        // 1. Tentukan Range Minggu Ini (Senin - Minggu)
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek()->format('Y-m-d');
        $endOfWeek   = $now->copy()->endOfWeek()->format('Y-m-d');

        // 2. Query Data Karyawan + Total Jam Lembur Minggu Ini
        // Menggunakan withSum agar performa cepat (tidak N+1 problem)
        $employees = Employee::with(['shift']) // Load data shift
            ->withSum(['overtimeRequests as weekly_hours_used' => function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('date', [$startOfWeek, $endOfWeek])
                      // Hanya hitung yang diajukan (SUBMITTED) dan disetujui (APPROVED)
                      // REJECTED tidak dihitung.
                      ->whereIn('status', ['SUBMITTED', 'APPROVED']);
            }], 'duration')
            
            // Opsional: Filter pencarian nama karyawan
            ->when($request->search, function ($query, $search) {
                return $query->where('full_name', 'like', "%{$search}%")
                             ->orWhere('employee_id_number', 'like', "%{$search}%"); // NIP/NIK
            })
            
            // Urutkan dari yang jam lemburnya paling banyak (Prioritas Monitoring)
            ->orderByDesc('weekly_hours_used')
            ->paginate(10); // Pagination 10 per halaman

        // 3. Transform Data untuk Frontend
        // Kita tambahkan status visual (Safe, Warning, Danger) agar Frontend tinggal render
        $employees->getCollection()->transform(function ($emp) {
            $limit = 18; // Limit regulasi
            $used  = (float) $emp->weekly_hours_used ?? 0; // Handle jika null jadi 0
            $remaining = max(0, $limit - $used);
            $percentage = ($used / $limit) * 100;

            // Logic Status Warna
            $status = 'SAFE'; // Hijau
            if ($used >= 18) {
                $status = 'DANGER'; // Merah (Mentok)
            } elseif ($used >= 14) {
                $status = 'WARNING'; // Kuning (Hampir Mentok)
            }

            return [
                'id'          => $emp->id,
                'name'        => $emp->full_name,
                'nip'         => $emp->employee_id_number ?? '-', // NIP / NIK
                'position'    => $emp->position,
                'shift_name'  => $emp->shift->shift_name ?? 'Non-Shift',
                'join_date'   => $emp->join_date ? Carbon::parse($emp->join_date)->format('d M Y') : '-',
                
                // Data Monitoring
                'weekly_limit' => $limit,
                'hours_used'   => $used,
                'hours_remaining' => $remaining,
                'percentage'   => round($percentage, 1),
                'status_level' => $status, // SAFE, WARNING, DANGER
                'is_locked'    => $used >= $limit // Flag untuk disable tombol add di frontend
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data summary mingguan berhasil dimuat.',
            'meta' => [
                'period_start' => Carbon::parse($startOfWeek)->format('d M Y'),
                'period_end'   => Carbon::parse($endOfWeek)->format('d M Y'),
            ],
            'data' => $employees
        ]);
    }
}