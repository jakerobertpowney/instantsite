<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchSitePhoto implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @param  int    $siteId    The published Site record to update
     * @param  array  $photo     Raw Google Places photo object (must contain 'name' key)
     * @param  string $placesId  Used to build a stable sub-directory path
     * @param  int    $index     1-based position — stored as {index}.jpg
     */
    public function __construct(
        private readonly int    $siteId,
        private readonly array  $photo,
        private readonly string $placesId,
        private readonly int    $index,
    ) {}

    public function handle(): void
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->get('https://places.googleapis.com/v1/' . $this->photo['name'] . '/media', [
            'key'          => env('GOOGLE_API_KEY'),
            'maxHeightPx'  => 1000,
        ]);

        $response->throw();

        // Store under a per-site directory so paths are stable and human-readable.
        // e.g.  storage/app/public/images/{placesId}/1.jpg
        $diskPath   = 'images/' . $this->placesId . '/' . $this->index . '.jpg';
        $publicPath = 'storage/' . $diskPath;   // root-relative URL when prepended with /

        Storage::disk('public')->put($diskPath, $response->body());

        // Atomically append the new path to images column.
        // Using a DB transaction + lockForUpdate prevents concurrent jobs from
        // overwriting each other's writes (the classic lost-update race condition).
        DB::transaction(function () use ($publicPath) {
            $site = Site::lockForUpdate()->find($this->siteId);
            if (! $site) {
                return;
            }

            $existing = $site->images ?? [];

            // Avoid duplicates if the job retries
            if (in_array($publicPath, $existing, true)) {
                return;
            }

            $site->images = array_merge($existing, [$publicPath]);
            $site->save();
        });
    }
}
