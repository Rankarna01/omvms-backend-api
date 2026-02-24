<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;

class EmployeeProfileController extends Controller
{
    /**
     * Ambil data profil lengkap dengan Departemen
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Load data employee beserta relasi department-nya
        $employee = Employee::with('department')->find($user->employee_id);

        if (!$employee) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan'], 404);
        }

        return response()->json([
            'status'  => 'success',
            'data'    => [
                'id'         => $employee->id,
                'nik'        => $employee->nik,
                'full_name'  => $employee->full_name,
                'email'      => $employee->email,
                'avatar'     => $employee->avatar ? asset('storage/' . $employee->avatar) : null,
                'department' => $employee->department ? $employee->department->dept_name : '-',
            ]
        ]);
    }

    /**
     * Update Avatar (Upload Foto)
     */
    public function updateAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    $user = $request->user();
    // Ambil data Employee berdasarkan employee_id yang ada di tabel users
    $employee = \App\Models\Employee::find($user->employee_id);

    if ($request->hasFile('avatar')) {
        // Hapus file lama jika ada
        if ($employee->avatar) {
            Storage::disk('public')->delete($employee->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $employee->update(['avatar' => $path]);

        return response()->json([
            'status' => 'success',
            'url' => asset('storage/' . $path)
        ]);
    }
}

    /**
     * Ubah Password Sendiri
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        // Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password lama tidak sesuai'
            ], 422);
        }

        // Update password di tabel users
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil diubah'
        ]);
    }
}