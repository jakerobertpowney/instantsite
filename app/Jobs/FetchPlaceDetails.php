<?php

namespace App\Jobs;

use App\Models\TemporarySite;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchPlaceDetails implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $id
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => env('GOOGLE_API_KEY'),
            'X-Goog-FieldMask' => 'id,displayName,photos,addressComponents,formattedAddress,regularOpeningHours,nationalPhoneNumber,editorialSummary,reviews,reviewSummary,rating,userRatingCount,primaryTypeDisplayName,websiteUri'
        ])->get('https://places.googleapis.com/v1/places/' . $this->id);

        $response->throw();

        $data = $response->json();

        /**
         * Store data in database
         */
        $site = TemporarySite::create([
           'places_id' => $this->id,
           'data' => $data,
        ]);

        /**
         * Loop through photos to fetch URL
         */
        foreach($data['photos'] ?? [] as $photo) {
            $this->batch()->add(new FetchPlacePhoto($photo, $site));
        }

        /**
         * If the business has a website, scrape it for social media links
         */
        if (!empty($data['websiteUri'])) {
            $this->batch()->add(new FetchSocialLinks($site, $data['websiteUri']));
        }

        /**
         * Always attempt to fetch services — FetchBusinessServices handles
         * the 3-tier fallback (own website → internet scan → AI generation).
         */
        $this->batch()->add(new FetchBusinessServices($site));

    }
}
