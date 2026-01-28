<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Department;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // TAHAP 1: AMBIL DATA DEPARTEMEN
        // ==========================================
        // Kita asumsikan Department sudah dibuat di MasterDataSeeder.
        // Jika belum, gunakan firstOrCreate seperti kode awalmu.
        
        $deptIT  = Department::firstOrCreate(['dept_code' => 'IT'], ['dept_name' => 'Information Technology', 'description' => 'IT Dept', 'is_active' => true]);
        $deptHR  = Department::firstOrCreate(['dept_code' => 'HRGA'], ['dept_name' => 'Human Resources & GA', 'description' => 'HR Dept', 'is_active' => true]);
        $deptFin = Department::firstOrCreate(['dept_code' => 'FIN'], ['dept_name' => 'Finance', 'description' => 'Finance Dept', 'is_active' => true]);
        $deptGA  = Department::firstOrCreate(['dept_code' => 'GA'], ['dept_name' => 'General Affair', 'description' => 'GA Dept', 'is_active' => true]);

        // ==========================================
        // TAHAP 2: BUAT USER (Gunakan firstOrCreate)
        // ==========================================

        // 1. Admin System
        User::firstOrCreate(
            ['nik' => '00000'], // Kunci Unik Pengecekan
            [
                'name'          => 'System Administrator',
                'email'         => 'admin@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'admin_system', 
                'department_id' => null,
                'is_active'     => true,
            ]
        );

        // 2. HR System
        User::firstOrCreate(
            ['nik' => '10001'],
            [
                'name'          => 'HR Manager',
                'email'         => 'hr@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'hr_system',
                'department_id' => $deptHR->id,
                'is_active'     => true,
            ]
        );

        // 3. Admin POS
        User::firstOrCreate(
            ['nik' => 'POS01'],
            [
                'name'          => 'Petugas Kantin 1',
                'email'         => 'pos@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'admin_pos',
                'department_id' => $deptGA->id,
                'is_active'     => true,
            ]
        );

        // 4. Admin IT Dept
        User::firstOrCreate(
            ['nik' => '2024001'],
            [
                'name'          => 'Admin IT Dept',
                'email'         => 'it_admin@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'admin_dept',
                'department_id' => $deptIT->id,
                'is_active'     => true,
            ]
        );

        // 5. Head Dept IT
        User::firstOrCreate(
            ['nik' => '2024002'],
            [
                'name'          => 'Manager IT',
                'email'         => 'manager_it@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'head_dept',
                'department_id' => $deptIT->id,
                'is_active'     => true,
            ]
        );

        // 6. Staff IT
        User::firstOrCreate(
            ['nik' => '2024003'],
            [
                'name'          => 'Budi Santoso',
                'email'         => 'budi@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'employee',
                'department_id' => $deptIT->id,
                'is_active'     => true,
            ]
        );

        // 7. Head Dept Finance
        User::firstOrCreate(
            ['nik' => '2024004'],
            [
                'name'          => 'Manager Finance',
                'email'         => 'manager_fin@omvms.com',
                'password'      => Hash::make('password'),
                'role'          => 'head_dept',
                'department_id' => $deptFin->id,
                'is_active'     => true,
            ]
        );
    }
}