<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_name',
        'start_time',
        'end_time',
        'allow_meal',    
        'lock_request',  
        'description'
    ];

    protected $casts = [
        'allow_meal' => 'boolean',
        'lock_request' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}