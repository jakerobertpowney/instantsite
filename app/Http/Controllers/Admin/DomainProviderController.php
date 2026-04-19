<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\CloudflareService;
use App\Services\DomainConnectService;
use App\Services\GodaddyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainProviderController extends Controller
{
    // ── Cloudflare OAuth ─────────────────────────────────────────────────────────

    /**
     * Start the Cloudflare OAuth flow.
     * Generates a PKCE code verifier + challenge and redirects to Cloudflare.
     */
    public function redirectToCloudflare(Request $request): RedirectResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site || $site->domain_type !== 'custom' || !$site->custom_domain) {
            return redirect()->route('dashboard')->with('error', 'Please save your custom domain first.');
        }

        // PKCE
        $codeVerifier  = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // CSRF state
        $state = Str::random(40);

        $request->session()->put([
            'cf_oauth_state'    => $state,
            'cf_code_verifier'  => $codeVerifier,
        ]);

        return redirect(CloudflareService::authorizationUrl($state, $codeChallenge));
    }

    /**
     * Handle the Cloudflare OAuth callback.
     * Exchanges the code for a token, finds the matching zone, pushes A records.
     */
    public function handleCloudflareCallback(Request $request): RedirectResponse
    {
        // Verify state (CSRF)
        if ($request->input('state') !== $request->session()->pull('cf_oauth_state')) {
            return redirect()->route('dashboard')->with('error', 'Security check failed. Please try connecting again.');
        }

        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', 'Cloudflare connection was cancelled.');
        }

        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site || !$site->custom_domain) {
            return redirect()->route('dashboard')->with('error', 'No custom domain found. Please save your domain first.');
        }

        try {
            // Exchange code → token
            $tokens = CloudflareService::exchangeCode(
                $request->input('code'),
                $request->session()->pull('cf_code_verifier'),
            );

            $accessToken = $tokens['access_token'] ?? null;

            if (!$accessToken) {
                return redirect()->route('dashboard')->with('error', 'Failed to connect to Cloudflare. Please try again.');
            }

            // Find the Cloudflare zone matching this domain
            $cf   = new CloudflareService($accessToken);
            $zone = $cf->findZoneForDomain($site->custom_domain);

            if (!$zone) {
                return redirect()->route('dashboard')->with('error',
                    "We couldn't find {$site->custom_domain} in your Cloudflare account. Make sure your domain is added to Cloudflare first."
                );
            }

            // Push A records
            $serverIp = gethostbyname(parse_url(config('app.url'), PHP_URL_HOST));
            $apex     = preg_replace('/^www\./i', '', $site->custom_domain);

            $apexOk = $cf->upsertARecord($zone['id'], $apex,        $serverIp);
            $wwwOk  = $cf->upsertARecord($zone['id'], 'www.' . $apex, $serverIp);

            // Save the connection and mark as configured
            $site->update([
                'connected_provider'  => 'cloudflare',
                'provider_token'      => encrypt($accessToken),
                'provider_zone_id'    => $zone['id'],
                'dns_auto_configured' => $apexOk && $wwwOk,
                // Since Cloudflare DNS is nearly instant, also verify immediately
                'domain_verified'     => $apexOk && $wwwOk,
            ]);

            if ($apexOk && $wwwOk) {
                return redirect()->route('dashboard')->with('success',
                    "🎉 All done! Your domain {$site->custom_domain} has been connected and should be live within a few minutes."
                );
            }

            return redirect()->route('dashboard')->with('error',
                'Connected to Cloudflare but could not update all DNS records. Please check your Cloudflare dashboard.'
            );

        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error',
                'Something went wrong connecting to Cloudflare: ' . $e->getMessage()
            );
        }
    }

    // ── GoDaddy API Key ──────────────────────────────────────────────────────────

    /**
     * Connect a GoDaddy account via API key + secret and push DNS records.
     * Called via fetch() from Site.vue — returns JSON.
     */
    public function connectGodaddy(Request $request): JsonResponse
    {
        $request->validate([
            'api_key'    => 'required|string|min:5',
            'api_secret' => 'required|string|min:5',
        ]);

        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site || $site->domain_type !== 'custom' || !$site->custom_domain) {
            return response()->json(['error' => 'Please save your custom domain first.'], 422);
        }

        $godaddy = new GodaddyService($request->input('api_key'), $request->input('api_secret'));

        // Test credentials
        if (!$godaddy->testConnection()) {
            return response()->json(['error' => 'Could not connect to GoDaddy — please check your API Key and Secret.'], 422);
        }

        // Check domain exists in this account
        if (!$godaddy->domainExists($site->custom_domain)) {
            return response()->json([
                'error' => "We couldn't find {$site->custom_domain} in your GoDaddy account. Make sure you're using the right API Key.",
            ], 422);
        }

        // Push A records
        $serverIp = gethostbyname(parse_url(config('app.url'), PHP_URL_HOST));
        $ok       = $godaddy->configureARecords($site->custom_domain, $serverIp);

        if (!$ok) {
            return response()->json(['error' => 'Connected to GoDaddy but could not update the DNS records. Please try again.'], 422);
        }

        // Persist the connection (store the composite key as JSON)
        $composite = json_encode(['key' => $request->input('api_key'), 'secret' => $request->input('api_secret')]);

        $site->update([
            'connected_provider'  => 'godaddy',
            'provider_token'      => encrypt($composite),
            'provider_zone_id'    => null,
            'dns_auto_configured' => true,
            'domain_verified'     => true,   // GoDaddy DNS is also fast
        ]);

        return response()->json([
            'success' => true,
            'message' => "🎉 All done! Your domain {$site->custom_domain} has been connected.",
        ]);
    }

    // ── Domain Connect OAuth (GoDaddy, Cloudflare, IONOS, NameSilo, …) ──────────

    /**
     * Probe the domain and — if its DNS provider supports Domain Connect —
     * redirect the user to their registrar's OAuth consent screen.
     *
     * Responds with JSON so the frontend can either start the redirect or
     * display a friendly fallback message (provider not supported).
     */
    public function probeAndRedirectDomainConnect(Request $request): JsonResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site || $site->domain_type !== 'custom' || !$site->custom_domain) {
            return response()->json(['error' => 'Please save your custom domain first.'], 422);
        }

        $domain = $site->custom_domain;

        // Step 1 — Discover whether the DNS provider supports Domain Connect
        $connectBase = DomainConnectService::discover($domain);

        if (!$connectBase) {
            return response()->json([
                'supported' => false,
                'message'   => "We couldn't detect Domain Connect support for {$domain}. Please use the manual DNS steps or connect Cloudflare/GoDaddy directly.",
            ]);
        }

        // Step 2 — Confirm the provider has our template and supports async OAuth
        $settings = DomainConnectService::fetchSettings($connectBase, $domain);

        if (!$settings) {
            return response()->json([
                'supported' => false,
                'message'   => "Your DNS provider was found but our template hasn't been registered with them yet. Please use the manual DNS steps while we get this set up.",
            ]);
        }

        // Step 3 — Build the OAuth consent URL and return it so the frontend
        //           can do window.location = url (avoids CORS issues with a
        //           direct server redirect on a JSON endpoint).
        $state       = Str::random(40);
        $serverIp    = gethostbyname(parse_url(config('app.url'), PHP_URL_HOST));
        $redirectUri = url('/dashboard/domain/connect/callback');

        $request->session()->put([
            'dc_state'  => $state,
            'dc_domain' => $domain,
        ]);

        $applyUrl = DomainConnectService::buildApplyUrl(
            connectBase:  $connectBase,
            domain:       $domain,
            serverIp:     $serverIp,
            redirectUri:  $redirectUri,
            state:        $state,
        );

        return response()->json([
            'supported' => true,
            'redirectTo' => $applyUrl,
        ]);
    }

    /**
     * Handle the Domain Connect OAuth callback.
     * The registrar has already applied the DNS records by this point —
     * we just need to record the connection and verify.
     */
    public function handleDomainConnectCallback(Request $request): RedirectResponse
    {
        // Verify state (CSRF protection)
        if ($request->input('state') !== $request->session()->pull('dc_state')) {
            return redirect()->route('dashboard')->with('error', 'Security check failed. Please try connecting again.');
        }

        if ($request->has('error')) {
            return redirect()->route('dashboard')->with('error', 'DNS configuration was cancelled.');
        }

        $domain = $request->session()->pull('dc_domain')
                  ?? $request->input('domain');

        $site = Site::where('user_id', auth()->id())->latest()->first();

        if (!$site) {
            return redirect()->route('dashboard')->with('error', 'Could not find your site.');
        }

        // The registrar applied the records — mark as configured.
        // We also run our standard DNS check to set domain_verified if records
        // have already propagated (Domain Connect providers are usually instant).
        $serverIp   = gethostbyname(parse_url(config('app.url'), PHP_URL_HOST));
        $apex       = preg_replace('/^www\./i', '', $domain ?? $site->custom_domain);
        $aRecords   = @dns_get_record($apex, DNS_A) ?: [];
        $verified   = collect($aRecords)->contains(fn($r) => ($r['ip'] ?? '') === $serverIp);

        $site->update([
            'connected_provider'  => 'domain_connect',
            'provider_token'      => null,
            'provider_zone_id'    => null,
            'dns_auto_configured' => true,
            'domain_verified'     => $verified,
        ]);

        $msg = $verified
            ? "🎉 All done! Your domain {$site->custom_domain} is now live."
            : "Your domain {$site->custom_domain} has been configured. It may take a few minutes to go live.";

        return redirect()->route('dashboard')->with('success', $msg);
    }

    // ── Disconnect ───────────────────────────────────────────────────────────────

    /**
     * Remove the provider connection from this site (does NOT delete the DNS records
     * at the provider — the user would need to do that manually).
     * Accepts both Inertia redirect and JSON (fetch) requests.
     */
    public function disconnect(Request $request): RedirectResponse|JsonResponse
    {
        $site = Site::where('user_id', auth()->id())->latest()->first();

        if ($site) {
            $site->update([
                'connected_provider'  => null,
                'provider_token'      => null,
                'provider_zone_id'    => null,
                'dns_auto_configured' => false,
                // Keep domain_verified as-is — DNS records are still pointing correctly
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('dashboard')->with('success', 'Provider disconnected.');
    }
}
