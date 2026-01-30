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
    Schema::table('vouchers', function (Blueprint $table) {
        // Menambahkan kolom redeemed_by yang boleh kosong (nullable)
        $table->foreignId('redeemed_by')->nullable()->constrained('users')->onDelete('set null');
        
        // Opsional: Tambahkan index agar query lebih cepat jika nanti butuh laporan per kasir
        // $table->index('redeemed_by');
    });
}

public function down()
{
    Schema::table('vouchers', function (Blueprint $table) {
        $table->dropForeign(['redeemed_by']);
        $table->dropColumn('redeemed_by');
    });
}
};
