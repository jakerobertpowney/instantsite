<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StockPhotoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $apiKey = config('services.pexels.key');

        if (!$apiKey) {
            return response()->json(['photos' => [], 'available' => false]);
        }

        $query = $request->input('q', 'business');
        $perPage = min((int) $request->input('per_page', 12), 24);

        $response = Http::withHeaders([
                'Authorization' => $apiKey,
            ])
            ->timeout(8)
            ->get('https://api.pexels.com/v1/search', [
                'query'       => $query,
                'per_page'    => $perPage,
                'orientation' => 'landscape',
            ]);

        if (!$response->ok()) {
            return response()->json(['photos' => [], 'available' => true, 'error' => 'Search failed']);
        }

        $photos = collect($response->json('photos', []))
            ->map(fn($p) => [
                'id'          => $p['id'],
                'url'         => $p['src']['large2x'] ?? $p['src']['large'] ?? $p['src']['original'],
                'thumb'       => $p['src']['medium'],
                'alt'         => $p['alt'] ?? '',
                'credit'      => $p['photographer'],
                'credit_url'  => $p['photographer_url'],
            ])
            ->values()
            ->all();

        return response()->json(['photos' => $photos, 'available' => true]);
    }
}
