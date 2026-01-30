<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = ['id'];
    
    // Relasi
    public function overtimeRequest()
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    // [TAMBAHKAN INI] Relasi ke Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
        return $this->belongsTo(User::class, 'user_id');
    }
}
