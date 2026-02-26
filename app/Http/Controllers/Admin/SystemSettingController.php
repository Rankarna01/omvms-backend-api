<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin_system') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $settings = SystemSetting::first();

        if (!$settings) {
            $settings = SystemSetting::create([
                'voucher_expiry_hours' => 24,
                'minimum_overtime_hours' => 4,
                'canteen_open_time' => '08:00:00',
                'canteen_close_time' => '20:00:00',
                'email_notifications' => true,
                'push_notifications' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin_system') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'voucher_expiry_hours' => 'required|integer|min:1',
            'minimum_overtime_hours' => 'required|integer|min:1',
            'canteen_open_time' => 'required|date_format:H:i',
            'canteen_close_time' => 'required|date_format:H:i',
            'email_notifications' => 'required|boolean',
            'push_notifications' => 'required|boolean',
        ]);

        $settings = SystemSetting::first();

        if (!$settings) {
            $settings = SystemSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'System settings updated successfully.',
            'data' => $settings
        ]);
    }
}