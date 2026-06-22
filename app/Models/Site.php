<?php

namespace App\Models;

use App\Jobs\ProvisionCustomDomainSsl;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    /**
     * Model event hooks.
     */
    protected static function booted(): void
    {
        // When a custom domain becomes verified, kick off SSL provisioning via
        // Ploi. Fires for any code path that flips the flag through Eloquent.
        static::updated(function (Site $site): void {
            if (
                $site->wasChanged('domain_verified')
                && $site->domain_verified
                && $site->domain_type === 'custom'
                && filled($site->custom_domain)
            ) {
                ProvisionCustomDomainSsl::dispatch($site->id);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'places_id',
        'domain_type',
        'subdomain',
        'custom_domain',
        'domain_verified',
        'is_private',
        'connected_provider',
        'provider_token',
        'provider_zone_id',
        'dns_auto_configured',
        'meta_title',
        'meta_description',
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
        'settings',
        'premium_intent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'socials'             => 'array',
        'opening_hours'       => 'array',
        'quick_links'         => 'array',
        'services'            => 'array',
        'images'              => 'array',
        'reviews'             => 'array',
        'components'          => 'array',
        'settings'            => 'array',
        'rating'              => 'float',
        'review_count'        => 'integer',
        'domain_verified'     => 'boolean',
        'is_private'          => 'boolean',
        'dns_auto_configured' => 'boolean',
        'premium_intent'      => 'boolean',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The user who owns this site.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Contact form submissions received by this site.
     */
    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContactSubmission::class);
    }
}
