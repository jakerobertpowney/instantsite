<?php

namespace App\Http\Controllers;

use App\Models\MarketingSite;
use App\Services\FakeBusinessDataService;
use App\Traits\ParsesPlacesData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class ClaimController extends Controller
{
    use ParsesPlacesData;

    public function show(string $placesId, FakeBusinessDataService $fakeService, Request $request): Response|RedirectResponse
    {
        $captureMode = $request->boolean('capture');

        $marketing = MarketingSite::where('places_id', $placesId)->first();

        if (! $marketing || $marketing->status === 'dismissed') {
            abort(404);
        }

        $data = $this->fetchPlaceData($placesId);

        if (! $data) {
            abort(404);
        }

        // Fill any missing fields with convincing fake data (AI description is cached internally)
        $data = $fakeService->fill($data, $placesId);

        // Inject booking link from CSV data as a quick-link button
        if ($marketing->booking_appointment_link) {
            $data['quick_links'] = [
                ['label' => 'Book Appointment', 'link' => $marketing->booking_appointment_link],
            ];
        }

        $data['settings'] = ['header_bg' => ['type' => 'none']];

        $data['components'] = [
            'header'        => ['enabled' => true],
            'description'   => ['enabled' => true],
            'gallery'       => ['enabled' => true],
            'quick_actions' => ['enabled' => true],
            'reviews'       => ['enabled' => true],
            'contact'       => ['enabled' => true],
            'contact_form'  => ['enabled' => true],
            'services'      => ['enabled' => true],
        ];

        $businessName = $data['business_name'] ?? 'Your Business';

        return Inertia::render('claim/Index', [
            'data'            => $data,
            'placesId'        => $placesId,
            'businessName'    => $businessName,
            'metaTitle'       => $businessName . ' — Claim Your Free Website',
            'hasPreviewData'  => true,
            'captureMode'     => $captureMode,
        ]);
    }

    public function claim(string $placesId): RedirectResponse
    {
        MarketingSite::where('places_id', $placesId)
            ->update(['status' => 'claimed']);

        // Hand off to the normal onboarding discovery flow
        return redirect()->route('preview.discover', $placesId);
    }

    public function dismiss(string $placesId): RedirectResponse
    {
        MarketingSite::where('places_id', $placesId)->delete();

        return redirect()->route('claim.dismissed');
    }

    public function dismissed(): Response
    {
        return Inertia::render('claim/Dismissed');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function fetchPlaceData(string $placesId): ?array
    {
        $response = Http::withHeaders([
            'X-Goog-Api-Key'  => env('GOOGLE_API_KEY'),
            'X-Goog-FieldMask' => implode(',', [
                'id',
                'displayName',
                'addressComponents',
                'formattedAddress',
                'regularOpeningHours',
                'nationalPhoneNumber',
                'editorialSummary',
                'rating',
                'userRatingCount',
                'primaryTypeDisplayName',
                'photos',
            ]),
        ])->get("https://places.googleapis.com/v1/places/{$placesId}");

        if (! $response->successful()) {
            return null;
        }

        $raw = $response->json();

        [$city, $region] = $this->extractCityRegion($raw['addressComponents'] ?? []);
        $openingHours    = $this->extractOpeningHours($raw['regularOpeningHours']['periods'] ?? []);

        // Build proxied photo paths — Gallery prepends '/' so 'claim-photo/...' → '/claim-photo/...'
        $photos = collect($raw['photos'] ?? [])
            ->take(6)
            // Strip = padding so the encoded string only contains URL-safe chars
            ->map(fn ($photo) => 'claim-photo/' . rtrim(strtr(base64_encode($photo['name']), '+/', '-_'), '='))
            ->values()
            ->all();

        return [
            'places_id'         => $placesId,
            'google_places_id'  => $placesId,
            'business_name'     => $raw['displayName']['text'] ?? null,
            'business_type'     => $raw['primaryTypeDisplayName']['text'] ?? null,
            'description'       => $raw['editorialSummary']['text'] ?? $raw['description'] ?? null,
            'formatted_address' => $raw['formattedAddress'] ?? null,
            'city'              => $city,
            'region'            => $region,
            'phone'             => $raw['nationalPhoneNumber'] ?? null,
            'rating'            => isset($raw['rating']) ? (float) $raw['rating'] : null,
            'review_count'      => isset($raw['userRatingCount']) ? (int) $raw['userRatingCount'] : null,
            'opening_hours'     => $openingHours ?: null,
            'images'            => $photos,
            'reviews'           => [],
            'socials'           => [],
            'quick_links'       => [],
            'logo_path'         => null,
            'contact_email'     => 'preview@321sites.com',
        ];
    }
}
