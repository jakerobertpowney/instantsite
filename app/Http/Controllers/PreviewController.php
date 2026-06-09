<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setup\StoreSetupRequest;
use App\Jobs\FetchPlaceDetails;
use App\Models\Site;
use App\Models\TemporarySite;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PreviewController extends Controller
{

    /**
     * @throws Throwable
     */
    public function discover(string $id): Response|RedirectResponse
    {
        if (Site::where('places_id', $id)->exists()) {
            return redirect()->route('home')->with(
                'error',
                'A website has already been created for this business.'
            );
        }

        $batch = Bus::batch([
            new FetchPlaceDetails($id)
        ])->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->dispatch();

        return Inertia::render('preview/Discover', [
            'id' => $id,
            'batchId' => $batch->id,
        ]);
    }

    public function setup(string $id): Response
    {
        $site = TemporarySite::where('places_id', $id)->latest()->first();

        return Inertia::render('preview/Setup', [
            'id'   => $id,
            'site' => $site ?? null,
        ]);
    }

    /**
     * Blank-slate setup wizard (no Google Business listing required).
     */
    public function create(): Response
    {
        return Inertia::render('preview/Setup', [
            'id'          => null,
            'site'        => null,
            'isBlankFlow' => true,
        ]);
    }

    public function store(StoreSetupRequest $request, string $id): RedirectResponse
    {
        $site = TemporarySite::where('places_id', $id)->latest()->first();

        if (!$site) {
            return redirect()->route('preview.discover', $id);
        }

        $logoPath = $site->logo_path;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store(
                'images/' . ($site->places_id ?? 'manual') . '/logos',
                'public'
            );
        } elseif ($request->filled('suggested_logo_url')) {
            $downloaded = $this->downloadSuggestedLogo(
                $request->string('suggested_logo_url')->value(),
                $site->places_id ?? 'manual'
            );
            if ($downloaded) {
                $logoPath = $downloaded;
            }
        }

        // Sanitise and store the confirmed services list from the wizard step.
        $services = collect($request->get('services', []))
            ->filter(fn($s) => !empty($s['name']))
            ->values()
            ->map(fn($s) => [
                'id'          => $s['id'] ?? (string) Str::uuid(),
                'name'        => $s['name'],
                'description' => $s['description'] ?? null,
                'price'       => $s['price'] ?? null,
                'show_price'  => filter_var($s['show_price'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'featured'    => filter_var($s['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->all();

        $openingHours = $request->get('opening_hours');
        if (!is_array($openingHours) || empty($openingHours)) {
            $openingHours = $site->opening_hours;
        } else {
            // FormData serialises booleans as "1"/"0" strings — normalise back to real booleans
            $openingHours = array_map(fn ($h) => [
                'day'    => $h['day']   ?? '',
                'open'   => $h['open']  ?? '',
                'close'  => $h['close'] ?? '',
                'closed' => filter_var($h['closed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ], $openingHours);
        }

        // Apply removals first, then append new uploads
        $removeSet  = collect($request->input('remove_photos', []))->flip();
        $imagePaths = collect($site->images ?? [])
            ->reject(fn ($p) => $removeSet->has($p))
            ->values()
            ->all();
        if ($request->hasFile('photos')) {
            $dir = 'images/' . ($site->places_id ?? 'site-' . $site->id) . '/photos';
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store($dir, 'public');
                $imagePaths[] = 'storage/' . $path;
            }
        }

        $site->update([
            'business_name'     => $request->get('business_name', $site->business_name),
            'business_type'     => $request->get('business_type', $site->business_type),
            'formatted_address' => $request->get('formatted_address', $site->formatted_address),
            'city'              => $request->get('city', $site->city),
            'region'            => $request->get('region', $site->region),
            'phone'             => $request->get('phone', $site->phone),
            'whatsapp_number'   => $request->get('whatsapp_number', $site->whatsapp_number),
            'website_url'       => $request->get('website_url', $site->website_url),
            'logo_path'         => $logoPath,
            'description'       => $request->get('description', $site->description),
            'contact_email'     => $request->get('contact', $site->contact_email),
            'socials'           => $request->get('socials', $site->socials),
            'opening_hours'     => $openingHours,
            'quick_links'       => $request->get('quickLinks', $site->quick_links),
            'services'          => !empty($services) ? $services : $site->services,
            'images'            => $imagePaths,
            'rating'            => $request->filled('rating') ? (float) $request->input('rating') : $site->rating,
            'review_count'      => $request->filled('review_count') ? (int) $request->input('review_count') : $site->review_count,
            'reviews'           => $request->has('reviews') ? $request->input('reviews', []) : ($site->reviews ?? []),
            'premium_intent'    => filter_var($request->input('premium', $site->premium_intent ?? false), FILTER_VALIDATE_BOOLEAN),
        ]);

        return redirect()->route('preview.show', $id);
    }

    /**
     * Store a new blank-flow TemporarySite (no Google Business listing).
     */
    public function storeBlank(StoreSetupRequest $request): RedirectResponse
    {
        $fakeId = 'manual-' . Str::uuid();

        $logoPath = null;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store(
                'images/' . $fakeId . '/logos',
                'public'
            );
        }

        // Sanitise services
        $services = collect($request->get('services', []))
            ->filter(fn($s) => !empty($s['name']))
            ->values()
            ->map(fn($s) => [
                'id'          => $s['id'] ?? (string) Str::uuid(),
                'name'        => $s['name'],
                'description' => $s['description'] ?? null,
                'price'       => $s['price'] ?? null,
                'show_price'  => filter_var($s['show_price'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'featured'    => filter_var($s['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->all();

        $openingHours = $request->get('opening_hours');
        if (!is_array($openingHours) || empty($openingHours)) {
            $openingHours = $this->defaultOpeningHours();
        } else {
            $openingHours = array_map(fn ($h) => [
                'day'    => $h['day']   ?? '',
                'open'   => $h['open']  ?? '',
                'close'  => $h['close'] ?? '',
                'closed' => filter_var($h['closed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ], $openingHours);
        }

        $removeSet  = collect($request->input('remove_photos', []))->flip();
        $imagePaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('images/' . $fakeId . '/photos', 'public');
                $imagePaths[] = 'storage/' . $path;
            }
        }

        $site = TemporarySite::create([
            'places_id'         => $fakeId,
            'business_name'     => $request->get('business_name'),
            'business_type'     => $request->get('business_type'),
            'formatted_address' => $request->get('formatted_address'),
            'city'              => $request->get('city'),
            'region'            => $request->get('region'),
            'phone'             => $request->get('phone'),
            'whatsapp_number'   => $request->get('whatsapp_number'),
            'website_url'       => $request->get('website_url'),
            'logo_path'         => $logoPath,
            'description'       => $request->get('description'),
            'contact_email'     => $request->get('contact'),
            'socials'           => $request->get('socials'),
            'opening_hours'     => $openingHours,
            'quick_links'       => $request->get('quickLinks'),
            'services'          => !empty($services) ? $services : null,
            'images'            => $imagePaths,
            'rating'            => $request->filled('rating') ? (float) $request->input('rating') : null,
            'review_count'      => $request->filled('review_count') ? (int) $request->input('review_count') : null,
            'reviews'           => $request->input('reviews', []),
            'premium_intent'    => filter_var($request->input('premium', false), FILTER_VALIDATE_BOOLEAN),
        ]);

        return redirect()->route('preview.show', $fakeId);
    }

    public function show(string $id): Response|RedirectResponse
    {
        $site = TemporarySite::where('places_id', $id)->latest()->first();

        if (!$site) {
            return redirect()->route('preview.discover', $id);
        }

        return Inertia::render('preview/Index', [
            'id'        => $id,
            'data'      => $this->buildSiteData($site),
            'isPremium' => (bool) $site->premium_intent,
        ]);
    }

    public function complete(Request $request, string $id): RedirectResponse
    {
        $request->session()->put('places_id', $id);
        return redirect()->route('register');
    }

    /**
     * Build a flat data array from a TemporarySite's individual columns.
     */
    private function buildSiteData(TemporarySite $site): array
    {
        return [
            'business_name'      => $site->business_name,
            'business_type'      => $site->business_type,
            'description'        => $site->description,
            'logo_path'          => $site->logo_path,
            'formatted_address'  => $site->formatted_address,
            'city'               => $site->city,
            'region'             => $site->region,
            'phone'              => $site->phone,
            'whatsapp_number'    => $site->whatsapp_number,
            'website_url'        => $site->website_url,
            'contact_email'      => $site->contact_email,
            'socials'            => $site->socials ?? [],
            'opening_hours'      => $site->opening_hours ?? [],
            'quick_links'        => $site->quick_links ?? [],
            'services'           => $site->services ?? [],
            'images'             => $site->images ?? [],
            'rating'             => $site->rating,
            'review_count'       => $site->review_count,
            'reviews'            => $site->reviews ?? [],
            'google_places_id'   => ($site->places_id && !str_starts_with($site->places_id ?? '', 'manual-')) ? $site->places_id : null,
            'components'         => $site->components ?? $this->defaultComponents(),
            'services_heading'   => $site->services_heading ?? 'Our Services',
            'services_cta_label' => $site->services_cta_label ?? '',
            'services_cta_link'  => $site->services_cta_link ?? '',
        ];
    }

    private function defaultComponents(): array
    {
        return [
            'header'        => ['enabled' => true],
            'description'   => ['enabled' => true],
            'gallery'       => ['enabled' => true],
            'quick_actions' => ['enabled' => true],
            'reviews'       => ['enabled' => true],
            'contact'       => ['enabled' => true],
            'contact_form'  => ['enabled' => true],
            'services'      => ['enabled' => true],
        ];
    }

    private function defaultOpeningHours(): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return array_map(fn($day) => [
            'day'    => $day,
            'open'   => '09:00',
            'close'  => '17:00',
            'closed' => true,
        ], $days);
    }

    /**
     * Download an externally-suggested logo (e.g. from Clearbit) and store it locally.
     * Returns the stored path, or null on failure.
     */
    private function downloadSuggestedLogo(string $url, string $placesId): ?string
    {
        // Only allow HTTPS requests to trusted logo providers
        $allowedHosts = ['logo.clearbit.com'];
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if (!in_array($host, $allowedHosts, true)) {
                return null;
            }

            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type') ?? 'image/png';
            $extension = match (true) {
                str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
                str_contains($contentType, 'svg')  => 'svg',
                default                             => 'png',
            };

            $path = 'images/' . $placesId . '/logos/suggested.' . $extension;
            Storage::disk('public')->put($path, $response->body());

            return $path;

        } catch (\Throwable $e) {
            Log::warning('Failed to download suggested logo: ' . $e->getMessage());
            return null;
        }
    }
}
