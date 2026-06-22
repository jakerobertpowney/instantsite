<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\PloiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Provisions a Let's Encrypt certificate for a site's verified custom domain
 * via the Ploi API: registers the apex + www as aliases on our Ploi site, then
 * requests the certificate.
 *
 * Dispatched automatically when a site's custom domain becomes verified.
 * No-ops (without failing) when Ploi credentials aren't configured.
 */
class ProvisionCustomDomainSsl implements ShouldQueue
{
    use Queueable;

    /** Retry across ~ an hour to ride out DNS propagation / transient Ploi errors. */
    public int $tries = 5;

    public array $backoff = [60, 300, 900, 1800];

    public function __construct(
        private readonly int $siteId,
    ) {}

    public function handle(PloiService $ploi): void
    {
        if (! $ploi->configured()) {
            Log::info('ProvisionCustomDomainSsl skipped — Ploi not configured.', ['site_id' => $this->siteId]);
            return;
        }

        $site = Site::find($this->siteId);

        if (! $site || $site->domain_type !== 'custom' || ! $site->domain_verified || blank($site->custom_domain)) {
            Log::info('ProvisionCustomDomainSsl skipped — site no longer eligible.', ['site_id' => $this->siteId]);
            return;
        }

        $apex    = preg_replace('/^www\./i', '', strtolower($site->custom_domain));
        $domains = [$apex, 'www.' . $apex];

        // 1. Add the domain (+www) as aliases so nginx serves it.
        $aliasResponse = $ploi->addAliases($domains);

        // 422 here typically means the alias already exists — safe to continue.
        if ($aliasResponse->failed() && $aliasResponse->status() !== 422) {
            Log::warning('Ploi addAliases failed.', [
                'site_id' => $site->id,
                'domain'  => $apex,
                'status'  => $aliasResponse->status(),
                'body'    => $aliasResponse->body(),
            ]);
            $aliasResponse->throw(); // trigger a retry
        }

        // 2. Request the Let's Encrypt certificate for apex + www.
        $certResponse = $ploi->createCertificate(implode(',', $domains));

        if ($certResponse->failed()) {
            Log::warning('Ploi createCertificate failed.', [
                'site_id' => $site->id,
                'domain'  => $apex,
                'status'  => $certResponse->status(),
                'body'    => $certResponse->body(),
            ]);
            $certResponse->throw(); // trigger a retry
        }

        Log::info('Requested Ploi SSL certificate for custom domain.', [
            'site_id' => $site->id,
            'domain'  => $apex,
        ]);
    }
}
