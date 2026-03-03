<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            // Add unique code column
            $table->string('overtime_code')->unique()->after('id')->nullable();
            
            // Change duration to decimal to support 8.5 (8 hours 30 mins)
            // 5 digits total, 2 after decimal point (e.g., 999.99)
            $table->decimal('duration', 5, 2)->change(); 
        });
    }

    public function down(): void
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropColumn('overtime_code');
            $table->integer('duration')->change(); // Revert back if needed
        });
    }
};