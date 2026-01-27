<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Karyawan
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Detail Waktu
            $table->date('date');                 // Tanggal Lembur
            $table->time('start_time');           // Jam Mulai (17:00)
            $table->time('end_time');             // Jam Selesai (21:00)
            $table->integer('duration');          // Total Durasi (4) - dalam Jam
            
            // Logic Voucher & Shift Snapshot
            $table->boolean('is_eligible_for_voucher')->default(false); // Hasil Cek Shift allow_meal
            
            // Alasan
            $table->text('reason');

            // Status Flow
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'])->default('DRAFT');
            
            // Logic Dynamic Expiration (Diisi saat Approval)
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expired_at')->nullable(); // Batas waktu redeem voucher

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};