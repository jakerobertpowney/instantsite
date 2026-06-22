<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * On-demand TLS authorization endpoint for Caddy.
 *
 * Caddy calls this (configured as `on_demand_tls { ask ... }`) before it tries
 * to obtain a Let's Encrypt certificate for a hostname. We return 200 only for
 * hostnames we actually serve, so domains randomly pointed at the server can't
 * trigger certificate issuance and exhaust Let's Encrypt rate limits.
 *
 * Authorized hosts:
 *   - the app's own domain and its www. variant
 *   - any {slug}.{app-domain} subdomain that maps to an existing site
 *   - any verified custom domain (bare or www.)
 */
class TlsCheckController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $host = strtolower(trim((string) $request->query('domain')));

        abort_if($host === '', 404);

        $appDomain = strtolower((string) config('app.domain'));

        // The app's own apex + www.
        if ($appDomain !== '' && ($host === $appDomain || $host === 'www.' . $appDomain)) {
            return response('ok');
        }

        // A subdomain of the app domain — must map to a real site.
        if ($appDomain !== '' && str_ends_with($host, '.' . $appDomain)) {
            $slug = substr($host, 0, -strlen('.' . $appDomain));

            abort_unless($slug !== '' && Site::where('subdomain', $slug)->exists(), 404);

            return response('ok');
        }

        // Otherwise it must be a verified custom domain (allow bare + www.).
        $bare = preg_replace('/^www\./', '', $host);

        $allowed = Site::query()
            ->where('domain_verified', true)
            ->where(fn ($q) => $q->where('custom_domain', $host)->orWhere('custom_domain', $bare))
            ->exists();

        abort_unless($allowed, 404);

        return response('ok');
    }
}
