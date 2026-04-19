<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Resolve the server's public IP for the DNS records instructions.
        $appHost  = parse_url(config('app.url'), PHP_URL_HOST);
        $serverIp = gethostbyname($appHost);

        // Load contact form submissions for the Messages panel
        $submissions = $site->submissions()
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id'                     => $s->id,
                'email'                  => $s->email,
                'subject'                => $s->subject,
                'message'                => $s->message,
                'preferred_contact_time' => $s->preferred_contact_time,
                'read_at'                => $s->read_at?->toISOString(),
                'created_at'             => $s->created_at->toISOString(),
            ]);

        $unreadCount = $site->submissions()->whereNull('read_at')->count();

        return Inertia::render('Dashboard', [
            'site'        => $site,
            'appDomain'   => env('APP_DOMAIN', '321sites.test'),
            'isPremium'   => auth()->user()->subscribed('default'),
            'serverIp'    => $serverIp,
            'submissions' => $submissions,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function site(UpdateDashboardSiteRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        $payload = $request->validated();

        // Non-premium users cannot set a custom domain — silently downgrade to subdomain.
        if (!auth()->user()->subscribed('default') && ($payload['domain_type'] ?? '') === 'custom') {
            $payload['domain_type'] = 'subdomain';
        }

        // If the custom domain has changed, reset verification status.
        if (($payload['domain_type'] ?? '') === 'custom'
            && ($payload['custom_domain'] ?? '') !== $site->custom_domain) {
            $payload['domain_verified'] = false;
        }

        // Coerce is_private to boolean (it arrives as a string from the form).
        if (array_key_exists('is_private', $payload)) {
            $payload['is_private'] = filter_var($payload['is_private'], FILTER_VALIDATE_BOOLEAN);
        }

        $site->update($payload);

        // Handle favicon (stored in site.data.overrides — separate from the direct columns above)
        if ($request->filled('favicon_type')) {
            $site        = $site->fresh();
            $data              = $site->data ?? [];
            $existingOverrides = $data['overrides'] ?? [];
            $faviconType       = $request->input('favicon_type');
            $placesId          = $site->places_id ?? 'default';
            $dir               = storage_path("app/public/images/{$placesId}");
            @mkdir($dir, 0755, true);

            if ($faviconType === 'clear') {
                unset($existingOverrides['favicon_path']);

            } elseif ($faviconType === 'upload' && $request->hasFile('favicon')) {
                $file = $request->file('favicon');
                $ext  = $file->getClientOriginalExtension() ?: 'png';
                $path = $file->storeAs("images/{$placesId}", "favicon.{$ext}", 'public');
                $existingOverrides['favicon_path'] = Storage::disk('public')->url($path);

            } elseif ($faviconType === 'initials') {
                // The frontend already rendered the canvas preview as a PNG data URL —
                // decode it and write to disk directly; no server-side GD required.
                $dataUrl = $request->input('favicon_data', '');
                $prefix  = 'data:image/png;base64,';
                if (str_starts_with($dataUrl, $prefix)) {
                    $imgBytes = base64_decode(substr($dataUrl, strlen($prefix)));
                    if ($imgBytes !== false) {
                        $outPath = $dir . '/favicon.png';
                        file_put_contents($outPath, $imgBytes);
                        $existingOverrides['favicon_path'] = Storage::disk('public')->url("images/{$placesId}/favicon.png");
                    }
                }

            } elseif ($faviconType === 'logo') {
                // Use the logo URL/path directly as the favicon — browsers will scale it.
                $faviconData = $request->input('favicon_data', '');
                if ($faviconData) {
                    $existingOverrides['favicon_path'] = $faviconData;
                } else {
                    // Fallback: read from stored overrides
                    $logoUrl = $existingOverrides['logo_path'] ?? ($data['logo'] ?? null);
                    if ($logoUrl) {
                        $existingOverrides['favicon_path'] = $logoUrl;
                    }
                }
            }

            $data['overrides'] = $existingOverrides;
            $site->update(['data' => $data]);
        }

        return redirect()->back();
    }

    /**
     * Check DNS records for the site's custom domain and mark it verified if correct.
     */
    public function verifyDomain(Request $request): JsonResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site || $site->domain_type !== 'custom' || !$site->custom_domain) {
            return response()->json(['verified' => false, 'error' => 'No custom domain configured.'], 422);
        }

        $domain   = $site->custom_domain;
        $appHost  = parse_url(config('app.url'), PHP_URL_HOST);
        $serverIp = gethostbyname($appHost);

        // Check the apex domain A record
        $aRecords = dns_get_record($domain, DNS_A);
        $apexOk   = collect($aRecords)->contains(fn($r) => ($r['ip'] ?? '') === $serverIp);

        // Check www — accept either A record or CNAME pointing back to the apex
        $wwwARecords    = dns_get_record('www.' . $domain, DNS_A);
        $wwwCnameRecords = dns_get_record('www.' . $domain, DNS_CNAME);
        $wwwOk = collect($wwwARecords)->contains(fn($r) => ($r['ip'] ?? '') === $serverIp)
            || collect($wwwCnameRecords)->contains(fn($r) => rtrim($r['target'] ?? '', '.') === $domain);

        $verified = $apexOk && $wwwOk;

        if ($verified) {
            $site->update(['domain_verified' => true]);
        }

        return response()->json([
            'verified' => $verified,
            'checks'   => [
                'apex' => ['ok' => $apexOk, 'record' => $domain,        'expected' => $serverIp],
                'www'  => ['ok' => $wwwOk,  'record' => 'www.' . $domain, 'expected' => $serverIp],
            ],
        ]);
    }

    public function settings(UpdateDashboardSettingsRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();
        $validated = $request->validated();

        $data = $site->data ?? [];

        // Google Analytics is a premium feature — ignore the field for free users.
        if (array_key_exists('google_analytics_id', $validated) && auth()->user()->subscribed('default')) {
            $data['google_analytics_id'] = $validated['google_analytics_id'] ?? '';
        }

        if (array_key_exists('allow_indexing', $validated)) {
            $data['allow_indexing'] = $request->boolean('allow_indexing');
        }

        $site->update([
            'meta_title' => $validated['meta_title'],
            'meta_description' => $validated['meta_description'],
            'data' => $data,
        ]);

        return redirect()->back();
    }

    public function refresh(): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (! $site || ! $site->places_id) {
            return redirect()->back()->with('error', 'No site found to refresh.');
        }

        \App\Jobs\RefreshSiteFromGoogle::dispatch($site->id);

        return redirect()->back()->with('success', 'Refreshing your Google info — it\'ll be updated in a moment.');
    }

    public function components(UpdateDashboardComponentsRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        $data = $site->data ?? [];

        $isPremium = auth()->user()->subscribed('default');

        // Merge component visibility flags
        if ($request->has('components')) {
            $existingComponents = $data['components'] ?? [];
            $incomingComponents = $request->input('components', []);

            foreach ($incomingComponents as $key => $value) {
                // contact_form is a premium-only component — free users cannot enable it.
                if ($key === 'contact_form' && !$isPremium) {
                    $existingComponents[$key] = ['enabled' => false];
                    continue;
                }

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

            // Contact email is available to all users (free + premium).
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

        // Handle favicon
        if ($request->has('favicon_type')) {
            $faviconType = $request->input('favicon_type');
            $placesId = $site->places_id ?? 'default';

            if ($faviconType === 'clear') {
                unset($existingOverrides['favicon_path']);
            } elseif ($faviconType === 'upload' && $request->hasFile('favicon')) {
                $file = $request->file('favicon');
                $ext  = $file->getClientOriginalExtension() ?: 'png';
                $path = $file->storeAs("images/{$placesId}", "favicon.{$ext}", 'public');
                $existingOverrides['favicon_path'] = Storage::disk('public')->url($path);
            } elseif ($faviconType === 'logo') {
                $logoUrl = $existingOverrides['logo_path'] ?? ($site->data['logo'] ?? null);
                if ($logoUrl) {
                    $generated = $this->generateFaviconFromImage($logoUrl, $placesId);
                    if ($generated) {
                        $existingOverrides['favicon_path'] = Storage::disk('public')->url($generated);
                    }
                }
            } elseif ($faviconType === 'initials') {
                $name    = $site->data['displayName']['text'] ?? 'Business';
                $primary = $existingOverrides['palette']['primary'] ?? ($site->data['overrides']['palette']['primary'] ?? '#1e293b');
                $generated = $this->generateFaviconFromInitials($name, $primary, $placesId);
                if ($generated) {
                    $existingOverrides['favicon_path'] = Storage::disk('public')->url($generated);
                }
            }
        }

        $data['overrides'] = $existingOverrides;

        // Save services list (full replace — order matters)
        if ($request->has('services')) {
            $data['services'] = collect($request->input('services', []))
                ->filter(fn($s) => !empty($s['name']))
                ->values()
                ->map(fn($s) => [
                    'id'          => $s['id'] ?? (string) \Illuminate\Support\Str::uuid(),
                    'name'        => $s['name'],
                    'description' => $s['description'] ?? null,
                    'price'       => $s['price'] ?? null,
                    'currency'    => strtoupper($s['currency'] ?? 'GBP'),
                    'show_price'  => filter_var($s['show_price'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'featured'    => filter_var($s['featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ])
                ->all();
        }

        if ($request->has('services_heading')) {
            $data['services_heading'] = $request->input('services_heading', '');
        }

        if ($request->has('services_cta_label')) {
            $data['services_cta_label'] = $request->input('services_cta_label', '');
        }

        if ($request->has('services_cta_link')) {
            $data['services_cta_link'] = $request->input('services_cta_link', '');
        }

        // Photo reorder — only accept paths that already exist in the current images array
        if ($request->has('images_order')) {
            $existing = collect($data['images'] ?? []);
            $reordered = collect($request->input('images_order', []))
                ->filter(fn ($path) => $existing->contains($path))
                ->values()
                ->all();
            // Append any paths not included in the submitted order (safety net)
            $missing = $existing->filter(fn ($p) => ! in_array($p, $reordered))->values()->all();
            $data['images'] = array_merge($reordered, $missing);
        }

        $site->update(['data' => $data]);

        return redirect()->back()->with('success', 'Components updated successfully.');
    }

    /**
     * Resize an existing image to a 64×64 PNG favicon.
     * Returns the storage-relative path on success, null on failure.
     */
    private function generateFaviconFromImage(string $logoUrl, string $placesId): ?string
    {
        // Resolve URL → absolute filesystem path under storage/app/public
        $storageDisk = storage_path('app/public');

        if (preg_match('#^https?://#i', $logoUrl)) {
            $parsed   = parse_url($logoUrl, PHP_URL_PATH) ?? '';
            $relative = ltrim(preg_replace('#^/storage/#', '', $parsed), '/');
            $filePath = $storageDisk . '/' . $relative;
        } else {
            $filePath = $storageDisk . '/' . ltrim($logoUrl, '/');
        }

        if (! file_exists($filePath)) {
            return null;
        }

        $mime = mime_content_type($filePath);
        $src  = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png'  => @imagecreatefrompng($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            'image/gif'  => @imagecreatefromgif($filePath),
            default      => false,
        };

        if (! $src) {
            return null;
        }

        $size = 64;
        $dest = imagecreatetruecolor($size, $size);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
        imagecopyresampled($dest, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));

        $outRelative = "images/{$placesId}/favicon.png";
        $outPath     = $storageDisk . '/' . $outRelative;
        @mkdir(dirname($outPath), 0755, true);
        imagepng($dest, $outPath);
        imagedestroy($src);
        imagedestroy($dest);

        return $outRelative;
    }

    /**
     * Generate a 64×64 PNG favicon with a coloured background and 1–2 letter initials.
     * Returns the storage-relative path on success, null on failure.
     */
    private function generateFaviconFromInitials(string $name, string $primaryColor, string $placesId): ?string
    {
        // Extract up to 2 initials from the business name
        $words    = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $letter = mb_strtoupper(mb_substr((string) $word, 0, 1));
            if ($letter !== '' && ctype_alpha($letter)) {
                $initials .= $letter;
            }
        }
        if ($initials === '') {
            $initials = 'B';
        }

        // Safely parse the hex colour (default to dark slate if malformed)
        $hex = ltrim($primaryColor, '#');
        if (strlen($hex) !== 6) {
            $hex = '1e293b';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $size = 64;
        $img  = imagecreatetruecolor($size, $size);

        $bgColor   = imagecolorallocate($img, (int) $r, (int) $g, (int) $b);
        $textColor = imagecolorallocate($img, 255, 255, 255);

        imagefill($img, 0, 0, $bgColor);

        // Built-in GD font 5 — largest available without a TTF file
        $fontNum    = 5;
        $charWidth  = imagefontwidth($fontNum);
        $charHeight = imagefontheight($fontNum);
        $textWidth  = $charWidth * strlen($initials);
        $textX      = (int) (($size - $textWidth) / 2);
        $textY      = (int) (($size - $charHeight) / 2);

        imagestring($img, $fontNum, $textX, $textY, $initials, $textColor);

        $outRelative = "images/{$placesId}/favicon.png";
        $storageDisk = storage_path('app/public');
        $outPath     = $storageDisk . '/' . $outRelative;
        @mkdir(dirname($outPath), 0755, true);
        imagepng($img, $outPath);
        imagedestroy($img);

        return $outRelative;
    }

}
