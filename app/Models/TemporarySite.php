<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemporarySite extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'places_id',
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
        'premium_intent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'socials'       => 'array',
        'opening_hours' => 'array',
        'quick_links'   => 'array',
        'services'      => 'array',
        'images'        => 'array',
        'reviews'       => 'array',
        'components'    => 'array',
        'rating'         => 'float',
        'review_count'   => 'integer',
        'premium_intent' => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;
}
