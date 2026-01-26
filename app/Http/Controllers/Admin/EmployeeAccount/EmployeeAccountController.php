<?php

namespace App\Http\Controllers\Admin\EmployeeAccount;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'user']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(20)
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validasi: Cuma butuh ID Karyawan & Password
        $request->validate([
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'password'    => 'required|min:6'
        ]);

        // 2. Ambil Data Karyawan
        $employee = Employee::with('department')->find($request->employee_id);
        
        // Cek Department
        $deptName = $employee->department ? $employee->department->dept_name : '-';

        // 3. AUTO GENERATE Username (NIK) & Email (Dummy)
        // Kita gunakan NIK sebagai username login
        $nik = $employee->nik; 
        
        $user = User::create([
            'name'          => $employee->full_name,
            'username'      => $nik,                // <--- Otomatis dari NIK
            'email'         => $nik . '@omvms.com', // <--- Otomatis Dummy Email
            'password'      => Hash::make($request->password),
            'role'          => 'employee',
            'department'    => $deptName, 
            'employee_id'   => $employee->id,
            'is_active'     => true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dibuat. Login menggunakan NIK.',
            'data' => $user
        ], 201);
    }

    public function destroy($employee_id)
    {
        $user = User::where('employee_id', $employee_id)->first();
        
        if(!$user) {
             return response()->json(['status' => 'error', 'message' => 'Account not found'], 404);
        }
        
        $user->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Account access revoked'
        ]);
    }
}   