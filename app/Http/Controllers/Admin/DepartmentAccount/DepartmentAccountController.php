<?php

namespace App\Http\Controllers\Admin\DepartmentAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DepartmentAccountController extends Controller
{
    /**
     * GET: List Akun (Admin Dept & Head Dept)
     */
   public function index(Request $request)
    {
        // 1. Mulai Query
        $query = Employee::with('department');

        // 2. FILTER DEPARTEMEN (Ini yang membuat dropdown sesuai departemen)
        if ($request->has('department_id') && $request->department_id != '') {
            $query->where('department_id', $request->department_id);
        }

        // 3. Filter Pencarian Nama/NIK (Opsional)
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('nik', 'like', '%' . $request->search . '%');
            });
        }

        // 4. Filter Karyawan yang BELUM punya akun (Supaya tidak double)
        // Pastikan Model Employee sudah ada fungsi public function user() { return $this->hasOne(User::class); }
        if ($request->has('show_doesnt_have_account')) {
             $query->doesntHave('user');
        }

        return response()->json([
            'status' => 'success',
            // Gunakan paginate yang cukup besar atau get() jika data sedikit
            'data' => $query->orderBy('full_name', 'asc')->paginate(50) 
        ]);
    }
    /**
     * POST: Buat Akun Baru
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'employee_id'   => 'required|exists:employees,id|unique:users,employee_id', // 1 Karyawan = 1 Akun
            'department_id' => 'required|exists:departments,id', // Wajib pilih departemen
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:6',
            'role'          => 'required|in:admin_dept,head_dept'
        ]);

        // 2. Ambil data karyawan untuk mendapatkan NIK & Nama
        $employee = Employee::findOrFail($validated['employee_id']);
        
        // Ambil NIK dari data karyawan
        $nik = $employee->nik; 

        // 3. Cek apakah NIK sudah dipakai di tabel users (Double protection)
        if (User::where('nik', $nik)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK karyawan ini sudah terdaftar sebagai user.'
            ], 422);
        }

        // 4. Create User
        try {
            $user = User::create([
                'name'          => $employee->full_name, // Mengambil nama asli karyawan
                'nik'           => $nik,                 // Otomatis dari Employee
                'email'         => $validated['email'],
                'password'      => Hash::make($validated['password']),
                'role'          => $validated['role'],
                'employee_id'   => $employee->id,
                'department_id' => $validated['department_id'], // Sesuai pilihan admin
                'is_active'     => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Akun departemen berhasil dibuat',
                'data' => $user->load('department') // Tampilkan respon beserta data departemennya
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT: Update Akun
     */
    public function update(Request $request, $id)
    {
        // Cari User yg role-nya admin_dept atau head_dept
        $user = User::whereIn('role', ['admin_dept', 'head_dept'])->findOrFail($id);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id', // Bisa pindah departemen
            'email'         => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'          => 'required|in:admin_dept,head_dept',
            'status'        => 'required|in:ACTIVE,INACTIVE',
            'password'      => 'nullable|min:6'
        ]);

        // Update Data
        $user->department_id = $validated['department_id'];
        $user->email         = $validated['email'];
        $user->role          = $validated['role'];
        $user->is_active     = $validated['status'] === 'ACTIVE'; // Konversi string ke boolean

        // Update password hanya jika diisi
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success', 
            'message' => 'Data akun berhasil diperbarui',
            'data' => $user->load('department')
        ]);
    }

    /**
     * DELETE: Hapus Akun (Soft Delete)
     */
    public function destroy($id)
    {
        $user = User::whereIn('role', ['admin_dept', 'head_dept'])->findOrFail($id);
        
        // Hapus user (SoftDelete sesuai model)
        $user->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dinonaktifkan (dihapus)'
        ]);
    }
    
    /**
     * GET: Helper untuk Form (List Department & Employee yg belum punya akun)
     * Opsional: Berguna untuk dropdown di frontend
     */
    public function getFormOptions()
    {
        // Ambil semua departemen aktif
        $departments = Department::where('is_active', true)
                        ->select('id', 'dept_name', 'dept_code')
                        ->get();

        // Ambil karyawan yang belum punya akun user
        $availableEmployees = Employee::doesntHave('user')
                        ->select('id', 'nik', 'full_name')
                        ->get();

        return response()->json([
            'departments' => $departments,
            'employees' => $availableEmployees
        ]);
    }
}