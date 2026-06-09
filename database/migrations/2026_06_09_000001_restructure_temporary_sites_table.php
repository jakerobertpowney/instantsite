<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporary_sites', function (Blueprint $table) {
            $table->dropColumn('data');
            $table->string('places_id')->nullable()->change();
            $table->string('batch_id')->nullable()->after('places_id');
            $table->string('business_name')->nullable()->after('batch_id');
            $table->string('business_type')->nullable()->after('business_name');
            $table->text('description')->nullable()->after('business_type');
            $table->string('logo_path')->nullable()->after('description');
            $table->string('formatted_address')->nullable()->after('logo_path');
            $table->string('city')->nullable()->after('formatted_address');
            $table->string('region')->nullable()->after('city');
            $table->string('phone')->nullable()->after('region');
            $table->string('whatsapp_number')->nullable()->after('phone');
            $table->string('website_url')->nullable()->after('whatsapp_number');
            $table->string('contact_email')->nullable()->after('website_url');
            $table->json('socials')->nullable()->after('contact_email');
            $table->json('opening_hours')->nullable()->after('socials');
            $table->json('quick_links')->nullable()->after('opening_hours');
            $table->json('services')->nullable()->after('quick_links');
            $table->json('images')->nullable()->after('services');
            $table->decimal('rating', 3, 1)->nullable()->after('images');
            $table->integer('review_count')->nullable()->after('rating');
            $table->json('reviews')->nullable()->after('review_count');
            $table->json('components')->nullable()->after('reviews');
            $table->string('services_heading')->nullable()->after('components');
            $table->string('services_cta_label')->nullable()->after('services_heading');
            $table->string('services_cta_link')->nullable()->after('services_cta_label');
        });
    }

    public function down(): void
    {
        Schema::table('temporary_sites', function (Blueprint $table) {
            $table->json('data')->after('places_id');
            $table->dropColumn([
                'batch_id',
                'business_name',
                'business_type',
                'description',
                'logo_path',
                'formatted_address',
                'city',
                'region',
                'phone',
                'whatsapp_number',
                'website_url',
                'contact_email',
                'socials',
                'opening_hours',
                'quick_links',
                'services',
                'images',
                'rating',
                'review_count',
                'reviews',
                'components',
                'services_heading',
                'services_cta_label',
                'services_cta_link',
            ]);
        });
    }
};
