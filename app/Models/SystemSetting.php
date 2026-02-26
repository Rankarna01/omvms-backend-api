<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_expiry_hours',
        'minimum_overtime_hours',
        'canteen_open_time',
        'canteen_close_time',
        'email_notifications',
        'push_notifications',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];
}