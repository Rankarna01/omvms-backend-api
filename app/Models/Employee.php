<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nik', 'full_name', 'phone', 'department_id', 'position', 'join_date', 'photo', 'is_active'
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
}