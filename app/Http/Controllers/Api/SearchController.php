<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchPlacesRequest;
use App\Jobs\FetchPlaceDetails;
use Illuminate\Bus\Batch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function discover(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'string']]);

        $batch = Bus::batch([
            new FetchPlaceDetails($request->string('id')->value())
        ])->then(function (Batch $batch) {
            // All jobs completed successfully...
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            // The batch has finished executing...
        })->dispatch();

        return response()->json(['batchId' => $batch->id]);
    }

    public function poll(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['error' => 'Batch not found'], 404);
        }

        return response()->json($batch->finished());
    }
}
