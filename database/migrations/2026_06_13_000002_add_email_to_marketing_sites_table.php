<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->string('email')->nullable()->after('booking_appointment_link');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
