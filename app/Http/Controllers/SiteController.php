<?php

namespace App\Http\Controllers;

use App\Mail\ContactFormMail;
use App\Models\ContactSubmission;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request, string $domain): Response|RedirectResponse
    {
        $site = $this->findSiteForDomain($domain);

        if(!$site) {
            return redirect()->route('home');
        }

        // Private sites are only accessible to the logged-in owner.
        if ($site->is_private) {
            $authUser = auth()->user();
            if (!$authUser || $authUser->id !== $site->user_id) {
                $loginUrl     = rtrim(config('app.url'), '/') . '/login';
                $businessName = $site->business_name;
                return Inertia::render('site/Private', [
                    'businessName' => $businessName,
                    'loginUrl'     => $loginUrl,
                ]);
            }
        }

        $isPremium = $site->user?->subscribed('default') ?? false;
        $siteUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        $canonicalUrl = $this->canonicalUrlForSite($site, $siteUrl);

        $authUser = auth()->user();
        $isOwner  = $authUser && $authUser->id === $site->user_id;

        $dashboardUrl = rtrim(config('app.url'), '/') . '/dashboard';

        $siteData = $this->buildSiteData($site);

        return Inertia::render('site/Index', [
            'data'            => $siteData,
            'isPremium'       => $isPremium,
            'metaTitle'       => $site->meta_title,
            'metaDescription' => $site->meta_description,
            'siteUrl'         => $siteUrl,
            'canonicalUrl'    => $canonicalUrl,
            'sitemapUrl'      => $siteUrl . '/sitemap.xml',
            'isOwner'         => $isOwner,
            'dashboardUrl'    => $dashboardUrl,
        ]);
    }

    public function sitemap(Request $request, string $domain)
    {
        $site = $this->findSiteForDomain($domain);

        abort_if(!$site, 404);
        abort_if($site->is_private, 404);

        $siteUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        $loc = htmlspecialchars($siteUrl, ENT_XML1);
        $lastmod = htmlspecialchars($site->updated_at?->toAtomString() ?? now()->toAtomString(), ENT_XML1);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
    </url>
</urlset>
XML;

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function contact(string $domain, Request $request): JsonResponse
    {
        if (filled($request->input('website'))) {
            return response()->json(['success' => true]);
        }

        $site = $this->findSiteForDomain($domain);

        if (!$site) {
            return response()->json(['error' => 'Site not found.'], 404);
        }

        // Contact form is a premium feature
        if (!($site->user?->subscribed('default') ?? false)) {
            return response()->json(['error' => 'Contact form is not available on this site.'], 403);
        }

        $contactEmail = $site->contact_email;

        if (!$contactEmail) {
            return response()->json(['error' => 'This site does not have a contact email configured.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'preferred_contact_time' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $businessName = $site->business_name ?? $domain;
        $preferredContactTime = trim((string) $request->input('preferred_contact_time', ''));

        ContactSubmission::create([
            'site_id' => $site->id,
            'email'   => $request->input('email'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'preferred_contact_time' => $preferredContactTime ?: null,
        ]);

        // Notify the site owner at their account email address (where they log in),
        // falling back to the site's public contact email if the owner is missing.
        $notifyEmail = $site->user?->email ?? $contactEmail;

        Mail::to($notifyEmail)->send(new ContactFormMail(
            senderEmail: $request->input('email'),
            mailSubject: $request->input('subject'),
            messageBody: $request->input('message'),
            businessName: $businessName,
            preferredContactTime: $preferredContactTime ?: null,
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Build a flat data array from a Site's individual columns.
     */
    private function buildSiteData(Site $site): array
    {
        return [
            'business_name'      => $site->business_name,
            'business_type'      => $site->business_type,
            'description'        => $site->description,
            'logo_path'          => $site->logo_path,
            'formatted_address'  => $site->formatted_address,
            'city'               => $site->city,
            'region'             => $site->region,
            'phone'              => $site->phone,
            'whatsapp_number'    => $site->whatsapp_number,
            'website_url'        => $site->website_url,
            'contact_email'      => $site->contact_email,
            'socials'            => $site->socials ?? [],
            'opening_hours'      => $site->opening_hours ?? [],
            'quick_links'        => $site->quick_links ?? [],
            'services'           => $site->services ?? [],
            'images'             => $site->images ?? [],
            'rating'             => $site->rating,
            'review_count'       => $site->review_count,
            'reviews'            => $site->reviews ?? [],
            'google_places_id'   => ($site->places_id && !str_starts_with($site->places_id ?? '', 'manual-')) ? $site->places_id : null,
            'components'         => $site->components ?? $this->defaultComponents(),
            'services_heading'   => $site->services_heading ?? 'Our Services',
            'services_cta_label' => $site->services_cta_label ?? '',
            'services_cta_link'  => $site->services_cta_link ?? '',
            'settings'           => $site->settings ?? [],
        ];
    }

    private function defaultComponents(): array
    {
        return [
            'header'        => ['enabled' => true],
            'description'   => ['enabled' => true],
            'gallery'       => ['enabled' => true],
            'quick_actions' => ['enabled' => true],
            'reviews'       => ['enabled' => true],
            'contact'       => ['enabled' => true],
            'contact_form'  => ['enabled' => true],
            'services'      => ['enabled' => true],
        ];
    }

    private function findSiteForDomain(string $domain): ?Site
    {
        $domain = strtolower($domain);
        $bare   = preg_replace('/^www\./i', '', $domain);

        // If we were handed a full subdomain host (e.g. "acme.321sites.com")
        // rather than just the slug, peel off the app domain to get the slug.
        // This keeps subdomains resolving even if the Route::domain group didn't
        // match and the request fell through to the catch-all host handler.
        $appDomain = config('app.domain') ?: parse_url(config('app.url'), PHP_URL_HOST);
        $slug = ($appDomain && str_ends_with($domain, '.' . strtolower($appDomain)))
            ? substr($domain, 0, -strlen('.' . $appDomain))
            : null;

        return ($slug ? Site::where('subdomain', $slug)->latest()->first() : null)
            ?? Site::where('subdomain', $domain)->latest()->first()
            ?? Site::where('custom_domain', $domain)->where('domain_verified', true)->latest()->first()
            ?? Site::where('custom_domain', $bare)->where('domain_verified', true)->latest()->first();
    }

    private function canonicalUrlForSite(Site $site, string $fallbackSiteUrl): string
    {
        if ($site->domain_verified && filled($site->custom_domain)) {
            return 'https://' . $site->custom_domain;
        }

        return $fallbackSiteUrl;
    }
}
