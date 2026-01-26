<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Data Department
        $it = Department::create([
            'dept_code' => 'IT',
            'dept_name' => 'Information Technology',
            'description' => 'Bagian IT dan Software Development'
        ]);

        $hr = Department::create([
            'dept_code' => 'HR',
            'dept_name' => 'Human Resource',
            'description' => 'Personalia dan Umum'
        ]);
        
        Department::create([
            'dept_code' => 'FIN', 
            'dept_name' => 'Finance', 
            'description' => 'Keuangan Perusahaan'
        ]);

        // 2. Buat Data Dummy Employee
        Employee::create([
            'nik' => 'EMP001',
            'full_name' => 'Budi Developer',
            // [FIX] Tambahkan Email dummy
            'email' => 'budi@omvms.com', 
            'phone' => '08123456789',
            'department_id' => $it->id,
            'position' => 'Senior Developer',
            'join_date' => '2024-01-01',
            'is_active' => true
        ]);

        Employee::create([
            'nik' => 'EMP002',
            'full_name' => 'Siti HRD',
            // [FIX] Tambahkan Email dummy
            'email' => 'siti@omvms.com',
            'phone' => '08987654321',
            'department_id' => $hr->id,
            'position' => 'HR Manager',
            'join_date' => '2023-05-20',
            'is_active' => true
        ]);
    }
}