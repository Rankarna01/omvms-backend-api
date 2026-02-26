<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('voucher_expiry_hours')->default(24);
            $table->integer('minimum_overtime_hours')->default(4);
            $table->time('canteen_open_time')->default('08:00:00');
            $table->time('canteen_close_time')->default('20:00:00');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('push_notifications')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};