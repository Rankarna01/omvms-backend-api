<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
            // 1. Tambahkan employee_id dulu (PENTING: ini harus duluan)
            $table->foreignId('employee_id')
                  ->nullable()          // Boleh kosong
                  ->after('id')         // Kita taruh setelah kolom 'id' bawaan user
                  ->constrained('employees')
                  ->onDelete('set null');

            // 2. Baru tambahkan department_id
            $table->foreignId('department_id')
                  ->nullable()          // Boleh kosong
                  ->after('employee_id') // AMAN, karena employee_id sudah dibuat di baris atas
                  ->constrained('departments')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dan kolom department_id
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');

            // Hapus foreign key dan kolom employee_id
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};