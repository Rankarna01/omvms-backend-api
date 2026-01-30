<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes; // WAJIB: Tambahkan ini untuk SoftDeletes

class User extends Authenticatable
{
    // Tambahkan SoftDeletes di sini karena di migration pakai $table->softDeletes();
    use HasApiTokens, HasFactory, Notifiable; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nik', 
        'email',
        'password',
        'role',
        'department_id', // <--- Ganti 'department' jadi 'department_id'
        'employee_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==========================================
    // RELASI
    // ==========================================

    // 1. Relasi ke Employee (Data Diri Personal)
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // 2. Relasi ke Department (Penempatan Kerja)
    public function department()
    {
        // belongsTo akan otomatis mencari kolom 'department_id' di tabel users
        return $this->belongsTo(Department::class);
        return $this->belongsTo(Department::class, 'department_id');
    }
}