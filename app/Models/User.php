<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    // Tambahkan SoftDeletes jika di migration pakai $table->softDeletes();
    use HasApiTokens, HasFactory, Notifiable; 

    /**
     * The attributes that are mass assignable.
     
     * @var array<int, string>
     */
    protected $fillable = [
    'name',
    'nik', // <--- Ganti 'username' jadi 'nik'
    'email',
    'password',
    'role',
    'department',
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
    // RELASI (WAJIB ADA agar Controller jalan)
    // ==========================================

    // Relasi ke tabel Employees
    // Dipanggil oleh: User::with('employee') di controller
    // Relasi ke Employee (Data Diri)
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Relasi ke Department (Hak Akses Departemen)
    // TAMBAHKAN INI 👇
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}