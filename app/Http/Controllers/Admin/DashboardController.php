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

        // Non-premium users cannot set a custom domain
        if (!auth()->user()->subscribed('default') && ($payload['domain_type'] ?? '') === 'custom') {
            $payload['domain_type'] = 'subdomain';
        }

        // If the custom domain has changed, reset verification status.
        if (($payload['domain_type'] ?? '') === 'custom'
            && ($payload['custom_domain'] ?? '') !== $site->custom_domain) {
            $payload['domain_verified'] = false;
        }

        // Coerce is_private to boolean
        if (array_key_exists('is_private', $payload)) {
            $payload['is_private'] = filter_var($payload['is_private'], FILTER_VALIDATE_BOOLEAN);
        }

        $site->update($payload);

        // Handle favicon (stored in site.settings — separate from the direct columns above)
        if ($request->filled('favicon_type')) {
            $site        = $site->fresh();
            $settings    = $site->settings ?? [];
            $faviconType = $request->input('favicon_type');
            $placesId    = $site->places_id ?? 'default';
            $dir         = storage_path("app/public/images/{$placesId}");
            @mkdir($dir, 0755, true);

            if ($faviconType === 'clear') {
                unset($settings['favicon_path']);

            } elseif ($faviconType === 'upload' && $request->hasFile('favicon')) {
                $file = $request->file('favicon');
                $ext  = $file->getClientOriginalExtension() ?: 'png';
                $path = $file->storeAs("images/{$placesId}", "favicon.{$ext}", 'public');
                $settings['favicon_path'] = Storage::disk('public')->url($path);

            } elseif ($faviconType === 'initials') {
                $dataUrl = $request->input('favicon_data', '');
                $prefix  = 'data:image/png;base64,';
                if (str_starts_with($dataUrl, $prefix)) {
                    $imgBytes = base64_decode(substr($dataUrl, strlen($prefix)));
                    if ($imgBytes !== false) {
                        $outPath = $dir . '/favicon.png';
                        file_put_contents($outPath, $imgBytes);
                        $settings['favicon_path'] = Storage::disk('public')->url("images/{$placesId}/favicon.png");
                    }
                }

            } elseif ($faviconType === 'logo') {
                $faviconData = $request->input('favicon_data', '');
                if ($faviconData) {
                    $settings['favicon_path'] = $faviconData;
                } else {
                    $logoUrl = $site->logo_path;
                    if ($logoUrl) {
                        $settings['favicon_path'] = $logoUrl;
                    }
                }
            }

            $site->update(['settings' => $settings]);
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

        $aRecords = dns_get_record($domain, DNS_A);
        $apexOk   = collect($aRecords)->contains(fn($r) => ($r['ip'] ?? '') === $serverIp);

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
        $site     = Site::where('user_id', auth()->id())->latest()->first();
        $validated = $request->validated();
        $settings  = $site->settings ?? [];

        // Google Analytics is a premium feature
        if (array_key_exists('google_analytics_id', $validated) && auth()->user()->subscribed('default')) {
            $settings['google_analytics_id'] = $validated['google_analytics_id'] ?? '';
        }

        if (array_key_exists('allow_indexing', $validated)) {
            $settings['allow_indexing'] = $request->boolean('allow_indexing');
        }

        $site->update([
            'meta_title'       => $validated['meta_title'],
            'meta_description' => $validated['meta_description'],
            'settings'         => $settings,
        ]);

        return redirect()->back();
    }

    public function refresh(): RedirectResponse
    {
        // RefreshSiteFromGoogle is deprecated — return early without doing anything.
        return redirect()->back()->with('success', 'Your site info is up to date.');
    }

    public function components(UpdateDashboardComponentsRequest $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        $isPremium = auth()->user()->subscribed('default');

        // Merge component visibility flags
        if ($request->has('components')) {
            $existingComponents = $site->components ?? [];
            $incomingComponents = $request->input('components', []);

            foreach ($incomingComponents as $key => $value) {
                // contact_form is a premium-only component
                if ($key === 'contact_form' && !$isPremium) {
                    $existingComponents[$key] = ['enabled' => false];
                    continue;
                }

                $existingComponents[$key] = [
                    'enabled' => filter_var($value['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ];
            }

            $site->components = $existingComponents;
        }

        // Description override — write directly to description column
        if ($request->has('overrides')) {
            $overrideDesc = $request->input('overrides.description', '');
            if ($overrideDesc !== '') {
                $site->description = $overrideDesc;
            }

            // Contact email
            if ($request->has('overrides.contact_email')) {
                $email = trim($request->input('overrides.contact_email', ''));
                $site->contact_email = $email ?: null;
            }

            // Hidden reviews — stored in settings
            if ($request->has('overrides.hidden_reviews')) {
                $settings = $site->settings ?? [];
                $settings['hidden_reviews'] = array_values(
                    array_map('intval', $request->input('overrides.hidden_reviews', []))
                );
                $site->settings = $settings;
            }
        }

        // Merge social links
        if ($request->has('socials')) {
            $site->socials = array_merge(
                $site->socials ?? [],
                array_filter($request->input('socials', []), fn($v) => $v !== null)
            );
        }

        // Save WhatsApp number
        if ($request->has('whatsapp_number')) {
            $site->whatsapp_number = preg_replace('/\D/', '', $request->input('whatsapp_number', '')) ?: null;
        }

        // Save custom quick links
        if ($request->has('quickLinks')) {
            $site->quick_links = collect($request->input('quickLinks', []))
                ->filter(fn($l) => !empty($l['label']) && !empty($l['link']))
                ->values()
                ->map(fn($l) => ['label' => $l['label'], 'link' => $l['link']])
                ->all();
        }

        // Save custom colour palette (in settings)
        if ($request->has('palette_primary')) {
            $primary  = $request->input('palette_primary', '');
            $settings = $site->settings ?? [];
            if ($primary) {
                $settings['palette'] = [
                    'primary'   => $primary,
                    'secondary' => $request->input('palette_secondary', '') ?: null,
                ];
            } else {
                unset($settings['palette']);
            }
            $site->settings = $settings;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $placesId = $site->places_id ?? 'default';
            $file     = $request->file('logo');
            $extension = $file->getClientOriginalExtension();
            $path = $file->storeAs(
                "images/{$placesId}",
                "logo.{$extension}",
                'public'
            );
            $site->logo_path = Storage::disk('public')->url($path);
        }

        // Handle header background (stored in settings)
        if ($request->has('header_bg_type')) {
            $bgType   = $request->input('header_bg_type', 'auto');
            $settings = $site->settings ?? [];

            if ($bgType === 'auto') {
                unset($settings['header_bg']);
            } elseif ($bgType === 'custom_image' && $request->hasFile('header_bg_image')) {
                $placesId = $site->places_id ?? 'default';
                $file     = $request->file('header_bg_image');
                $ext      = $file->getClientOriginalExtension();
                $path     = $file->storeAs("images/{$placesId}", "header_bg.{$ext}", 'public');
                $settings['header_bg'] = [
                    'type'  => 'custom_image',
                    'value' => Storage::disk('public')->url($path),
                ];
            } elseif ($bgType === 'google_image') {
                $settings['header_bg'] = [
                    'type'  => 'google_image',
                    'value' => $request->input('header_bg_value', ''),
                ];
            } elseif ($bgType === 'color') {
                $settings['header_bg'] = [
                    'type'  => 'color',
                    'value' => $request->input('header_bg_value', ''),
                ];
            } elseif ($bgType === 'stock') {
                $settings['header_bg'] = [
                    'type'        => 'stock',
                    'value'       => $request->input('header_bg_value', ''),
                    'thumb'       => $request->input('header_bg_thumb', ''),
                    'credit'      => $request->input('header_bg_credit', ''),
                    'credit_url'  => $request->input('header_bg_credit_url', ''),
                ];
            }

            $site->settings = $settings;
        }

        // Handle favicon (stored in settings)
        if ($request->has('favicon_type')) {
            $faviconType = $request->input('favicon_type');
            $placesId    = $site->places_id ?? 'default';
            $settings    = $site->settings ?? [];

            if ($faviconType === 'clear') {
                unset($settings['favicon_path']);
            } elseif ($faviconType === 'upload' && $request->hasFile('favicon')) {
                $file = $request->file('favicon');
                $ext  = $file->getClientOriginalExtension() ?: 'png';
                $path = $file->storeAs("images/{$placesId}", "favicon.{$ext}", 'public');
                $settings['favicon_path'] = Storage::disk('public')->url($path);
            } elseif ($faviconType === 'logo') {
                $logoUrl = $site->logo_path;
                if ($logoUrl) {
                    $generated = $this->generateFaviconFromImage($logoUrl, $placesId);
                    if ($generated) {
                        $settings['favicon_path'] = Storage::disk('public')->url($generated);
                    }
                }
            } elseif ($faviconType === 'initials') {
                $name    = $site->business_name ?? 'Business';
                $primary = ($site->settings ?? [])['palette']['primary'] ?? '#1e293b';
                $generated = $this->generateFaviconFromInitials($name, $primary, $placesId);
                if ($generated) {
                    $settings['favicon_path'] = Storage::disk('public')->url($generated);
                }
            }

            $site->settings = $settings;
        }

        // Save services list
        if ($request->has('services')) {
            $site->services = collect($request->input('services', []))
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
            $site->services_heading = $request->input('services_heading', '');
        }

        if ($request->has('services_cta_label')) {
            $site->services_cta_label = $request->input('services_cta_label', '');
        }

        if ($request->has('services_cta_link')) {
            $site->services_cta_link = $request->input('services_cta_link', '');
        }

        // Photo reorder
        if ($request->has('images_order')) {
            $existing = collect($site->images ?? []);
            $reordered = collect($request->input('images_order', []))
                ->filter(fn ($path) => $existing->contains($path))
                ->values()
                ->all();
            $missing = $existing->filter(fn ($p) => !in_array($p, $reordered))->values()->all();
            $site->images = array_merge($reordered, $missing);
        }

        $site->save();

        return redirect()->back()->with('success', 'Components updated successfully.');
    }

    /**
     * Resize an existing image to a 64×64 PNG favicon.
     */
    private function generateFaviconFromImage(string $logoUrl, string $placesId): ?string
    {
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
     */
    private function generateFaviconFromInitials(string $name, string $primaryColor, string $placesId): ?string
    {
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
