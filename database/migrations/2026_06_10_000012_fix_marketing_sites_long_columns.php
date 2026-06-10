<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->text('booking_appointment_link')->nullable()->change();
            $table->text('website')->nullable()->change();
            $table->text('formatted_address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->string('booking_appointment_link')->nullable()->change();
            $table->string('website')->nullable()->change();
            $table->string('formatted_address')->nullable()->change();
        });
    }
};
