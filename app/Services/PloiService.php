<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Ploi API for provisioning SSL on customer custom
 * domains. We add the domain (apex + www) as aliases on our Ploi site so nginx
 * serves it, then request a Let's Encrypt certificate for it.
 *
 * @see https://developers.ploi.io/
 */
class PloiService
{
    protected const API = 'https://ploi.io/api';

    public function __construct(
        protected ?string $token = null,
        protected ?string $serverId = null,
        protected ?string $siteId = null,
    ) {
        $this->token    ??= config('services.ploi.token');
        $this->serverId ??= config('services.ploi.server_id');
        $this->siteId   ??= config('services.ploi.site_id');
    }

    /**
     * Whether enough credentials are present to talk to Ploi. When false,
     * callers should skip provisioning (e.g. local dev) rather than error.
     */
    public function configured(): bool
    {
        return filled($this->token) && filled($this->serverId) && filled($this->siteId);
    }

    /**
     * Add one or more domains as aliases on the site so nginx serves them.
     */
    public function addAliases(array $domains): Response
    {
        return $this->client()->post($this->sitePath('aliases'), [
            'aliases' => array_values($domains),
        ]);
    }

    /**
     * Request a Let's Encrypt certificate for the given domains.
     * Pass multiple domains comma-separated, e.g. "example.com,www.example.com".
     */
    public function createCertificate(string $certificate): Response
    {
        return $this->client()->post($this->sitePath('certificates'), [
            'type'        => 'letsencrypt',
            'certificate' => $certificate,
        ]);
    }

    /**
     * List certificates currently on the site (useful for polling status).
     */
    public function listCertificates(): Response
    {
        return $this->client()->get($this->sitePath('certificates'));
    }

    protected function client(): PendingRequest
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    protected function sitePath(string $suffix): string
    {
        return self::API . "/servers/{$this->serverId}/sites/{$this->siteId}/{$suffix}";
    }
}
