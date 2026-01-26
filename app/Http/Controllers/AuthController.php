<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Tambahkan Import Hash
use App\Models\User;

class AuthController extends Controller
{
   public function login(Request $request)
    {
        // 1. Validasi Input (Terima 'nik')
        $request->validate([
            'nik'      => 'required|string', // Frontend kirim 'nik'
            'password' => 'required|string',
        ]);

        // 2. Cek User berdasarkan kolom 'nik'
        $user = User::where('nik', $request->nik)->first();

        // 3. Cek Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK atau Password salah.'
            ], 401);
        }

        // Cek Status Aktif (Opsional, recommended)
        if (!$user->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda dinonaktifkan. Hubungi Admin.'
            ], 403);
        }

        // 4. Hapus token lama (Agar 1 device 1 token - Opsional)
        $user->tokens()->delete();

        // 5. Buat Token Baru
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Return Response JSON (Struktur Tetap Sama)
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }
    
    // API Cek User
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }
}