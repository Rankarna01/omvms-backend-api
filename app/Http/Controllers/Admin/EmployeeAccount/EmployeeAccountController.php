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
        $request->validate([
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'password'    => 'required|min:6'
        ]);

        // 1. Ambil Data Karyawan (Pastikan kolom email terambil)
        $employee = Employee::with('department')->find($request->employee_id);
        
        $deptName = $employee->department ? $employee->department->dept_name : '-';
        $nikAsli  = $employee->nik; 
        
        // 2. LOGIKA BARU: Cek apakah karyawan punya email asli?
        // Jika ada, pakai email asli. Jika kosong, baru generate dummy.
        $emailUser = $employee->email ? $employee->email : ($nikAsli . '@omvms.com');

        $user = User::create([
            'name'        => $employee->full_name,
            'nik'         => $nikAsli,
            
            // --- PERBAIKAN DISINI ---
            'email'       => $emailUser, // Pakai variable yang sudah dicek di atas
            
            'password'    => Hash::make($request->password),
            'role'        => 'employee',
            'department'  => $deptName, 
            'employee_id' => $employee->id,
            'is_active'   => true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dibuat.',
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