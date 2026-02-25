<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = ['id'];
    
    // Relasi ke Overtime Request
    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    // Relasi ke Employee (SUDAH DIPERBAIKI)
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // [OPSIONAL] Jika Anda memang butuh relasi ke User, pisahkan function-nya:
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}