<?php

namespace App\Listeners;

use App\Models\Site;
use App\Models\TemporarySite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

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

        if (!$temporarySite) {
            return;
        }

        $data = $temporarySite->data ?? [];

        // Auto-populate meta title from business name
        $businessName = $data['displayName']['text'] ?? null;
        $metaTitle = $businessName ?? null;

        // Auto-populate meta description from Google editorial summary or description
        $metaDescription = $data['editorialSummary']['text']
            ?? $data['description']
            ?? null;

        // Auto-generate a subdomain slug from the business name
        $subdomain = $businessName
            ? Str::slug($businessName)
            : null;

        Site::create([
            'user_id'          => $event->user->id,
            'places_id'        => $temporarySite->places_id,
            'data'             => $data,
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDescription ? Str::limit($metaDescription, 158) : null,
            'subdomain'        => $subdomain,
            'domain_type'      => $subdomain ? 'subdomain' : 'draft',
        ]);

    }
}
