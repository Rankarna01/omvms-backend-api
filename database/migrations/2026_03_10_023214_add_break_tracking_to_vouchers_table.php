<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modifikasi ENUM status untuk menambahkan ON_BREAK dan OVERBREAK
        // Pastikan sesuaikan dengan nama tabel jika menggunakan prefix, defaultnya 'vouchers'
        DB::statement("ALTER TABLE vouchers MODIFY COLUMN status ENUM('AVAILABLE', 'ON_BREAK', 'REDEEMED', 'OVERBREAK', 'EXPIRED') DEFAULT 'AVAILABLE'");

        // 2. Tambahkan kolom tracking
        Schema::table('vouchers', function (Blueprint $table) {
            $table->timestamp('checkin_at')->nullable()->after('expired_at'); // Waktu tap-in kantin
            $table->timestamp('checkout_at')->nullable()->after('checkin_at'); // Waktu tap-out kantin
            $table->boolean('is_late')->default(false)->after('checkout_at'); // Flag penanda telat
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['checkin_at', 'checkout_at', 'is_late']);
        });

        // Kembalikan ENUM seperti semula jika di-rollback
        DB::statement("ALTER TABLE vouchers MODIFY COLUMN status ENUM('AVAILABLE', 'REDEEMED', 'EXPIRED') DEFAULT 'AVAILABLE'");
    }
};