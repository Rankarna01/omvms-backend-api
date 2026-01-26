<?php

namespace App\Http\Controllers\Admin\CanteenAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CanteenAccountController extends Controller
{
    public function index()
    {
        // FILTER: Gunakan 'admin_pos' sesuai database
        $users = User::where('role', 'admin_pos')->latest()->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'username'    => $validated['username'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            
            'role'        => 'admin_pos', // <--- SESUAIKAN DISINI
            
            'employee_id' => null,
            'is_active'   => true
        ]);

        return response()->json([
            'status' => 'success', 
            'message' => 'Akun kantin berhasil dibuat', 
            'data' => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // FILTER: Pastikan yang diedit adalah akun 'admin_pos'
        $user = User::where('role', 'admin_pos')->findOrFail($id);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'password' => 'nullable|min:6',
            'is_active'=> 'boolean'
        ]);

        $user->name = $validated['name'];
        if ($request->has('is_active')) {
            $user->is_active = $validated['is_active'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();

        return response()->json([
            'status' => 'success', 
            'message' => 'Akun kantin diperbarui'
        ]);
    }

    public function destroy($id)
    {
        // FILTER: Pastikan yang dihapus adalah akun 'admin_pos'
        $user = User::where('role', 'admin_pos')->findOrFail($id);
        $user->delete();
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Akun kantin dihapus'
        ]);
    }
}