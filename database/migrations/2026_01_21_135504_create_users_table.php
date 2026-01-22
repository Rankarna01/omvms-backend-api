<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            
            // 1. Ubah 'name' jadi nullable agar error 1364 hilang
            // (Nama asli nanti ambil dari relasi employee->full_name)
            $table->string('name')->nullable(); 

            // 2. Username wajib ada (Hapus nullable)
            $table->string('username')->unique(); 

            $table->string('email')->unique();
            $table->string('password');
            
            // 3. Role Enum
            $table->enum('role', [
    'admin_system', 
    'hr_system', 
    'admin_pos', 
    'admin_dept', 
    'employee'
])->default('employee');
            
            // 4. Relasi ke Employee (PENTING UNTUK HR SYSTEM)
            // Pastikan tabel employees sudah terbuat sebelum tabel users dijalankan!
            $table->foreignId('employee_id')
                  ->nullable()
                  ->constrained('employees')
                  ->onDelete('set null');

            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Tambahkan Soft Deletes
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};