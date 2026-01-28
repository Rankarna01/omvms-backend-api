<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('vouchers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('overtime_request_id')->constrained()->onDelete('cascade');
        $table->foreignId('employee_id')->constrained();
        
        $table->string('code')->unique(); // Kode Barcode (ex: VCH-12345)
        $table->string('qr_path')->nullable(); // Jika mau simpan path gambar QR
        
        $table->enum('status', ['AVAILABLE', 'REDEEMED', 'EXPIRED'])->default('AVAILABLE');
        $table->dateTime('expired_at'); // Mengikuti logic approval tadi
        $table->dateTime('redeemed_at')->nullable(); // Kapan dipakai
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
