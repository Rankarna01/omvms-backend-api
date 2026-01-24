<?php

namespace App\Http\Controllers\Admin\DepartmentAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DepartmentAccountController extends Controller
{
    // GET: List Akun
    public function index(Request $request)
    {
        // Kita load relasi berjenjang: User -> Employee -> Department
        $query = User::with(['employee.department']) 
                     ->whereIn('role', ['admin_dept', 'head_dept']);

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(10)
        ]);
    }

    // POST: Buat Akun Baru
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id|unique:users,employee_id',
            'username'      => 'required|unique:users,username',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:6',
            'role'          => 'required|in:admin_dept,head_dept',
            // department_id dari frontend boleh dikirim, tapi kita validasi saja keberadaannya
            'department_id' => 'sometimes|exists:departments,id' 
        ]);

        // 2. AMBIL DATA KARYAWAN
        $employee = Employee::findOrFail($request->employee_id);

        // 3. LOGIC PENENTUAN DEPARTEMEN
        // Kita ambil department_id ASLI milik karyawan tersebut
        // Jadi meskipun frontend salah kirim ID dept, data di database tetap konsisten
        $realDepartmentId = $employee->department_id;

        // 4. Buat User
        $user = User::create([
            'name'          => $employee->full_name,
            'username'      => $validated['username'],
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'role'          => $validated['role'],
            
            // Relasi PENTING
            'employee_id'   => $employee->id,
            'department_id' => $realDepartmentId, // <--- ISI OTOMATIS DARI KARYAWAN
            
            'is_active'     => true
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Akun departemen berhasil dibuat', 
            'data' => $user
        ], 201);
    }

    // PUT: Update Akun
    public function update(Request $request, $id)
    {
        $user = User::whereIn('role', ['admin_dept', 'head_dept'])->findOrFail($id);

        $validated = $request->validate([
            'username' => 'required|unique:users,username,' . $id,
            'email'    => 'required|email|unique:users,email,' . $id,
            'role'     => 'required|in:admin_dept,head_dept',
            'status'   => 'required|in:ACTIVE,INACTIVE',
            'password' => 'nullable|min:6'
        ]);

        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->is_active = $validated['status'] === 'ACTIVE';

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Data diperbarui']);
    }

    // DELETE
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['status' => 'success']);
    }
}