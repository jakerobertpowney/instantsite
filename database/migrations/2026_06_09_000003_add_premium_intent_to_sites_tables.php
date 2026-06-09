<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_sites', function (Blueprint $table) {
            $table->boolean('premium_intent')->default(false)->after('components');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('premium_intent')->default(false)->after('components');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_sites', function (Blueprint $table) {
            $table->dropColumn('premium_intent');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('premium_intent');
        });
    }
};
