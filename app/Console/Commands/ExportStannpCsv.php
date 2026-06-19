<?php

namespace App\Console\Commands;

use App\Models\MarketingSite;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportStannpCsv extends Command
{
    protected $signature = 'marketing:export-stannp
                            {--status=pending : Filter by status (pending, claimed, dismissed, or all)}
                            {--output=stannp_export.csv : Output file path}';

    protected $description = 'Export marketing_sites to a CSV ready for Stannp variable-data postcard upload';

    public function handle(): int
    {
        $status = $this->option('status');
        $output = $this->option('output');

        $query = MarketingSite::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Only export records that have enough address data to post
        $sites = $query->whereNotNull('street')
            ->whereNotNull('postal_code')
            ->get();

        if ($sites->isEmpty()) {
            $this->warn("No records found with status: {$status} and a postable address.");
            return self::FAILURE;
        }

        $appUrl = rtrim(config('app.url'), '/');

        $handle = fopen($output, 'w');

        // Stannp required address columns + custom variable columns.
        // Variable column names must match the {{variable}} tags in the Stannp template.
        fputcsv($handle, [
            // Stannp address fields
            'firstname',
            'lastname',
            'address1',
            'address2',
            'town',
            'postcode',
            'country',
            // Custom template variables
            'screenshot_url',   // browser mockup image on the front
            'qr_url',           // QR code image — links to preview_url
            'short_url',        // "or type:" text on the back
            'preview_url',      // full claim URL (also QR destination)
            'business_name',    // e.g. "Hair Candy"
            'city',             // e.g. "Stockport"
            'subdomain',        // e.g. "hair-candy.321sites.com"
        ]);

        foreach ($sites as $site) {
            $previewUrl    = "{$appUrl}/claim/{$site->places_id}";
            $screenshotUrl = "https://321sites.com/screenshots/{$site->places_id}.png";
            $shortUrl      = $site->unique_code
                ? '321sites.com/go/' . $site->unique_code
                : '321sites.com/claim/' . $site->places_id;

            // QR code via qrserver.com — Stannp fetches this as an image at print time
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data='
                . urlencode($previewUrl);

            // Text template variables used in the Stannp variable-data template
            $businessName = $site->business_name ?? '';
            $city         = $site->city ?? '';
            $subdomain    = Str::slug($businessName) . '.' . config('app.domain', '321sites.com');

            // Split business name into firstname/lastname for Stannp's address block.
            // Stannp doesn't have a dedicated "company" field on postcards — first+last
            // prints as a single line so we put the full name in firstname and leave
            // lastname blank.
            $firstname = $businessName;
            $lastname  = '';

            // Address lines
            $address1 = $site->street ?? '';
            $address2 = $site->county ?? '';
            $town     = $city;
            $postcode = $site->postal_code ?? '';
            $country  = strtoupper($site->country ?? 'GB');

            // Normalise country to ISO 3166-1 alpha-2 for Stannp
            if (strtolower($country) === 'united kingdom' || strtolower($country) === 'uk') {
                $country = 'GB';
            }

            fputcsv($handle, [
                $firstname,
                $lastname,
                $address1,
                $address2,
                $town,
                $postcode,
                $country,
                $screenshotUrl,
                $qrUrl,
                $shortUrl,
                $previewUrl,
                $businessName,
                $city,
                $subdomain,
            ]);
        }

        fclose($handle);

        $count = $sites->count();
        $this->info("Exported {$count} records to {$output}");

        $skipped = MarketingSite::where('status', $status === 'all' ? '!=' : $status)
            ->whereNull('street')
            ->orWhereNull('postal_code')
            ->count();

        if ($skipped > 0) {
            $this->warn("{$skipped} records skipped — missing street or postcode.");
        }

        return self::SUCCESS;
    }
}
