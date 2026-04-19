<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GodaddyService
{
    protected const API = 'https://api.godaddy.com/v1';

    public function __construct(
        protected string $apiKey,
        protected string $apiSecret,
    ) {}

    /**
     * Test the credentials are valid by fetching the account's domains.
     */
    public function testConnection(): bool
    {
        $response = Http::withHeaders($this->authHeaders())
            ->get(self::API . '/domains', ['limit' => 1]);

        return $response->successful();
    }

    /**
     * Check that the given domain is in this GoDaddy account.
     */
    public function domainExists(string $domain): bool
    {
        $apex     = $this->apexDomain($domain);
        $response = Http::withHeaders($this->authHeaders())
            ->get(self::API . "/domains/{$apex}");

        return $response->successful();
    }

    /**
     * Add/replace A records for the apex (@) and www to point to $ip.
     * Uses GoDaddy's replace endpoint which is idempotent.
     */
    public function configureARecords(string $domain, string $ip): bool
    {
        $apex = $this->apexDomain($domain);

        $apexOk = Http::withHeaders($this->authHeaders())
            ->put(self::API . "/domains/{$apex}/records/A/@", [
                ['data' => $ip, 'ttl' => 600],
            ])
            ->successful();

        $wwwOk = Http::withHeaders($this->authHeaders())
            ->put(self::API . "/domains/{$apex}/records/A/www", [
                ['data' => $ip, 'ttl' => 600],
            ])
            ->successful();

        return $apexOk && $wwwOk;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────────

    protected function authHeaders(): array
    {
        return [
            'Authorization' => "sso-key {$this->apiKey}:{$this->apiSecret}",
        ];
    }

    /**
     * Strip any subdomain prefix to get the registrable domain.
     * e.g. "www.davespaint.co.uk" → "davespaint.co.uk"
     */
    protected function apexDomain(string $domain): string
    {
        return preg_replace('/^www\./i', '', $domain);
    }
}
