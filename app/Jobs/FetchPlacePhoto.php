<?php

namespace App\Jobs;

use App\Models\TemporarySite;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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

        $this->site->update([
            'data' => array_merge($this->site->data, [
                'images' => array_merge($this->site->data['images'] ?? [], [
                    $filename
                ])
            ])
        ]);
    }
}
