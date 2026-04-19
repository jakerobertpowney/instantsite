<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Domain Connect — https://domainconnect.org
 *
 * An open standard (MIT licence, IETF draft) supported by GoDaddy, Cloudflare,
 * IONOS, NameSilo, Vercel, WordPress.com and others. Users see a simple
 * "Allow 321Sites to configure your DNS?" consent screen at their own
 * registrar — no API keys, no technical knowledge required.
 *
 * Template registration:
 *   Submit a PR to https://github.com/Domain-Connect/Templates with the JSON
 *   file in public/domainconnect/ before going live. During local development
 *   the template file is served from /storage/app/public/domainconnect/.
 *
 * Spec reference: https://github.com/Domain-Connect/spec
 */
class DomainConnectService
{
    /** Your registered provider ID — matches the template file name prefix. */
    public const PROVIDER_ID  = '321sites';

    /** Your registered service ID — matches the template file name suffix. */
    public const SERVICE_ID   = 'connect-website';

    // ── Discovery ─────────────────────────────────────────────────────────────────

    /**
     * Discover the Domain Connect API base URL for a given domain by querying
     * the `_domainconnect.<domain>` TXT record.
     *
     * Returns null if the domain's DNS provider does not support Domain Connect.
     */
    public static function discover(string $domain): ?string
    {
        $apex = preg_replace('/^www\./i', '', $domain);

        // PHP's dns_get_record does not reliably resolve TXT records in all
        // environments, so we also try the Cloudflare DoH API as a fallback.
        $records = @dns_get_record('_domainconnect.' . $apex, DNS_TXT) ?: [];

        if (empty($records)) {
            // Fallback: Cloudflare DNS-over-HTTPS (returns results without
            // requiring the sandbox to have a working resolver)
            $records = self::dohLookup('_domainconnect.' . $apex, 'TXT');
        }

        foreach ($records as $record) {
            $txt = $record['txt'] ?? $record['data'] ?? '';
            if ($txt && str_starts_with($txt, 'https://')) {
                return rtrim($txt, '/');
            }
        }

        return null;
    }

    /**
     * Fetch the Domain Connect settings from the provider's API to confirm
     * it supports the asynchronous (OAuth) flow.
     *
     * Returns the settings array, or null if the provider/template is not found.
     */
    public static function fetchSettings(string $connectBase, string $domain): ?array
    {
        $apex = preg_replace('/^www\./i', '', $domain);
        $url  = "{$connectBase}/v2/domainTemplates/providers/"
              . self::PROVIDER_ID . '/services/' . self::SERVICE_ID;

        $response = Http::timeout(5)->get($url, ['domain' => $apex]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        // Must support async (OAuth) flow
        if (empty($data['syncRedirectDomain']) && empty($data['urlAsyncUXServer'])) {
            return null;
        }

        return $data;
    }

    // ── OAuth redirect ────────────────────────────────────────────────────────────

    /**
     * Build the URL that will take the user to their registrar's consent screen.
     *
     * The provider will:
     *   1. Show the user a "Allow 321Sites to configure your DNS?" dialog.
     *   2. Apply our A records.
     *   3. Redirect to $redirectUri with a `?domain=...&state=...` response.
     *
     * Variables (@serverip@) are substituted by the provider when applying
     * the template — they must match the variable definitions in the template JSON.
     */
    public static function buildApplyUrl(
        string $connectBase,
        string $domain,
        string $serverIp,
        string $redirectUri,
        string $state,
    ): string {
        $apex = preg_replace('/^www\./i', '', $domain);

        // Check if the provider gave us an explicit async UX server URL
        // (some providers differentiate between the API base and the UX server)
        $applyBase = rtrim($connectBase, '/');

        return $applyBase
            . '/v2/domainTemplates/providers/' . self::PROVIDER_ID
            . '/services/' . self::SERVICE_ID
            . '/apply?' . http_build_query([
                'domain'       => $apex,
                'host'         => '',           // empty = apex (@)
                'IP'           => $serverIp,    // variable defined in template
                'redirect_uri' => $redirectUri,
                'state'        => $state,
            ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────────

    /**
     * Cloudflare DNS-over-HTTPS lookup — used as a fallback when the system
     * resolver doesn't return results for TXT queries.
     */
    protected static function dohLookup(string $name, string $type): array
    {
        try {
            $response = Http::timeout(3)
                ->withHeaders(['Accept' => 'application/dns-json'])
                ->get('https://cloudflare-dns.com/dns-query', [
                    'name' => $name,
                    'type' => $type,
                ]);

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json('Answer', []))
                ->map(fn($r) => ['txt' => trim($r['data'] ?? '', '"')])
                ->all();

        } catch (\Throwable) {
            return [];
        }
    }
}
