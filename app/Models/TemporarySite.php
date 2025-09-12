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
        'data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;
}
