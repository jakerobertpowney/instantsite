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

        $businessName = $temporarySite->business_name;
        $businessType = $temporarySite->business_type;
        $city         = $temporarySite->city;
        $location     = $city;

        $metaTitle       = $this->buildMetaTitle($businessName, $businessType, $location);
        $metaDescription = $this->buildMetaDescription($temporarySite, $businessName, $businessType, $location);

        // Auto-generate a subdomain slug from the business name
        $subdomain = $businessName
            ? Str::slug($businessName)
            : null;

        $site = Site::firstOrCreate(
            ['user_id' => $event->user->id],
            [
                'places_id'         => $temporarySite->places_id,
                'business_name'     => $temporarySite->business_name,
                'business_type'     => $temporarySite->business_type,
                'description'       => $temporarySite->description,
                'logo_path'         => $temporarySite->logo_path,
                'formatted_address' => $temporarySite->formatted_address,
                'city'              => $temporarySite->city,
                'region'            => $temporarySite->region,
                'phone'             => $temporarySite->phone,
                'whatsapp_number'   => $temporarySite->whatsapp_number,
                'website_url'       => $temporarySite->website_url,
                'contact_email'     => $temporarySite->contact_email,
                'socials'           => $temporarySite->socials,
                'opening_hours'     => $temporarySite->opening_hours,
                'quick_links'       => $temporarySite->quick_links,
                'services'          => $temporarySite->services,
                'images'            => $temporarySite->images,
                'rating'            => $temporarySite->rating,
                'review_count'      => $temporarySite->review_count,
                'reviews'           => $temporarySite->reviews,
                'components'        => $temporarySite->components,
                'premium_intent'    => $temporarySite->premium_intent ?? false,
                'services_heading'  => $temporarySite->services_heading,
                'services_cta_label'=> $temporarySite->services_cta_label,
                'services_cta_link' => $temporarySite->services_cta_link,
                'meta_title'        => $metaTitle,
                'meta_description'  => $metaDescription,
                'subdomain'         => $subdomain,
                'domain_type'       => $subdomain ? 'subdomain' : 'draft',
            ]
        );

        // Dispatch photo download jobs for any photos stored in the TemporarySite.
        // FetchSitePhoto writes to the images column on the published Site.
        $placesId    = $temporarySite->places_id;
        $storagePath = "images/{$placesId}";
        $existingFiles = collect(Storage::disk('public')->files($storagePath))
            ->map(fn ($f) => basename($f))
            ->toArray();

        // We no longer have raw photos[] from Google at this point — images were already
        // downloaded by FetchPlacePhoto into the TemporarySite.images column.
        // Copy those images directly to the Site record if not already there.
        if (empty($site->images) && !empty($temporarySite->images)) {
            $site->images = $temporarySite->images;
            $site->save();
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

    private function buildMetaDescription(TemporarySite $site, ?string $businessName, ?string $businessType, ?string $location): ?string
    {
        $googleDescription = $site->description;

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
}
