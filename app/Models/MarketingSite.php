<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarketingSite extends Model
{
    protected $fillable = [
        'places_id',
        'unique_code',
        'business_name',
        'formatted_address',
        'street',
        'city',
        'county',
        'postal_code',
        'country',
        'business_type',
        'phone',
        'website',
        'booking_appointment_link',
        'status',
        'stannp_id',
        'stannp_sent_at',
    ];

    protected $casts = [
        'stannp_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketingSite $site) {
            if (empty($site->unique_code)) {
                do {
                    $code = strtolower(Str::random(6));
                } while (static::where('unique_code', $code)->exists());

                $site->unique_code = $code;
            }
        });
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }
}
