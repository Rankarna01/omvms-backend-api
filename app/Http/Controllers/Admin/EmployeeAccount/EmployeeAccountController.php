<?php

namespace App\Http\Controllers\Admin\EmployeeAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeAccountController extends Controller
{
    // GET: List semua akun karyawan
    public function index(Request $request)
    {
        $query = User::with('employee.department') // Load data karyawan & dept
                     ->where('role', 'employee');  // Filter hanya role employee

        if ($request->search) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(10)
        ]);
    }

    // POST: Buat Akun Karyawan (Link ke Data Employee)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id|unique:users,employee_id', // 1 Karyawan = 1 Akun
            'username'    => 'required|unique:users,username',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6',
        ]);

        // Ambil data karyawan untuk mengisi field 'name' (opsional, biar rapi)
        $employee = Employee::findOrFail($request->employee_id);

        $user = User::create([
            'name'        => $employee->full_name, // Ambil nama dari tabel employee
            'username'    => $validated['username'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => 'employee', // Hardcode role
            'employee_id' => $validated['employee_id'],
            'is_active'   => true
        ]);

        return response()->json(['status' => 'success', 'message' => 'Akun karyawan berhasil dibuat', 'data' => $user], 201);
    }

    // DELETE: Hapus Akun
    public function destroy($id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);
        $user->delete();
        return response()->json(['status' => 'success', 'message' => 'Akun karyawan dihapus']);
    }
}