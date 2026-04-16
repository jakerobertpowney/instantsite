<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\Admin\UpdateDashboardSettingsRequest;
use App\Http\Requests\Admin\UpdateDashboardSiteRequest;
use App\Http\Requests\Admin\UpdateDashboardComponentsRequest;

class DashboardController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if(!$site) {
            return redirect()->route('home');
        }

        return Inertia::render('Dashboard', [
            'site'      => $site,
            'appDomain' => env('APP_DOMAIN', 'instantsite.test'),
            'isPremium' => auth()->user()->subscribed('default'),
        ]);
    }

    public function site(UpdateDashboardSiteRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();
        $site->update($request->all());

        return redirect()->back();
    }

    public function settings(UpdateDashboardSettingsRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        $data = $site->data ?? [];

        if ($request->has('google_analytics_id')) {
            $data['google_analytics_id'] = $request->input('google_analytics_id', '');
        }

        if ($request->has('allow_indexing')) {
            $data['allow_indexing'] = $request->boolean('allow_indexing');
        }

        $site->update(['data' => $data]);

        return redirect()->back();
    }

    public function components(UpdateDashboardComponentsRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        $data = $site->data ?? [];

        // Merge component visibility flags
        if ($request->has('components')) {
            $existingComponents = $data['components'] ?? [];
            $incomingComponents = $request->input('components', []);

            foreach ($incomingComponents as $key => $value) {
                $existingComponents[$key] = [
                    'enabled' => filter_var($value['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ];
            }

            $data['components'] = $existingComponents;
        }

        // Merge content overrides
        $existingOverrides = $data['overrides'] ?? [];
        if ($request->has('overrides')) {
            $existingOverrides['description'] = $request->input('overrides.description', $existingOverrides['description'] ?? '');
        }

        // Merge social links
        if ($request->has('socials')) {
            $data['socials'] = array_merge($data['socials'] ?? [], array_filter($request->input('socials', []), fn($v) => $v !== null));;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $placesId = $site->places_id;
            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $path = $file->storeAs(
                "images/{$placesId}",
                "logo.{$extension}",
                'public'
            );
            $existingOverrides['logo_path'] = Storage::disk('public')->url($path);
        }

        $data['overrides'] = $existingOverrides;

        $site->update(['data' => $data]);

        return redirect()->back()->with('success', 'Components updated successfully.');
    }

}
