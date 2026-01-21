<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'dept_code', 
        'dept_name', 
        'description', 
        'is_active'
    ];

    // Relasi ke Employee (Agar bisa dihitung jumlahnya)
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}