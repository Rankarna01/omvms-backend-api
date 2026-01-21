<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    // GET: Ambil semua data department + Jumlah Karyawan
    public function index(Request $request)
    {
        $query = Department::withCount('employees'); // Hitung jumlah karyawan otomatis

        // Fitur Search sederhana (Optional)
        if ($request->search) {
            $query->where('dept_name', 'like', '%' . $request->search . '%')
                  ->orWhere('dept_code', 'like', '%' . $request->search . '%');
        }

        $departments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $departments
        ]);
    }

    // POST: Tambah Department Baru
    public function store(Request $request)
    {
        $request->validate([
            'dept_code' => 'required|string|max:10|unique:departments,dept_code',
            'dept_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $department = Department::create([
            'dept_code' => strtoupper($request->dept_code), // Paksa huruf besar
            'dept_name' => $request->dept_name,
            'description' => $request->description,
            'is_active' => true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Departemen berhasil dibuat',
            'data' => $department
        ], 201);
    }

    // GET: Detail 1 Department
    public function show($id)
    {
        $department = Department::find($id);
        if (!$department) return response()->json(['message' => 'Not Found'], 404);

        return response()->json(['status' => 'success', 'data' => $department]);
    }

    // PUT: Update Department
    public function update(Request $request, $id)
    {
        $department = Department::find($id);
        if (!$department) return response()->json(['message' => 'Not Found'], 404);

        $request->validate([
            // Unique tapi abaikan ID diri sendiri saat update
            'dept_code' => ['required', 'string', Rule::unique('departments')->ignore($department->id)],
            'dept_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $department->update([
            'dept_code' => strtoupper($request->dept_code),
            'dept_name' => $request->dept_name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Departemen berhasil diperbarui',
            'data' => $department
        ]);
    }

    // DELETE: Hapus Department
    public function destroy($id)
    {
        $department = Department::find($id);
        if (!$department) return response()->json(['message' => 'Not Found'], 404);

        // Cek apakah masih ada karyawan di dalamnya? (Optional Safety)
        if ($department->employees()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal hapus! Masih ada karyawan di departemen ini.'
            ], 400);
        }

        $department->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Departemen berhasil dihapus'
        ]);
    }
}