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
            'id' => $id,
            'site' => $site ?? []
        ]);
    }

    public function store(StoreSetupRequest $request, string $id): RedirectResponse
    {
        $site = TemporarySite::where('places_id', $id)->latest()->first();

        if (!$site) {
            return redirect()->route('preview.discover', $id);
        }

        $logo = null;

        if ($request->hasFile('logo')) {
            // User uploaded their own logo — store it.
            $logo = $request->file('logo')->store(
                'images/' . $site->places_id . '/logos',
                'public'
            );
        } elseif ($request->filled('suggested_logo_url')) {
            // User accepted a Clearbit/auto-suggested logo — download and store it.
            $logo = $this->downloadSuggestedLogo(
                $request->string('suggested_logo_url')->value(),
                $site->places_id
            );
        }

        // Sanitise and store the confirmed services list from the wizard step.
        $services = collect($request->get('services', []))
            ->filter(fn($s) => !empty($s['name']))
            ->values()
            ->map(fn($s) => [
                'id'          => $s['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                'name'        => $s['name'],
                'description' => $s['description'] ?? null,
                'price'       => $s['price'] ?? null,
                'show_price'  => filter_var($s['show_price'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'featured'    => filter_var($s['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->all();

        $site->update([
           'data' => array_merge($site->data, [
               'logo'        => $logo,
               'description' => $request->get('description'),
               'socials'     => $request->get('socials'),
               'premium'     => $request->get('premium'),
               'contact'     => $request->get('contact'),
               'quickLinks'  => $request->get('quickLinks'),
               'services'    => $services,
           ])
        ]);

        return redirect()->route('preview.show', $id);
    }

    public function show(string $id): Response|RedirectResponse
    {
        $site = TemporarySite::where('places_id', $id)->latest()->first();

        if(!$site) {
            return redirect()->route('preview.discover', $id);
        }

        return Inertia::render('preview/Index', [
            'id' => $id,
            'data' => $site->data
        ]);
    }

    public function complete(Request $request, string $id): RedirectResponse
    {
        $request->session()->put('places_id', $id);
        return redirect()->route('register');
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
