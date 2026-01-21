<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique(); // Primary ID Perusahaan
            $table->string('full_name');
            $table->string('phone');
            
            // Relasi ke tabel departments
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            
            $table->string('position'); // Jabatan
            $table->date('join_date');
            $table->string('photo')->nullable(); // Foto profil (opsional)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes(); // Fitur tong sampah (agar data tidak hilang permanen)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};