<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_sites', function (Blueprint $table) {
            $table->id();
            $table->string('places_id')->unique();
            $table->string('business_name')->nullable();
            $table->string('formatted_address')->nullable();
            $table->string('city')->nullable();
            $table->string('business_type')->nullable();
            $table->enum('status', ['pending', 'claimed', 'dismissed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_sites');
    }
};
