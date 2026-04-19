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
        // Non-owners (including guests) see a polite "private site" page instead of a redirect,
        // with a login button pointing at the root app's login page.
        if ($site->is_private) {
            $authUser = auth()->user();
            if (!$authUser || $authUser->id !== $site->user_id) {
                $loginUrl     = rtrim(config('app.url'), '/') . '/login';
                $businessName = $site->data['displayName']['text'] ?? null;
                return Inertia::render('site/Private', [
                    'businessName' => $businessName,
                    'loginUrl'     => $loginUrl,
                ]);
            }
        }

        $isPremium = $site->user?->subscribed('default') ?? false;
        $siteUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        $canonicalUrl = $this->canonicalUrlForSite($site, $siteUrl);

        // Determine whether the currently authenticated user owns this site.
        $authUser = auth()->user();
        $isOwner  = $authUser && $authUser->id === $site->user_id;

        // Build the dashboard URL against the root app domain, not the current subdomain,
        // because the dashboard route only exists on the main domain.
        $dashboardUrl = rtrim(config('app.url'), '/') . '/dashboard';

        return Inertia::render('site/Index', [
            'data' => $site->data,
            'isPremium' => $isPremium,
            'metaTitle' => $site->meta_title,
            'metaDescription' => $site->meta_description,
            'siteUrl' => $siteUrl,
            'canonicalUrl' => $canonicalUrl,
            'sitemapUrl' => $siteUrl . '/sitemap.xml',
            'isOwner' => $isOwner,
            'dashboardUrl' => $dashboardUrl,
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

        // Contact form is a premium feature — reject submissions for non-premium sites.
        if (!($site->user?->subscribed('default') ?? false)) {
            return response()->json(['error' => 'Contact form is not available on this site.'], 403);
        }

        // Prefer the owner-supplied override email, fall back to the Google Places email.
        $contactEmail = $site->data['overrides']['contact_email'] ?? $site->data['contact'] ?? null;

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

        $businessName = $site->data['displayName']['text'] ?? $domain;
        $preferredContactTime = trim((string) $request->input('preferred_contact_time', ''));

        // Persist the submission so the owner can review it in the dashboard
        ContactSubmission::create([
            'site_id' => $site->id,
            'email'   => $request->input('email'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'preferred_contact_time' => $preferredContactTime ?: null,
        ]);

        Mail::to($contactEmail)->send(new ContactFormMail(
            senderEmail: $request->input('email'),
            mailSubject: $request->input('subject'),
            messageBody: $request->input('message'),
            businessName: $businessName,
            preferredContactTime: $preferredContactTime ?: null,
        ));

        return response()->json(['success' => true]);
    }

    private function findSiteForDomain(string $domain): ?Site
    {
        return Site::where('subdomain', $domain)->latest()->first()
            ?? Site::where('custom_domain', $domain)->where('domain_verified', true)->latest()->first();
    }

    private function canonicalUrlForSite(Site $site, string $fallbackSiteUrl): string
    {
        if ($site->domain_verified && filled($site->custom_domain)) {
            return 'https://' . $site->custom_domain;
        }

        return $fallbackSiteUrl;
    }
}
