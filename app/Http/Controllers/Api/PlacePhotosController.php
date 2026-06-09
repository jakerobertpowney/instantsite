<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class PlacePhotosController extends Controller
{
    /**
     * Return a list of Google photo proxy URLs for a given place ID.
     * Photos are fetched live and never stored — this is purely for the
     * "here's what's currently on your Google listing" inspiration panel.
     */
    public function index(string $placeId): JsonResponse
    {
        $response = Http::withHeaders([
            'X-Goog-Api-Key'  => env('GOOGLE_API_KEY'),
            'X-Goog-FieldMask' => 'photos',
        ])->get('https://places.googleapis.com/v1/places/' . $placeId);

        if (! $response->successful()) {
            return response()->json(['photos' => []]);
        }

        $photos = collect($response->json('photos') ?? [])
            ->take(9)
            ->map(fn ($p) => [
                'url'  => route('api.place.photo.proxy', ['name' => base64_encode($p['name'])]),
                'name' => $p['name'],
            ])
            ->values();

        return response()->json(['photos' => $photos]);
    }

    /**
     * Proxy a single Google Places photo to the browser.
     * The photo is streamed through — never written to disk.
     */
    public function proxy(string $name): Response
    {
        $photoName = base64_decode($name);

        $response = Http::withOptions(['stream' => true])
            ->get('https://places.googleapis.com/v1/' . $photoName . '/media', [
                'key'         => env('GOOGLE_API_KEY'),
                'maxHeightPx' => 800,
            ]);

        if (! $response->successful()) {
            abort(404);
        }

        $contentType = $response->header('Content-Type') ?? 'image/jpeg';

        return response($response->body(), 200, [
            'Content-Type'  => $contentType,
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
