<?php

namespace App\Listeners;

use App\Jobs\FetchSitePhoto;
use App\Models\Site;
use App\Models\TemporarySite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Storage;
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

        $businessName = $data['displayName']['text'] ?? null;
        $businessType = $data['primaryTypeDisplayName']['text'] ?? null;
        $location = $this->extractLocation($data['addressComponents'] ?? []);

        $metaTitle = $this->buildMetaTitle($businessName, $businessType, $location);
        $metaDescription = $this->buildMetaDescription($data, $businessName, $businessType, $location);

        // Auto-generate a subdomain slug from the business name
        $subdomain = $businessName
            ? Str::slug($businessName)
            : null;

        $site = Site::firstOrCreate(
            ['places_id' => $temporarySite->places_id],
            [
                'user_id'          => $event->user->id,
                'data'             => $data,
                'meta_title'       => $metaTitle,
                'meta_description' => $metaDescription,
                'subdomain'        => $subdomain,
                'domain_type'      => $subdomain ? 'subdomain' : 'draft',
            ]
        );

        // Dispatch photo download jobs for every photo in the Google Places data.
        // FetchPlacePhoto only downloads to TemporarySite — which may not have finished
        // by the time the user registers. FetchSitePhoto writes to data.images on the
        // published Site, which is what Gallery.vue reads from.
        $placesId      = $temporarySite->places_id;
        $storagePath   = "images/{$placesId}";
        $existingFiles = collect(Storage::disk('public')->files($storagePath))
            ->map(fn ($f) => basename($f))
            ->toArray();

        foreach ($data['photos'] ?? [] as $index => $photo) {
            $filename = ($index + 1) . '.jpg';
            if (! in_array($filename, $existingFiles, true)) {
                dispatch(new FetchSitePhoto($site->id, $photo, $placesId, $index + 1));
            }
        }
    }

    private function buildMetaTitle(?string $businessName, ?string $businessType, ?string $location): ?string
    {
        if (!$businessName) {
            return null;
        }

        $parts = [$businessName];

        if ($businessType && $location) {
            $parts[] = $businessType . ' in ' . $location;
        } elseif ($businessType) {
            $parts[] = $businessType;
        } elseif ($location) {
            $parts[] = 'in ' . $location;
        }

        return Str::limit(implode(' - ', $parts), 60, '');
    }

    private function buildMetaDescription(array $data, ?string $businessName, ?string $businessType, ?string $location): ?string
    {
        $googleDescription = $data['editorialSummary']['text'] ?? $data['description'] ?? null;

        if ($googleDescription) {
            return Str::limit(Str::squish($googleDescription), 155, '');
        }

        if (!$businessName) {
            return null;
        }

        $summaryParts = array_filter([
            $businessType,
            $location ? 'in ' . $location : null,
        ]);

        $summary = $summaryParts ? implode(' ', $summaryParts) : 'business';

        return Str::limit(
            Str::squish("Find {$businessName}, {$summary}. See opening hours, photos, reviews, and contact details."),
            155,
            ''
        );
    }

    private function extractLocation(array $addressComponents): ?string
    {
        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];

            if (in_array('locality', $types, true) || in_array('postal_town', $types, true)) {
                return $component['longText'] ?? $component['shortText'] ?? null;
            }
        }

        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];

            if (in_array('administrative_area_level_2', $types, true) || in_array('administrative_area_level_1', $types, true)) {
                return $component['longText'] ?? $component['shortText'] ?? null;
            }
        }

        return null;
    }
}
