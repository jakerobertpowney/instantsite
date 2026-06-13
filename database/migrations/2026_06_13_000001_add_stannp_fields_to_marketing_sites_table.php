<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->string('stannp_id')->nullable()->after('status');
            $table->timestamp('stannp_sent_at')->nullable()->after('stannp_id');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->dropColumn(['stannp_id', 'stannp_sent_at']);
        });
    }
};
