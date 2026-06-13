<?php

namespace App\Console\Commands;

use App\Models\MarketingSite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendStannpMailers extends Command
{
    protected $signature = 'marketing:send-stannp
                            {--status=pending   : Filter by status (pending, or any other value)}
                            {--limit=           : Max number of records to send in this run}
                            {--test             : Stannp test mode — generates a proof PDF but never dispatches and never charges; outputs the Stannp proof URL}
                            {--preview          : Generate PDFs locally and save to storage/app/mailer-previews/ — no API calls}
                            {--dry-run          : List which records would be sent, without generating PDFs or calling Stannp}';

    protected $description = 'Generate personalised postcard PDFs and dispatch them via the Stannp API';

    /** Stannp EU endpoint (used for UK mailings) */
    private const STANNP_API = 'https://api-eu1.stannp.com/v1/postcards/create';

    /** Path to the Python postcard generator, relative to base_path() */
    private const SCRIPT_PATH = 'scripts/postcard_template.py';

    public function handle(): int
    {
        $status  = $this->option('status');
        $limit   = $this->option('limit');
        $test    = (bool) $this->option('test');
        $preview = (bool) $this->option('preview');
        $dryRun  = (bool) $this->option('dry-run');

        $apiKey = config('services.stannp.key');

        if (! $apiKey && ! $dryRun && ! $preview) {
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

        $mode = match (true) {
            $dryRun  => 'DRY RUN',
            $preview => 'LOCAL PREVIEW',
            $test    => 'TEST MODE',
            default  => 'LIVE',
        };

        $this->info("Processing {$sites->count()} postcard(s) [{$mode}]…");

        if ($preview) {
            $previewDir = storage_path('app/mailer-previews');
            if (! is_dir($previewDir)) {
                mkdir($previewDir, 0755, true);
            }
            $this->line("Saving PDFs to: {$previewDir}");
        }

        $bar     = $this->output->createProgressBar($sites->count());
        $sent    = 0;
        $skipped = 0;

        foreach ($sites as $site) {
            $bar->advance();

            $appUrl        = rtrim(config('app.url'), '/');
            $previewUrl    = "{$appUrl}/claim/{$site->places_id}";
            $screenshotUrl = "https://321sites.com/screenshots/{$site->places_id}.png";
            $shortUrl      = "321sites.com/claim/{$site->places_id}";
            $qrUrl         = 'https://api.qrserver.com/v1/create-qr-code/'
                . '?size=400x400&margin=10&data=' . urlencode($previewUrl);

            $businessName = $site->business_name ?? '';
            $city         = $site->city ?? '';
            // Script takes the slug only; it appends .321sites.com internally
            $subdomain    = Str::slug($businessName);

            // ── DRY RUN: just list records ────────────────────────────────
            if ($dryRun) {
                $this->newLine();
                $this->line("  → {$site->business_name} | {$site->street}, {$site->postal_code}");
                $sent++;
                continue;
            }

            // ── Generate both PDFs in one Python invocation ───────────────
            $frontPath = sys_get_temp_dir() . '/stannp-front-' . uniqid() . '.pdf';
            $backPath  = sys_get_temp_dir() . '/stannp-back-'  . uniqid() . '.pdf';

            try {
                $this->runGenerator(
                    $script, $businessName, $city, $subdomain,
                    $screenshotUrl, $qrUrl, $shortUrl,
                    $frontPath, $backPath
                );
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

            // ── LOCAL PREVIEW: copy to storage and stop ───────────────────
            if ($preview) {
                $slug = preg_replace('/[^a-z0-9_-]/i', '_', $site->places_id);
                rename($frontPath, "{$previewDir}/{$slug}-front.pdf");
                rename($backPath,  "{$previewDir}/{$slug}-back.pdf");
                $this->newLine();
                $this->line("  ✓ {$site->business_name}");
                $this->line("      front → {$previewDir}/{$slug}-front.pdf");
                $this->line("      back  → {$previewDir}/{$slug}-back.pdf");
                $sent++;
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
                        'size'                => 'A5',
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
                        'status'         => $test ? $site->status : 'sent',
                    ]);

                    $sent++;

                    if ($test && $proofUrl) {
                        $this->newLine();
                        $this->line("  ✓ {$site->business_name} — proof: {$proofUrl}");
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
            } finally {
                @unlink($frontPath);
                @unlink($backPath);
            }
        }

        $bar->finish();
        $this->newLine();

        if ($preview) {
            $this->info("Saved {$sent} preview PDF pair(s) to storage/app/mailer-previews/");
        } elseif ($dryRun) {
            $this->info("Dry run complete — {$sent} record(s) would be sent.");
        } else {
            $this->info("Done. Sent: {$sent} | Skipped/failed: {$skipped}.");
            if (! $test && $sent > 0) {
                $this->comment("Tip: check your Stannp dashboard for print status and tracking.");
            }
        }

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Invoke postcard_template.py to generate both PDF pages in one call.
     * The script writes to --front-out and --back-out file paths.
     *
     * @throws \RuntimeException
     */
    private function runGenerator(
        string $script,
        string $businessName,
        string $city,
        string $subdomain,
        string $screenshotUrl,
        string $qrUrl,
        string $shortUrl,
        string $frontOut,
        string $backOut
    ): void {
        $fontDir = base_path('fonts');

        $args = [
            'python3',
            escapeshellarg($script),
            '--business-name=' . escapeshellarg($businessName),
            '--city=' . escapeshellarg($city),
            '--subdomain=' . escapeshellarg($subdomain),
            '--screenshot-url=' . escapeshellarg($screenshotUrl),
            '--qr-url=' . escapeshellarg($qrUrl),
            '--short-url=' . escapeshellarg($shortUrl),
            '--front-out=' . escapeshellarg($frontOut),
            '--back-out=' . escapeshellarg($backOut),
        ];

        if (is_dir($fontDir)) {
            $args[] = '--font-dir=' . escapeshellarg($fontDir);
        }

        $cmd = implode(' ', $args);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($proc)) {
            throw new \RuntimeException('Failed to spawn Python process');
        }

        fclose($pipes[0]);
        $stdout   = stream_get_contents($pipes[1]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                "postcard_template.py exited {$exitCode}" .
                ($stderr ? ": {$stderr}" : '') .
                ($stdout ? " [stdout: {$stdout}]" : '')
            );
        }

        if (! file_exists($frontOut) || ! file_exists($backOut)) {
            throw new \RuntimeException(
                'postcard_template.py exited 0 but output PDFs are missing'
            );
        }
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
