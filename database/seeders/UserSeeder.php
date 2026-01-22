<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Opsional: Kosongkan tabel user dulu agar tidak duplikat saat seeding ulang
        // User::truncate(); 

        // 1. Admin System (Pemilik Sistem / IT Support)
        // Akses: Kelola User, Konfigurasi Global, Monitoring Error
        User::create([
            'name'      => 'System Administrator',
            'username'  => 'sysadmin',
            'email'     => 'admin@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin_system', 
            'is_active' => true,
        ]);

        // 2. HR System (Human Resources)
        // Akses: Master Data Employee, Master Data Department, Reports
        User::create([
            'name'      => 'HR Manager',
            'username'  => 'hr_manager',
            'email'     => 'hr@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'hr_system',
            'is_active' => true,
        ]);

        // 3. Admin POS (Petugas Kantin)
        // Akses: Scan Voucher, Report Transaksi Harian
        User::create([
            'name'      => 'Petugas Kantin',
            'username'  => 'kasir_kantin',
            'email'     => 'pos@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin_pos',
            'is_active' => true,
        ]);

        // 4. Admin Department (Kepala/Admin per Departemen)
        // Akses: Input Lembur Karyawan, Approve Lembur
        User::create([
            'name'      => 'Admin IT Dept',
            'username'  => 'admin_it',
            'email'     => 'it_admin@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin_dept',
            'is_active' => true,
        ]);

        // 5. Karyawan (End User)
        // Akses: Cek Saldo Voucher, History Pemakaian
        User::create([
            'name'      => 'Budi Santoso',
            'username'  => 'budi',
            'email'     => 'budi@omvms.com',
            'password'  => Hash::make('password'),
            'role'      => 'employee',
            'is_active' => true,
        ]);
    }
}