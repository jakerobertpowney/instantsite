<?php

namespace App\Jobs;

use App\Models\TemporarySite;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchPlacePhoto implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $photo,
        private TemporarySite $site
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the image data
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->get('https://places.googleapis.com/v1/'.$this->photo['name'].'/media', [
            'key' => env('GOOGLE_API_KEY'),
            'maxHeightPx' => 1000
        ]);

        $response->throw();

        $filename = 'images/' . Str::uuid() . '.jpg';

        // Save the image data to a file
        Storage::disk('public')->put($filename, $response->body());

        // Store the path with the 'storage/' prefix so that prepending '/' gives
        // a valid root-relative URL:  /storage/images/{uuid}.jpg
        $publicPath = 'storage/' . $filename;

        // Atomically append to data.images — avoids the lost-update race condition
        // when multiple FetchPlacePhoto jobs run concurrently for the same site.
        $siteId = $this->site->id;
        DB::transaction(function () use ($siteId, $publicPath) {
            $site = TemporarySite::lockForUpdate()->find($siteId);
            if (! $site) {
                return;
            }

            $data           = $site->data ?? [];
            $existing       = $data['images'] ?? [];

            if (in_array($publicPath, $existing, true)) {
                return; // idempotent on retry
            }

            $data['images'] = array_merge($existing, [$publicPath]);
            $site->data     = $data;
            $site->save();
        });
    }
}
