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
        
        // --- UBAH DISINI ---
        // Ganti 'username' jadi 'nik'
        $table->string('nik')->unique(); 
        
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        
        $table->enum('role', [
            'admin_system', 'hr_system', 'admin_pos', 
            'admin_dept', 'head_dept', 'employee'
        ])->default('employee');
        
        $table->string('department')->nullable(); 
        
        $table->foreignId('employee_id')
              ->nullable()
              ->constrained('employees')
              ->onDelete('cascade'); 
        
        $table->boolean('is_active')->default(true);
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};