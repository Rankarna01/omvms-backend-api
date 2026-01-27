<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory;

    // Tabel yang terkait (Opsional jika nama tabel sesuai standar plural: overtime_requests)
    protected $table = 'overtime_requests';

    // Kolom yang boleh diisi secara massal (create/update)
    protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'is_eligible_for_voucher',
        'reason',
        'status',       // 'DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'
        'approved_at',
        'expired_at'
    ];

    // Casting tipe data agar otomatis berubah saat keluar dari database (JSON)
    protected $casts = [
        'date' => 'date:Y-m-d',              // Format tanggal konsisten
        'is_eligible_for_voucher' => 'boolean', // 0/1 jadi true/false di Frontend
        'approved_at' => 'datetime',
        'expired_at' => 'datetime',
        'duration' => 'integer',             // Pastikan durasi dianggap angka
    ];

    /**
     * RELASI: OvertimeRequest milik satu Employee
     * Ini PENTING agar di Controller bisa pakai ->with('employee')
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}