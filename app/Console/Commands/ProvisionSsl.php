<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionCustomDomainSsl;
use App\Models\Site;
use App\Services\PloiService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ProvisionSsl extends Command
{
    protected $signature = 'ssl:provision
                            {site? : Site ID, custom domain, or subdomain to provision SSL for}
                            {--all : Provision for every verified custom-domain site (backfill)}
                            {--sync : Run now instead of queueing the job}';

    protected $description = 'Provision a Let\'s Encrypt certificate via Ploi for a verified custom domain';

    public function handle(PloiService $ploi): int
    {
        if (! $ploi->configured()) {
            $this->error('Ploi is not configured. Set PLOI_API_TOKEN, PLOI_SERVER_ID and PLOI_SITE_ID in your .env.');
            return self::FAILURE;
        }

        $sites = $this->resolveSites();

        if ($sites->isEmpty()) {
            $this->warn('No matching verified custom-domain sites found.');
            return self::FAILURE;
        }

        $sync = (bool) $this->option('sync');

        foreach ($sites as $site) {
            if ($site->domain_type !== 'custom' || ! $site->domain_verified || blank($site->custom_domain)) {
                $this->warn("Skipping site #{$site->id} — not a verified custom domain.");
                continue;
            }

            if ($sync) {
                ProvisionCustomDomainSsl::dispatchSync($site->id);
                $this->info("Provisioned SSL for {$site->custom_domain} (site #{$site->id}).");
            } else {
                ProvisionCustomDomainSsl::dispatch($site->id);
                $this->info("Queued SSL provisioning for {$site->custom_domain} (site #{$site->id}).");
            }
        }

        if (! $sync) {
            $this->line('Jobs queued — ensure a queue worker is running to process them.');
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Site>
     */
    private function resolveSites(): Collection
    {
        if ($this->option('all')) {
            return Site::query()
                ->where('domain_type', 'custom')
                ->where('domain_verified', true)
                ->whereNotNull('custom_domain')
                ->get();
        }

        $needle = $this->argument('site');

        if (blank($needle)) {
            $this->error('Provide a site ID / domain, or pass --all to backfill every verified custom domain.');
            return new Collection();
        }

        $site = ctype_digit((string) $needle)
            ? Site::find((int) $needle)
            : Site::where('custom_domain', $needle)->orWhere('subdomain', $needle)->first();

        return $site ? new Collection([$site]) : new Collection();
    }
}
