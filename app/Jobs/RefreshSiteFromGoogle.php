<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RefreshSiteFromGoogle implements ShouldQueue
{
    use QueueableTrait;

    public int $timeout = 60;

    public function __construct(private readonly int $siteId) {}

    public function handle(): void
    {
        $site = Site::find($this->siteId);

        if (! $site || ! $site->places_id) {
            return;
        }

        $response = Http::withHeaders([
            'Content-Type'    => 'application/json',
            'X-Goog-Api-Key'  => env('GOOGLE_API_KEY'),
            'X-Goog-FieldMask' => 'id,displayName,photos,addressComponents,formattedAddress,regularOpeningHours,nationalPhoneNumber,editorialSummary,reviews,reviewSummary,rating,userRatingCount,primaryTypeDisplayName,websiteUri',
        ])->get('https://places.googleapis.com/v1/places/' . $site->places_id);

        if (! $response->successful()) {
            return;
        }

        $fresh = $response->json();

        // Keys that belong entirely to the user — never overwrite these with Google data.
        $userKeys = [
            'overrides', 'components', 'services', 'palette', 'headerBackground',
            'socials', 'quickLinks', 'contact_email', 'contact_whatsapp',
            'google_analytics_id', 'allow_indexing', 'hidden_reviews',
            'google_synced_at',
            'images',   // locally-downloaded photo paths — preserve across refreshes
        ];

        $existing = $site->data ?? [];

        // Build the merged payload: start with fresh Google data, then layer
        // back any user-controlled keys from the existing data.
        $merged = $fresh;
        foreach ($userKeys as $key) {
            if (array_key_exists($key, $existing)) {
                $merged[$key] = $existing[$key];
            }
        }

        // Normalise legacy image paths.  Old downloads stored bare paths like
        // "images/uuid.jpg"; the correct root-relative URL requires the
        // "storage/" prefix so '/' . $path resolves to /storage/images/...
        $merged['images'] = collect($merged['images'] ?? [])
            ->map(function (string $img): string {
                if (str_starts_with($img, 'storage/') || str_starts_with($img, '/') || str_contains($img, '://')) {
                    return $img;
                }
                return 'storage/' . $img;
            })
            ->all();

        $merged['google_synced_at'] = now()->toISOString();

        $site->data = $merged;
        $site->save();

        // Re-download any photos that are not yet stored locally.
        // Uses the stable per-site directory: images/{placesId}/{n}.jpg
        $placesId    = $site->places_id;
        $storagePath = "images/{$placesId}";

        $existingFiles = collect(Storage::disk('public')->files($storagePath))
            ->map(fn ($f) => basename($f))
            ->toArray();

        // If data.images is empty (legacy sites where it was wiped or never populated),
        // force a full re-download of all photos so the Gallery recovers automatically.
        $imagesPopulated = ! empty($merged['images']);

        $photoJobs = [];
        foreach ($fresh['photos'] ?? [] as $index => $photo) {
            $filename = ($index + 1) . '.jpg';
            $alreadyOnDisk = in_array($filename, $existingFiles, true);

            // Download if: file is missing from disk, OR images list is empty
            // (forces repopulation of data.images for legacy sites).
            if (! $alreadyOnDisk || ! $imagesPopulated) {
                $photoJobs[] = new FetchSitePhoto($site->id, $photo, $placesId, $index + 1);
            }
        }

        if (! empty($photoJobs)) {
            foreach ($photoJobs as $job) {
                dispatch($job);
            }
        }
    }
}
