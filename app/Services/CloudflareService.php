<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class CloudflareService
{
    protected const API = 'https://api.cloudflare.com/client/v4';
    protected const AUTH_URL = 'https://dash.cloudflare.com/oauth2/auth';
    protected const TOKEN_URL = 'https://dash.cloudflare.com/oauth2/token';

    // ── OAuth helpers ────────────────────────────────────────────────────────────

    /**
     * Build the Cloudflare OAuth authorization URL.
     */
    public static function authorizationUrl(string $state, string $codeChallenge): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'             => config('services.cloudflare.client_id'),
            'response_type'         => 'code',
            'redirect_uri'          => config('services.cloudflare.redirect'),
            'scope'                 => 'account:read zone.dns:edit offline_access',
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * Exchange an authorization code for an access token (+ refresh token).
     * Returns ['access_token' => ..., 'refresh_token' => ...] or throws.
     */
    public static function exchangeCode(string $code, string $codeVerifier): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('services.cloudflare.client_id'),
            'client_secret' => config('services.cloudflare.client_secret'),
            'redirect_uri'  => config('services.cloudflare.redirect'),
            'code'          => $code,
            'code_verifier' => $codeVerifier,
        ]);

        $response->throw();

        return $response->json();
    }

    // ── API helpers (require a valid access token) ────────────────────────────────

    /**
     * List all zones (domains) in the authenticated account.
     */
    public function listZones(): array
    {
        $response = $this->get('/zones', ['per_page' => 50]);
        return $response['result'] ?? [];
    }

    /**
     * Find the Cloudflare zone that owns the given domain name.
     * e.g. "www.davespaint.co.uk" → zone for "davespaint.co.uk"
     */
    public function findZoneForDomain(string $domain): ?array
    {
        $zones = $this->listZones();

        // Strip www. prefix so we match the apex zone
        $apex = preg_replace('/^www\./i', '', $domain);

        foreach ($zones as $zone) {
            if ($zone['name'] === $apex || str_ends_with($apex, '.' . $zone['name'])) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Create or update an A record for the given name within a zone.
     * Proxied is intentionally off so our server-side DNS check still works.
     */
    public function upsertARecord(string $zoneId, string $name, string $ip): bool
    {
        // Check for existing record
        $existing = $this->get("/zones/{$zoneId}/dns_records", [
            'type' => 'A',
            'name' => $name,
        ])['result'] ?? [];

        $payload = [
            'type'    => 'A',
            'name'    => $name,
            'content' => $ip,
            'ttl'     => 1,      // "Auto" TTL
            'proxied' => false,  // Direct DNS — keeps our verification check working
        ];

        if (!empty($existing)) {
            $recordId = $existing[0]['id'];
            $result   = $this->put("/zones/{$zoneId}/dns_records/{$recordId}", $payload);
        } else {
            $result = $this->post("/zones/{$zoneId}/dns_records", $payload);
        }

        return ($result['success'] ?? false) === true;
    }

    // ── HTTP internals ────────────────────────────────────────────────────────────

    public function __construct(protected string $accessToken) {}

    protected function get(string $path, array $query = []): array
    {
        return Http::withToken($this->accessToken)
            ->get(self::API . $path, $query)
            ->throw()
            ->json();
    }

    protected function post(string $path, array $data): array
    {
        return Http::withToken($this->accessToken)
            ->post(self::API . $path, $data)
            ->throw()
            ->json();
    }

    protected function put(string $path, array $data): array
    {
        return Http::withToken($this->accessToken)
            ->put(self::API . $path, $data)
            ->throw()
            ->json();
    }
}
