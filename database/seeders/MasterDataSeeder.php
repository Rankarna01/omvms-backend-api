<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Data Department (Gunakan updateOrCreate agar tidak duplikat)
        // Logic: Cari 'dept_code' IT. Jika ada update datanya, jika tidak ada buat baru.
        
        $it = Department::updateOrCreate(
            ['dept_code' => 'IT'], // Kunci pencarian (Unique Key)
            [
                'dept_name' => 'Information Technology',
                'description' => 'Bagian IT dan Software Development'
            ]
        );

        $hr = Department::updateOrCreate(
            ['dept_code' => 'HR'], 
            [
                'dept_name' => 'Human Resource',
                'description' => 'Personalia dan Umum'
            ]
        );
        
        Department::updateOrCreate(
            ['dept_code' => 'FIN'],
            [ 
                'dept_name' => 'Finance', 
                'description' => 'Keuangan Perusahaan'
            ]
        );

        // 2. Buat Data Dummy Employee (Gunakan updateOrCreate juga via NIK atau Email)
        Employee::updateOrCreate(
            ['nik' => 'EMP001'], // Kunci pencarian (Unique Key)
            [
                'full_name' => 'Budi Developer',
                'email' => 'budi@omvms.com', 
                'phone' => '08123456789',
                'department_id' => $it->id, // $it tetap mengembalikan objek department yg benar
                'position' => 'Senior Developer',
                'join_date' => '2024-01-01',
                'is_active' => true
            ]
        );

        Employee::updateOrCreate(
            ['nik' => 'EMP002'],
            [
                'full_name' => 'Siti HRD',
                'email' => 'siti@omvms.com',
                'phone' => '08987654321',
                'department_id' => $hr->id,
                'position' => 'HR Manager',
                'join_date' => '2023-05-20',
                'is_active' => true
            ]
        );
    }
}