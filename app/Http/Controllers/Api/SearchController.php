<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchPlacesRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function places(SearchPlacesRequest $request): JsonResponse
    {

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => env('GOOGLE_API_KEY'),
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress'
        ])->post('https://places.googleapis.com/v1/places:searchText', [
            'textQuery' => $request->string('query')->value()
        ]);

        $response->throw();

        return response()->json($response->json());

    }

    public function poll(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);
        return response()->json($batch->finished());
    }
}
