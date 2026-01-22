<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin (HR System Owner - Full Akses)
        User::create([
            'username' => 'superadmin',
            'email' => 'admin@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // 2. Admin OMVMS (Admin Pusat Voucher/Kantin)
        User::create([
            'username' => 'admin_omvms',
            'email' => 'pusat@omvms.com', // Email beda dari superadmin
            'password' => Hash::make('password'),
            'role' => 'admin_omvms',
            'is_active' => true,
        ]);

        // 3. Admin Departemen (Admin IT - NEW ROLE)
        // Tugas: Buat jadwal lembur, report departemen
        User::create([
            'username' => 'admin_it',
            'email' => 'admin.it@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'admin_dept', // Role untuk Admin Departemen
            'is_active' => true,
        ]);

        // 4. Head Dept (Approval)
        User::create([
            'username' => 'head_it',
            'email' => 'head@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'head_dept',
            'is_active' => true,
        ]);

        // 5. Karyawan (User biasa)
        User::create([
            'username' => 'budi',
            'email' => 'budi@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        // 6. Petugas Kantin (POS)
        User::create([
            'username' => 'kasir',
            'email' => 'pos@omvms.com',
            'password' => Hash::make('password'),
            'role' => 'pos',
            'is_active' => true,
        ]);
    }
}