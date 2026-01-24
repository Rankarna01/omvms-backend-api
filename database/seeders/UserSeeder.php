<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset table user (Hati-hati, ini menghapus data lama)
        // User::truncate(); 

        // 1. Admin System (IT Support Global)
        // Tidak butuh departemen spesifik
        User::create([
            'name'      => 'System Administrator',
            'username'  => 'sysadmin',
            'email'     => 'admin@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin_system', 
            'department'=> null,
            'is_active' => true,
        ]);

        // 2. HR System (Human Resources)
        User::create([
            'name'      => 'HR Manager',
            'username'  => 'hr_manager',
            'email'     => 'hr@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'hr_system',
            'department'=> 'HRGA', // HR biasanya masuk dept HRGA
            'is_active' => true,
        ]);

        // 3. Admin POS (Petugas Kantin)
        User::create([
            'name'      => 'Petugas Kantin',
            'username'  => 'kasir_kantin',
            'email'     => 'pos@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin_pos',
            'department'=> 'General Affair',
            'is_active' => true,
        ]);

        // --- DEPARTEMEN IT (Contoh 1 Flow Lengkap) ---

        // 4. Admin Department (Admin IT)
        // Tugas: Input data lembur tim IT
        User::create([
            'name'      => 'Admin IT Dept',
            'username'  => 'admin_it',
            'email'     => 'it_admin@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin_dept',
            'department'=> 'Information Technology', // Wajib diisi
            'is_active' => true,
        ]);

        // 5. Head Department (Manager IT) - ROLE BARU
        // Tugas: Approve lembur tim IT
        User::create([
            'name'      => 'Manager IT',
            'username'  => 'head_it', // Username untuk login Head Dept
            'email'     => 'manager_it@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'head_dept', // Role baru
            'department'=> 'Information Technology', // Harus sama dengan Admin & Karyawan IT
            'is_active' => true,
        ]);

        // 6. Karyawan (Staff IT)
        User::create([
            'name'      => 'Budi Santoso',
            'username'  => 'budi',
            'email'     => 'budi@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'employee',
            'department'=> 'Information Technology',
            'is_active' => true,
        ]);

        // --- DEPARTEMEN FINANCE (Contoh 2 agar terlihat bedanya) ---
        
        // Head Dept Finance
        User::create([
            'name'      => 'Manager Finance',
            'username'  => 'head_finance',
            'email'     => 'manager_fin@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'head_dept',
            'department'=> 'Finance',
            'is_active' => true,
        ]);
    }
}