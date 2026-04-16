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
        Schema::table('sites', function (Blueprint $table) {
            $table->enum('domain_type', [
                'subdomain',
                'custom',
                'draft'
            ])->default('subdomain')->after('places_id');
            $table->string('custom_domain')->nullable()->after('subdomain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('domain_type');
            $table->dropColumn('custom_domain');
        });
    }
};
