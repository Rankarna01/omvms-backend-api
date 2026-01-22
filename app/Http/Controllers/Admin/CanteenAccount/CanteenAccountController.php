<?php

namespace App\Http\Controllers\Admin\CanteenAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CanteenAccountController extends Controller
{
    // GET: List akun POS
    public function index()
    {
        $users = User::where('role', 'pos')->latest()->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    // POST: Buat Akun POS (Tanpa Employee ID)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255', // Nama input manual
            'username' => 'required|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'username'    => $validated['username'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => 'pos', // Hardcode role POS
            'employee_id' => null,  // POS tidak terikat data karyawan
            'is_active'   => true
        ]);

        return response()->json(['status' => 'success', 'message' => 'Akun kantin berhasil dibuat', 'data' => $user], 201);
    }

    public function destroy($id)
    {
        $user = User::where('role', 'pos')->findOrFail($id);
        $user->delete();
        return response()->json(['status' => 'success', 'message' => 'Akun kantin dihapus']);
    }
}