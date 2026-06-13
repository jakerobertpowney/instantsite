<?php

namespace App\Console\Commands;

use App\Models\MarketingSite;
use Illuminate\Console\Command;

class MarketingPlacesJson extends Command
{
    protected $signature = 'marketing:places-json
                            {--status=all : Filter by status (pending|claimed|dismissed|all)}
                            {--limit= : Limit the number of rows returned}';

    protected $description = 'Output a JSON array of marketing site places_ids for use by the screenshot script';

    public function handle(): int
    {
        $query = MarketingSite::query()->select('places_id', 'business_name');

        $status = $this->option('status');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $rows = $query->get()->map(fn ($row) => [
            'places_id'     => $row->places_id,
            'business_name' => $row->business_name,
        ]);

        $this->output->write(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
