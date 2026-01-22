<?php

namespace App\Http\Controllers\Admin\DepartmentAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DepartmentAccountController extends Controller
{
    // GET: List akun Admin Dept & Head Dept
    public function index(Request $request)
    {
        $query = User::with('employee.department')
                     ->whereIn('role', ['admin_dept', 'head_dept']); // Filter 2 role ini

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(10)
        ]);
    }

    // POST: Buat Akun Departemen
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id',
            'username'    => 'required|unique:users,username',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6',
            'role'        => 'required|in:admin_dept,head_dept', // Pilih salah satu
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        $user = User::create([
            'name'        => $employee->full_name,
            'username'    => $validated['username'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => $validated['role'], // admin_dept atau head_dept
            'employee_id' => $validated['employee_id'],
            'is_active'   => true
        ]);

        return response()->json(['status' => 'success', 'message' => 'Akun departemen berhasil dibuat', 'data' => $user], 201);
    }

    public function destroy($id)
    {
        $user = User::whereIn('role', ['admin_dept', 'head_dept'])->findOrFail($id);
        $user->delete();
        return response()->json(['status' => 'success', 'message' => 'Akun departemen dihapus']);
    }
}