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
    Schema::table('employees', function (Blueprint $table) {
        // Menambahkan shift_id setelah position
        // Nullable dulu agar data lama tidak error, tapi nanti wajib diisi lewat HR
        $table->foreignId('shift_id')
              ->nullable()
              ->after('position')
              ->constrained('shifts')
              ->onDelete('set null'); // Jika shift dihapus, karyawan jadi tidak punya shift (bukan terhapus)
    });
}

public function down(): void
{
    Schema::table('employees', function (Blueprint $table) {
        $table->dropForeign(['shift_id']);
        $table->dropColumn('shift_id');
    });
}
};
