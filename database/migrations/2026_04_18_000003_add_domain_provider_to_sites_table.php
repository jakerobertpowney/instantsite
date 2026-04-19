<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Which registrar/provider is connected ('cloudflare', 'godaddy', 'namecheap', etc.)
            $table->string('connected_provider')->nullable()->after('domain_verified');

            // OAuth access token or API key — encrypted at rest
            $table->text('provider_token')->nullable()->after('connected_provider');

            // Cloudflare zone ID (or similar provider-side identifier for the domain)
            $table->string('provider_zone_id')->nullable()->after('provider_token');

            // True once we have successfully pushed DNS records via the provider API
            $table->boolean('dns_auto_configured')->default(false)->after('provider_zone_id');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['connected_provider', 'provider_token', 'provider_zone_id', 'dns_auto_configured']);
        });
    }
};
