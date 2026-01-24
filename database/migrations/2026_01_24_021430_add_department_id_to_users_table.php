<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom department_id setelah employee_id
            // Nullable (karena superadmin mungkin tidak punya dept)
            // Constrained ke tabel departments
            $table->foreignId('department_id')
                  ->nullable()
                  ->after('employee_id')
                  ->constrained('departments')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};