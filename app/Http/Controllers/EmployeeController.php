<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // GET: Ambil semua data karyawan (Paginated)
    public function index()
    {
        $employees = Employee::with('department') // Load relasi department
            ->orderBy('created_at', 'desc')
            ->paginate(10); // 10 data per halaman

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }

    // POST: Tambah Karyawan Baru
    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|unique:employees,nik',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|numeric',
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string',
            'join_date' => 'required|date',
            'is_active' => 'required|boolean',
        ]);

        $employee = Employee::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil ditambahkan',
            'data' => $employee
        ], 201);
    }

    // GET: Detail 1 Karyawan (Opsional, untuk edit)
    public function show($id)
    {
        $employee = Employee::with('department')->find($id);

        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $employee]);
    }

    // PUT: Update Data Karyawan
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $request->validate([
            'nik' => ['required', Rule::unique('employees')->ignore($employee->id)],
            'full_name' => 'required|string|max:255',
            'phone' => 'required|numeric',
            'department_id' => 'required|exists:departments,id',
            'position' => 'required|string',
            'join_date' => 'required|date',
            'is_active' => 'required|boolean',
        ]);

        $employee->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Data karyawan berhasil diperbarui',
            'data' => $employee
        ]);
    }

    // DELETE: Hapus Karyawan
    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $employee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil dihapus'
        ]);
    }

    // GET: List Department (Untuk Dropdown di Frontend)
    public function getDepartments()
    {
        $departments = Department::select('id', 'dept_name')->where('is_active', 1)->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $departments
        ]);
    }
}