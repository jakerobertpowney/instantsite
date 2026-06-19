<?php

use App\Models\MarketingSite;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->string('unique_code', 8)->nullable()->unique()->after('places_id');
        });

        // Backfill existing rows
        MarketingSite::whereNull('unique_code')->each(function (MarketingSite $site) {
            do {
                $code = strtolower(Str::random(6));
            } while (MarketingSite::where('unique_code', $code)->exists());

            $site->update(['unique_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('marketing_sites', function (Blueprint $table) {
            $table->dropColumn('unique_code');
        });
    }
};
