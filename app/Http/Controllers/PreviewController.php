<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setup\StoreSetupRequest;
use App\Jobs\FetchPlaceDetails;
use App\Models\TemporarySite;
use Illuminate\Bus\Batch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PreviewController extends Controller
{

    /**
     * @throws Throwable
     */
    public function discover(string $id): Response
    {
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

        if($request->hasFile('logo')) {
            // Store uploaded logo in the public logos folder.
            $logo = $request->file('logo')->store(
                'images/' . $site->places_id . '/logos',
                'public'
            );
        }

        $site->update([
           'data' => array_merge($site->data, [
               'logo' => $logo ?? null,
               'description' => $request->get('description'),
               'socials' => $request->get('socials'),
               'premium' => $request->get('premium'),
               'contact' => $request->get('contact'),
               'quicklinks' => $request->get('quicklinks'),
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
}

