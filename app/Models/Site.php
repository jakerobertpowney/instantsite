<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
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
        'data',
        'meta_title',
        'meta_description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data'                => 'array',
        'domain_verified'     => 'boolean',
        'is_private'          => 'boolean',
        'dns_auto_configured' => 'boolean',
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
