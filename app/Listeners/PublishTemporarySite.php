<?php

namespace App\Listeners;

use App\Models\Site;
use App\Models\TemporarySite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;

class PublishTemporarySite
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {

        $temporarySite = TemporarySite::where('places_id', $event->user->places_id)->latest()->first();

        Site::create([
            'user_id' => $event->user->id,
            ...$temporarySite->only([
                'places_id',
                'data'
            ])
        ]);

    }
}
