<?php

namespace App\Console\Commands;

use App\Models\MarketingSite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendStannpMailers extends Command
{
    protected $signature = 'marketing:send-stannp
                            {--status=pending   : Filter by status (pending, or any other value)}
                            {--limit=           : Max number of records to send in this run}
                            {--batch-dir=       : Directory containing pre-generated PDFs (default: storage/app/postcard-batches)}
                            {--test             : Stannp test mode — proof PDF only, never charges}
                            {--dry-run          : List which records would be sent without calling Stannp}';

    protected $description = 'Dispatch pre-generated postcard PDFs via the Stannp API';

    /** Stannp EU endpoint (used for UK mailings) */
    private const STANNP_API = 'https://api-eu1.stannp.com/v1/postcards/create';

    public function handle(): int
    {
        $status  = $this->option('status');
        $limit   = $this->option('limit');
        $test    = (bool) $this->option('test');
        $dryRun  = (bool) $this->option('dry-run');
        $batchDir = rtrim($this->option('batch-dir') ?: storage_path('app/postcard-batches'), '/');

        $apiKey = config('services.stannp.key');

        if (! $apiKey && ! $dryRun) {
            $this->error('STANNP_API_KEY is not set in your environment.');
            return self::FAILURE;
        }

        $query = MarketingSite::query()
            ->where('status', $status)
            ->whereNull('website')
            ->whereNotNull('street')
            ->whereNotNull('postal_code')
            ->whereNull('stannp_id'); // skip already-dispatched records

        if ($limit) {
            $query->limit((int) $limit);
        }

        $sites = $query->get();

        if ($sites->isEmpty()) {
            $this->warn("No sendable records found (status={$status}, has address, no stannp_id).");
            return self::SUCCESS;
        }

        $mode = match (true) {
            $dryRun => 'DRY RUN',
            $test   => 'TEST MODE',
            default => 'LIVE',
        };

        $this->info("Processing {$sites->count()} postcard(s) [{$mode}]…");
        $this->line("Reading PDFs from: {$batchDir}");
        $this->newLine();

        $bar     = $this->output->createProgressBar($sites->count());
        $sent    = 0;
        $skipped = 0;

        foreach ($sites as $site) {
            $bar->advance();

            $businessName = $site->business_name ?? '';
            $city         = $site->city ?? '';
            $folder       = $batchDir . '/' . $this->batchFolderName($site->id, $businessName);
            $frontPath    = $folder . '/front.pdf';
            $backPath     = $folder . '/back.pdf';

            // ── DRY RUN: just list records ────────────────────────────────
            if ($dryRun) {
                $exists = file_exists($frontPath) && file_exists($backPath) ? '✓' : '✗ missing PDFs';
                $this->newLine();
                $this->line("  [{$exists}] {$businessName} | {$site->street}, {$site->postal_code}");
                $this->line("      {$folder}");
                $sent++;
                continue;
            }

            // ── Check pre-generated PDFs exist ────────────────────────────
            if (! file_exists($frontPath) || ! file_exists($backPath)) {
                $this->newLine();
                $this->warn("  Skipping {$businessName} — PDFs not found at {$folder}");
                $this->warn("  Run: php artisan marketing:generate-postcards first.");
                Log::warning('SendStannpMailers: pre-generated PDFs missing', [
                    'places_id' => $site->places_id,
                    'folder'    => $folder,
                ]);
                $skipped++;
                continue;
            }

            // ── SEND (test or live) via Stannp API ────────────────────────
            $country = $this->normaliseCountry($site->country ?? 'GB');

            try {
                $response = Http::withBasicAuth($apiKey, '')
                    ->attach('front', file_get_contents($frontPath), 'front.pdf')
                    ->attach('back',  file_get_contents($backPath),  'back.pdf')
                    ->post(self::STANNP_API, [
                        'test'                => $test ? 'true' : 'false',
                        'size'                => 'A5-PORT',
                        'padding'             => 0,
                        'recipient[company]'  => $businessName,
                        'recipient[address1]' => $site->street ?? '',
                        'recipient[address2]' => $site->county ?? '',
                        'recipient[city]'     => $city,
                        'recipient[postcode]' => $site->postal_code ?? '',
                        'recipient[country]'  => $country,
                        'tags'                => '321sites,marketing-mailer',
                    ]);

                $body = $response->json();

                if ($response->successful() && ($body['success'] ?? false)) {
                    $stannpId = $body['data']['id'] ?? null;
                    $proofUrl = $body['data']['pdf'] ?? null;

                    $site->update([
                        'stannp_id'      => (string) $stannpId,
                        'stannp_sent_at' => now(),
                        'status'         => $test ? $site->status : 'mailed',
                    ]);

                    $sent++;

                    if ($test && $proofUrl) {
                        $this->newLine();
                        $this->line("  ✓ {$businessName} — proof: {$proofUrl}");
                    }
                } else {
                    $error = $body['error'] ?? $response->body();
                    $this->newLine();
                    $this->warn("  Stannp rejected {$site->places_id}: {$error}");
                    Log::warning('SendStannpMailers: Stannp API error', [
                        'places_id' => $site->places_id,
                        'response'  => $body,
                    ]);
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("  HTTP error for {$site->places_id}: {$e->getMessage()}");
                Log::error('SendStannpMailers: HTTP exception', [
                    'places_id' => $site->places_id,
                    'error'     => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("Dry run complete — {$sent} record(s) listed.");
        } else {
            $this->info("Done. Sent: {$sent} | Skipped/failed: {$skipped}.");
            if (! $test && $sent > 0) {
                $this->comment("Tip: check your Stannp dashboard for print status and tracking.");
            }
        }

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Reproduce the folder name that generate_batch.py uses:
     *   {id:04d}_{slugify(business_name)}
     * where slugify() lowercases, replaces & with "and", then collapses
     * non-alphanumeric runs to hyphens.
     */
    private function batchFolderName(int $id, string $businessName): string
    {
        $slug = strtolower(str_replace('&', 'and', $businessName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return sprintf('%04d_%s', $id, $slug);
    }

    private function normaliseCountry(string $country): string
    {
        $upper = strtoupper(trim($country));
        return match ($upper) {
            'UNITED KINGDOM', 'UK' => 'GB',
            default => $upper,
        };
    }
}
