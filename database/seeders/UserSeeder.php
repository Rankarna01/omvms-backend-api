<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department; // Pastikan Model Department diimport

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // TAHAP 1: BUAT / AMBIL DATA DEPARTEMEN DULU
        // ==========================================
        // Kita pakai firstOrCreate agar tidak error jika dijalankan berulang
        
        $deptIT = Department::firstOrCreate(
            ['dept_code' => 'IT'], // Cek berdasarkan kode
            [
                'dept_name' => 'Information Technology', 
                'description' => 'Tim IT Support dan Developer',
                'is_active' => true
            ]
        );

        $deptHR = Department::firstOrCreate(
            ['dept_code' => 'HRGA'], 
            [
                'dept_name' => 'Human Resources & GA', 
                'description' => 'Departemen SDM dan Umum',
                'is_active' => true
            ]
        );

        $deptFin = Department::firstOrCreate(
            ['dept_code' => 'FIN'], 
            [
                'dept_name' => 'Finance & Accounting', 
                'description' => 'Departemen Keuangan',
                'is_active' => true
            ]
        );

        $deptGA = Department::firstOrCreate(
            ['dept_code' => 'GA'], 
            [
                'dept_name' => 'General Affair', 
                'description' => 'Urusan Umum dan Fasilitas',
                'is_active' => true
            ]
        );

       

        // 1. Admin System (IT Support Global) - Tidak wajib punya departemen
        User::create([
            'name'          => 'System Administrator',
            'nik'           => '00000',
            'email'         => 'admin@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'admin_system', 
            'department_id' => null, // Boleh NULL
            'is_active'     => true,
            'employee_id'   => null,
        ]);

        // 2. HR System (Human Resources) - Masuk ke Dept HR
        User::create([
            'name'          => 'HR Manager',
            'nik'           => '10001',
            'email'         => 'hr@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'hr_system',
            'department_id' => $deptHR->id, // Ambil ID dari variabel $deptHR
            'is_active'     => true,
            'employee_id'   => null, 
        ]);

        // 3. Admin POS (Petugas Kantin) - Masuk ke Dept GA
        User::create([
            'name'          => 'Petugas Kantin 1',
            'nik'           => 'POS01',
            'email'         => 'pos@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'admin_pos',
            'department_id' => $deptGA->id, // Masuk GA
            'is_active'     => true,
            'employee_id'   => null,
        ]);

        // --- USER DEPARTEMEN IT ---

        // 4. Admin Department (Admin IT)
        User::create([
            'name'          => 'Admin IT Dept',
            'nik'           => '2024001',
            'email'         => 'it_admin@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'admin_dept',
            'department_id' => $deptIT->id, // Masuk IT
            'is_active'     => true,
            'employee_id'   => null,
        ]);

        // 5. Head Department (Manager IT)
        User::create([
            'name'          => 'Manager IT',
            'nik'           => '2024002',
            'email'         => 'manager_it@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'head_dept',
            'department_id' => $deptIT->id, // Masuk IT
            'is_active'     => true,
            'employee_id'   => null,
        ]);

        // 6. Karyawan (Staff IT)
        User::create([
            'name'          => 'Budi Santoso',
            'nik'           => '2024003',
            'email'         => 'budi@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'employee',
            'department_id' => $deptIT->id, // Masuk IT
            'is_active'     => true,
            'employee_id'   => null,
        ]);

        // --- USER DEPARTEMEN FINANCE ---
        
        // 7. Head Dept Finance
        User::create([
            'name'          => 'Manager Finance',
            'nik'           => '2024004',
            'email'         => 'manager_fin@omvms.com',
            'password'      => Hash::make('password'),
            'role'          => 'head_dept',
            'department_id' => $deptFin->id, // Masuk Finance
            'is_active'     => true,
            'employee_id'   => null,
        ]);
    }
}