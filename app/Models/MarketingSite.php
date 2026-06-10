<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingSite extends Model
{
    protected $fillable = [
        'places_id',
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
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }
}
