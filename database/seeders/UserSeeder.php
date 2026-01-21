<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'admin@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // 2. Admin Departemen
        User::create([
            'name' => 'Admin HR',
            'username' => 'admin_hr',
            'email' => 'hr@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'admin_dept',
            'is_active' => true,
        ]);

        // 3. Karyawan
        User::create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // 4. Petugas Kantin (POS)
        User::create([
            'name' => 'Petugas Kantin',
            'username' => 'kasir',
            'email' => 'pos@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'pos',
            'is_active' => true,
        ]);
    }
}