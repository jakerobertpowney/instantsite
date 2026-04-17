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

            // Contact email override — empty string means "use Google email"
            if ($request->has('overrides.contact_email')) {
                $email = trim($request->input('overrides.contact_email', ''));
                if ($email) {
                    $existingOverrides['contact_email'] = $email;
                } else {
                    unset($existingOverrides['contact_email']);
                }
            }

            // Save hidden review indices (nullable — absence means no reviews are hidden)
            if ($request->has('overrides.hidden_reviews')) {
                $existingOverrides['hidden_reviews'] = array_values(
                    array_map('intval', $request->input('overrides.hidden_reviews', []))
                );
            }
        }

        // Merge social links
        if ($request->has('socials')) {
            $data['socials'] = array_merge($data['socials'] ?? [], array_filter($request->input('socials', []), fn($v) => $v !== null));;
        }

        // Save WhatsApp number
        if ($request->has('whatsapp_number')) {
            $data['whatsapp_number'] = preg_replace('/\D/', '', $request->input('whatsapp_number', ''));
        }

        // Save custom quick links (full replace — order matters)
        if ($request->has('quickLinks')) {
            $data['quickLinks'] = collect($request->input('quickLinks', []))
                ->filter(fn($l) => !empty($l['label']) && !empty($l['link']))
                ->values()
                ->map(fn($l) => ['label' => $l['label'], 'link' => $l['link']])
                ->all();
        }

        // Save custom colour palette
        if ($request->has('palette_primary')) {
            $primary = $request->input('palette_primary', '');
            if ($primary) {
                $existingOverrides['palette'] = [
                    'primary'   => $primary,
                    'secondary' => $request->input('palette_secondary', '') ?: null,
                ];
            } else {
                // Empty primary = revert to auto palette
                unset($existingOverrides['palette']);
            }
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

        // Handle header background
        if ($request->has('header_bg_type')) {
            $bgType = $request->input('header_bg_type', 'auto');

            if ($bgType === 'auto') {
                unset($existingOverrides['header_bg']);
            } elseif ($bgType === 'custom_image' && $request->hasFile('header_bg_image')) {
                $placesId = $site->places_id;
                $file     = $request->file('header_bg_image');
                $ext      = $file->getClientOriginalExtension();
                $path     = $file->storeAs("images/{$placesId}", "header_bg.{$ext}", 'public');
                $existingOverrides['header_bg'] = [
                    'type'  => 'custom_image',
                    'value' => Storage::disk('public')->url($path),
                ];
            } elseif ($bgType === 'google_image') {
                $existingOverrides['header_bg'] = [
                    'type'  => 'google_image',
                    'value' => $request->input('header_bg_value', ''),
                ];
            } elseif ($bgType === 'color') {
                $existingOverrides['header_bg'] = [
                    'type'  => 'color',
                    'value' => $request->input('header_bg_value', ''),
                ];
            } elseif ($bgType === 'stock') {
                $existingOverrides['header_bg'] = [
                    'type'        => 'stock',
                    'value'       => $request->input('header_bg_value', ''),
                    'thumb'       => $request->input('header_bg_thumb', ''),
                    'credit'      => $request->input('header_bg_credit', ''),
                    'credit_url'  => $request->input('header_bg_credit_url', ''),
                ];
            }
        }

        $data['overrides'] = $existingOverrides;

        $site->update(['data' => $data]);

        return redirect()->back()->with('success', 'Components updated successfully.');
    }

}
