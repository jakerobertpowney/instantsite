<?php

namespace App\Console\Commands;

use App\Models\MarketingSite;
use Illuminate\Console\Command;

class GeneratePostcardBatch extends Command
{
    protected $signature = 'marketing:generate-postcards
                            {--status=pending        : Filter by status (pending, sent, or all)}
                            {--limit=                : Max number of records to generate}
                            {--out=                  : Output directory (default: storage/app/postcard-batches)}
                            {--overwrite             : Re-render PDFs that already exist}
                            {--guides                : Draw trim/safe guides for proofing}
                            {--font-dir=             : Path to Inter .ttf font files}';

    protected $description = 'Batch-generate personalised postcard PDFs from marketing_sites records';

    public function handle(): int
    {
        $scriptsDir = base_path('scripts');
        $batchScript = $scriptsDir . '/generate_batch.py';
        $templateScript = $scriptsDir . '/postcard_template.py';

        foreach ([$batchScript, $templateScript] as $script) {
            if (! file_exists($script)) {
                $this->error("Required script not found: {$script}");
                return self::FAILURE;
            }
        }

        // ── Build query ───────────────────────────────────────────────────────
        $status = $this->option('status');
        $limit  = $this->option('limit');

        $query = MarketingSite::query()
            ->whereNotNull('places_id')
            ->whereNotNull('unique_code');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $sites = $query->get(['id', 'places_id', 'unique_code', 'business_name', 'city', 'status']);

        if ($sites->isEmpty()) {
            $this->warn("No records found (status={$status}).");
            return self::SUCCESS;
        }

        $this->info("Found {$sites->count()} record(s). Writing CSV…");

        // ── Write temp CSV ────────────────────────────────────────────────────
        $csvPath = sys_get_temp_dir() . '/321sites-batch-' . uniqid() . '.csv';
        $handle  = fopen($csvPath, 'w');
        fputcsv($handle, ['id', 'places_id', 'unique_code', 'business_name', 'city', 'status']);

        foreach ($sites as $site) {
            fputcsv($handle, [
                $site->id,
                $site->places_id,
                $site->unique_code,
                $site->business_name ?? '',
                $site->city ?? '',
                $site->status ?? '',
            ]);
        }

        fclose($handle);

        // ── Resolve output directory ──────────────────────────────────────────
        $outDir = $this->option('out') ?: storage_path('app/postcard-batches');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // ── Build Python command ──────────────────────────────────────────────
        $args = [
            'python3',
            escapeshellarg($batchScript),
            '--csv=' . escapeshellarg($csvPath),
            '--out=' . escapeshellarg($outDir),
        ];

        if ($fontDir = $this->option('font-dir') ?: (is_dir(base_path('fonts')) ? base_path('fonts') : null)) {
            $args[] = '--font-dir=' . escapeshellarg($fontDir);
        }

        if ($this->option('overwrite')) {
            $args[] = '--overwrite';
        }

        if ($this->option('guides')) {
            $args[] = '--guides';
        }

        // Run from the scripts directory so `import postcard_template` resolves
        $cmd = 'cd ' . escapeshellarg($scriptsDir) . ' && ' . implode(' ', $args);

        $this->line("Output directory: {$outDir}");
        $this->newLine();

        // ── Stream output ─────────────────────────────────────────────────────
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($proc)) {
            @unlink($csvPath);
            $this->error('Failed to start Python process.');
            return self::FAILURE;
        }

        fclose($pipes[0]);

        // Stream stdout line-by-line so progress appears in real time
        while (! feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false) {
                $this->output->write($line);
            }
        }

        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        @unlink($csvPath);

        if ($exitCode !== 0) {
            if ($stderr) {
                $this->newLine();
                $this->error("Python error output:\n{$stderr}");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
