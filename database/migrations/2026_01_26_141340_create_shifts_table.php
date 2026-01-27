<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('shifts', function (Blueprint $table) {
        $table->id();
        $table->string('shift_name'); 
        
        $table->time('start_time');
        $table->time('end_time');

        // --- UPDATE DISINI ---
        // Hapus: $table->boolean('allow_overtime')->default(false);
        
        // Ganti dengan Logic Baru:
        $table->boolean('allow_meal')->default(false);     // Voucher Makan
        $table->boolean('lock_request')->default(false);   // Strict Mode (Kunci Pengajuan)
        
        $table->text('description')->nullable();
        $table->timestamps();
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};