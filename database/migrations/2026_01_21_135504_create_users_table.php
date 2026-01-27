<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            
            // Menggunakan NIK sebagai pengganti username
            $table->string('nik')->unique(); 
            
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            $table->enum('role', [
                'admin_system', 'hr_system', 'admin_pos', 
                'admin_dept', 'head_dept', 'employee'
            ])->default('employee');
            
            // --- MODIFIKASI: Relasi ke tabel departments ---
            // Mengganti string biasa menjadi foreign key
            $table->foreignId('department_id')
                  ->nullable() // Boleh kosong jika user adalah Super Admin/HR System
                  ->constrained('departments') // Terhubung ke tabel 'departments'
                  ->onDelete('set null'); // Jika departemen dihapus, user tidak ikut terhapus (hanya jadi null)
            
            // Relasi ke tabel employees (Data Diri Karyawan)
            $table->foreignId('employee_id')
                  ->nullable()
                  ->constrained('employees')
                  ->onDelete('cascade'); // Jika data karyawan dihapus, user login ikut terhapus
            
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Fitur soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};