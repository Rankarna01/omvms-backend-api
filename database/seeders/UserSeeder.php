<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset table user (Opsional, agar tidak duplikat saat seed ulang)
        // User::truncate(); 

        // 1. Admin System (IT Support Global)
        User::create([
            'name'       => 'System Administrator',
            'nik'        => '00000', // Admin pakai kode spesial '00000'
            'email'      => 'admin@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin_system', 
            'department' => null,
            'is_active'  => true,
            'employee_id' => null, // Admin System tidak wajib link ke employee
        ]);

        // 2. HR System (Human Resources)
        User::create([
            'name'       => 'HR Manager',
            'nik'        => '10001', // NIK HR
            'email'      => 'hr@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'hr_system',
            'department' => 'HRGA',
            'is_active'  => true,
            'employee_id' => null, 
        ]);

        // 3. Admin POS (Petugas Kantin)
        User::create([
            'name'       => 'Petugas Kantin 1',
            'nik'        => 'POS01', // Kode POS bisa huruf+angka
            'email'      => 'pos@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin_pos',
            'department' => 'General Affair',
            'is_active'  => true,
            'employee_id' => null,
        ]);

        // --- DEPARTEMEN IT ---

        // 4. Admin Department (Admin IT)
        User::create([
            'name'       => 'Admin IT Dept',
            'nik'        => '2024001',
            'email'      => 'it_admin@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin_dept',
            'department' => 'Information Technology',
            'is_active'  => true,
            'employee_id' => null,
        ]);

        // 5. Head Department (Manager IT)
        User::create([
            'name'       => 'Manager IT',
            'nik'        => '2024002',
            'email'      => 'manager_it@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'head_dept',
            'department' => 'Information Technology',
            'is_active'  => true,
            'employee_id' => null,
        ]);

        // 6. Karyawan (Staff IT)
        User::create([
            'name'       => 'Budi Santoso',
            'nik'        => '2024003',
            'email'      => 'budi@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'employee',
            'department' => 'Information Technology',
            'is_active'  => true,
            'employee_id' => null, // Nanti diisi ID asli kalau sudah ada data employee
        ]);

        // --- DEPARTEMEN FINANCE ---
        
        // 7. Head Dept Finance
        User::create([
            'name'       => 'Manager Finance',
            'nik'        => '2024004',
            'email'      => 'manager_fin@omvms.com',
            'password'   => Hash::make('password'),
            'role'       => 'head_dept',
            'department' => 'Finance',
            'is_active'  => true,
            'employee_id' => null,
        ]);
    }
}