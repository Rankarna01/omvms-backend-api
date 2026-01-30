<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;

class EmployeeOvertimeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->employee_id) {
            return response()->json(['message' => 'User tidak terhubung dengan data karyawan'], 403);
        }

        // Ambil data lembur milik karyawan ini
        $data = OvertimeRequest::where('employee_id', $user->employee_id)
            ->orderBy('date', 'desc') // Urutkan dari yang terbaru
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}