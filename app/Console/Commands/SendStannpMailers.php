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
                            {--test             : Stannp test mode — generates a proof PDF but never dispatches and never charges}
                            {--dry-run          : Preview which records would be sent, without calling Stannp at all}';

    protected $description = 'Generate personalised postcard PDFs and dispatch them via the Stannp API';

    /** Stannp EU endpoint (used for UK mailings) */
    private const STANNP_API = 'https://api-eu1.stannp.com/v1/postcards/create';

    /** Path to the Python postcard generator, relative to base_path() */
    private const SCRIPT_PATH = 'scripts/gen_postcard.py';

    public function handle(): int
    {
        $status = $this->option('status');
        $limit  = $this->option('limit');
        $test   = (bool) $this->option('test');
        $dryRun = (bool) $this->option('dry-run');

        $apiKey = config('services.stannp.key');

        if (! $apiKey && ! $dryRun) {
            $this->error('STANNP_API_KEY is not set in your environment.');
            return self::FAILURE;
        }

        $script = base_path(self::SCRIPT_PATH);
        if (! file_exists($script)) {
            $this->error("Postcard generator not found at: {$script}");
            return self::FAILURE;
        }

        $query = MarketingSite::query()
            ->where('status', $status)
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

        $mode = $dryRun ? 'DRY RUN' : ($test ? 'TEST MODE' : 'LIVE');
        $this->info("Sending {$sites->count()} postcard(s) via Stannp [{$mode}]…");

        $bar   = $this->output->createProgressBar($sites->count());
        $sent  = 0;
        $skipped = 0;

        foreach ($sites as $site) {
            $bar->advance();

            $appUrl        = rtrim(config('app.url'), '/');
            $previewUrl    = "{$appUrl}/claim/{$site->places_id}";
            $screenshotUrl = "https://321sites.com/screenshots/{$site->places_id}.png";
            $shortUrl      = "321sites.com/claim/{$site->places_id}";
            $qrUrl         = 'https://api.qrserver.com/v1/create-qr-code/'
                . '?size=300x300&margin=10&data=' . urlencode($previewUrl);

            if ($dryRun) {
                $this->newLine();
                $this->line("  → {$site->business_name} | {$site->street}, {$site->postal_code}");
                continue;
            }

            try {
                $frontPdf = $this->generatePage($script, 'front', $screenshotUrl, $qrUrl, $shortUrl);
                $backPdf  = $this->generatePage($script, 'back',  $screenshotUrl, $qrUrl, $shortUrl);
            } catch (\RuntimeException $e) {
                $this->newLine();
                $this->warn("  PDF generation failed for {$site->places_id}: {$e->getMessage()}");
                Log::error('SendStannpMailers: PDF generation failed', [
                    'places_id' => $site->places_id,
                    'error'     => $e->getMessage(),
                ]);
                $skipped++;
                continue;
            }

            $country = $this->normaliseCountry($site->country ?? 'GB');

            try {
                $response = Http::withBasicAuth($apiKey, '')
                    ->attach('front', $frontPdf, 'front.pdf')
                    ->attach('back',  $backPdf,  'back.pdf')
                    ->post(self::STANNP_API, [
                        'test'                => $test ? 'true' : 'false',
                        'size'                => 'A5',
                        'recipient[company]'  => $site->business_name ?? '',
                        'recipient[address1]' => $site->street ?? '',
                        'recipient[address2]' => $site->county ?? '',
                        'recipient[city]'     => $site->city ?? '',
                        'recipient[postcode]' => $site->postal_code ?? '',
                        'recipient[country]'  => $country,
                        'tags'                => '321sites,marketing-mailer',
                    ]);

                $body = $response->json();

                if ($response->successful() && ($body['success'] ?? false)) {
                    $stannpId = $body['data']['id'] ?? null;

                    $site->update([
                        'stannp_id'      => (string) $stannpId,
                        'stannp_sent_at' => now(),
                        // Only advance status to 'sent' on a real (non-test) dispatch
                        'status'         => $test ? $site->status : 'sent',
                    ]);

                    $sent++;
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
        $this->info("Done. Sent: {$sent} | Skipped/failed: {$skipped}.");

        if (! $test && ! $dryRun && $sent > 0) {
            $this->comment("Tip: check your Stannp dashboard for print status and tracking.");
        }

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Call the Python generator for one side of the postcard.
     * Returns raw PDF bytes.
     *
     * @throws \RuntimeException
     */
    private function generatePage(
        string $script,
        string $side,
        string $screenshotUrl,
        string $qrUrl,
        string $shortUrl
    ): string {
        $cmd = implode(' ', [
            'python3',
            escapeshellarg($script),
            '--side=' . escapeshellarg($side),
            '--screenshot-url=' . escapeshellarg($screenshotUrl),
            '--qr-url=' . escapeshellarg($qrUrl),
            '--short-url=' . escapeshellarg($shortUrl),
        ]);

        // Capture stdout (PDF bytes) and stderr separately
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($proc)) {
            throw new \RuntimeException("Failed to spawn Python process for side={$side}");
        }

        fclose($pipes[0]);
        $pdf    = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0 || empty($pdf)) {
            throw new \RuntimeException(
                "gen_postcard.py exited {$exitCode} for side={$side}" .
                ($stderr ? ": {$stderr}" : '')
            );
        }

        return $pdf;
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
