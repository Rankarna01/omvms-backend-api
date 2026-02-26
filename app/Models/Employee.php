<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
    'nik', 
    'full_name', 
    'email', // <--- WAJIB ADA
    'phone', 
    'department_id',
    'shift_id', 
    'position', 
    'join_date', 
    'is_active',
    'avatar'
];
    // Relasi ke Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // 👇 TAMBAHKAN INI (PENTING AGAR TIDAK ERROR 500)
    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function overtimeRequests()
{
    return $this->hasMany(OvertimeRequest::class, 'employee_id');
}
public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'employee_id');
    }
}