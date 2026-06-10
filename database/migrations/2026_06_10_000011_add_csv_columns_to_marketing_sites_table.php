<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('business_type');
            $table->string('website')->nullable()->after('phone');
            $table->string('street')->nullable()->after('formatted_address');
            $table->string('county')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('county');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('booking_appointment_link')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'website',
                'street',
                'county',
                'postal_code',
                'country',
                'booking_appointment_link',
            ]);
        });
    }
};
