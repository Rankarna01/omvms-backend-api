<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cek Credential Manual (Tanpa Session/Cookie)
        // Kita cari user by email
        $user = User::where('email', $request->email)->first();

        // 3. Cek Password & User
        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // 4. Hapus token lama (opsional, agar 1 device 1 token)
        $user->tokens()->delete();

        // 5. Buat Token Baru (Plain Text)
        // Token ini string panjang yang akan disimpan di frontend
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Return Response JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token, // <-- Ini kuncinya
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
    
    // API Cek User (Untuk mengetes token valid/tidak)
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }
}