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
            'X-Goog-FieldMask' => 'id,displayName,addressComponents,formattedAddress,regularOpeningHours,nationalPhoneNumber,editorialSummary,rating,userRatingCount,primaryTypeDisplayName,websiteUri'
        ])->get('https://places.googleapis.com/v1/places/' . $this->id);

        $response->throw();

        $data = $response->json();

        // Extract city and region from addressComponents
        [$city, $region] = $this->extractCityRegion($data['addressComponents'] ?? []);

        // Convert Google's regularOpeningHours.periods to our flat array format
        $openingHours = $this->extractOpeningHours($data['regularOpeningHours']['periods'] ?? []);

        /**
         * Store extracted fields as individual columns — do NOT store raw Google data.
         */
        $site = TemporarySite::create([
            'places_id'         => $this->id,
            'business_name'     => $data['displayName']['text'] ?? null,
            'business_type'     => $data['primaryTypeDisplayName']['text'] ?? null,
            'description'       => $data['editorialSummary']['text'] ?? $data['description'] ?? null,
            'formatted_address' => $data['formattedAddress'] ?? null,
            'city'              => $city,
            'region'            => $region,
            'phone'             => $data['nationalPhoneNumber'] ?? null,
            'website_url'       => $data['websiteUri'] ?? null,
            'rating'            => isset($data['rating']) ? (float) $data['rating'] : null,
            'review_count'      => isset($data['userRatingCount']) ? (int) $data['userRatingCount'] : null,
            'opening_hours'     => $openingHours,
        ]);

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

    /**
     * Extract city and region (administrative_area_level_1) from addressComponents.
     * Returns [city, region].
     *
     * @param  array  $addressComponents
     * @return array{0: string|null, 1: string|null}
     */
    private function extractCityRegion(array $addressComponents): array
    {
        $city   = null;
        $region = null;

        foreach ($addressComponents as $component) {
            $types = $component['types'] ?? [];

            if ($city === null && (in_array('locality', $types, true) || in_array('postal_town', $types, true))) {
                $city = $component['longText'] ?? $component['shortText'] ?? null;
            }

            if ($region === null && in_array('administrative_area_level_1', $types, true)) {
                $region = $component['longText'] ?? $component['shortText'] ?? null;
            }
        }

        return [$city, $region];
    }

    /**
     * Convert Google's regularOpeningHours.periods array into a flat 7-day array.
     *
     * Input format:  [{open:{day,hour,minute}, close:{day,hour,minute}}, ...]
     * Output format: [{day:"Monday",open:"09:00",close:"17:00",closed:false}, ...]
     *
     * Days are ordered Sun–Sat to match Google's 0-indexed day convention.
     * Any day not present in the periods array is marked as closed:true.
     *
     * @param  array  $periods
     * @return array<int, array{day:string,open:string,close:string,closed:bool}>
     */
    private function extractOpeningHours(array $periods): array
    {
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Build a lookup indexed by day number (0=Sunday … 6=Saturday)
        $byDay = [];
        foreach ($periods as $period) {
            $dayIndex = $period['open']['day'] ?? null;
            if ($dayIndex === null) {
                continue;
            }

            $openHour  = $period['open']['hour']   ?? 0;
            $openMin   = $period['open']['minute']  ?? 0;
            $closeHour = $period['close']['hour']  ?? 0;
            $closeMin  = $period['close']['minute'] ?? 0;

            $byDay[$dayIndex] = [
                'open'   => sprintf('%02d:%02d', $openHour, $openMin),
                'close'  => sprintf('%02d:%02d', $closeHour, $closeMin),
                'closed' => false,
            ];
        }

        $result = [];
        foreach ($dayNames as $index => $name) {
            if (isset($byDay[$index])) {
                $result[] = array_merge(['day' => $name], $byDay[$index]);
            } else {
                $result[] = [
                    'day'    => $name,
                    'open'   => '09:00',
                    'close'  => '17:00',
                    'closed' => true,
                ];
            }
        }

        return $result;
    }
}
