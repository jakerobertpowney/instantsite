<?php

namespace App\Console\Commands;

use App\Models\MarketingSite;
use Illuminate\Console\Command;

class ExportMarketingCsv extends Command
{
    protected $signature = 'marketing:export-csv
                            {--status=pending : Filter by status (pending, claimed, dismissed, or all)}
                            {--output=marketing_export.csv : Output file path}';

    protected $description = 'Export marketing_sites to a CSV ready for MailerLite import';

    public function handle(): int
    {
        $status = $this->option('status');
        $output = $this->option('output');

        $query = MarketingSite::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $sites = $query->get();

        if ($sites->isEmpty()) {
            $this->warn("No records found with status: {$status}");
            return self::FAILURE;
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $domain  = config('app.domain', parse_url($appUrl, PHP_URL_HOST));

        $handle = fopen($output, 'w');

        // MailerLite CSV headers — column names become the {$variable} names in templates
        fputcsv($handle, [
            'email',
            'business_name',
            'preview_url',
            'site_domain',
            'screenshot_url',
            'short_url',
            'delete_url',
            'town',
        ]);

        foreach ($sites as $site) {
            $slug        = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $site->business_name ?? ''));
            $slug        = trim($slug, '-');
            $previewUrl  = "{$appUrl}/claim/{$site->places_id}";
            $siteDomain  = "{$slug}.{$domain}";
            $screenshotUrl = "https://321sites.com/screenshots/{$site->places_id}.png";
            $shortUrl    = "321sites.com/claim/{$site->places_id}";
            $deleteUrl   = "{$appUrl}/claim/{$site->places_id}/dismiss";
            $town        = $site->city ?? '';

            fputcsv($handle, [
                '',               // email — to be filled before import
                $site->business_name ?? '',
                $previewUrl,
                $siteDomain,
                $screenshotUrl,
                $shortUrl,
                $deleteUrl,
                $town,
            ]);
        }

        fclose($handle);

        $this->info("Exported {$sites->count()} records to {$output}");

        return self::SUCCESS;
    }
}
