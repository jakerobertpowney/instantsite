<?php

namespace App\Jobs;

use App\Models\TemporarySite;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchBusinessServices implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Best-effort — do not retry on failure.
     */
    public int $tries = 1;

    /**
     * Allow extra time for Claude's web_search tool round-trips.
     */
    public int $timeout = 90;

    public function __construct(
        private readonly TemporarySite $site
    ) {}

    public function handle(): void
    {
        try {
            $businessName = $this->site->business_name;
            $businessType = $this->site->business_type;
            $websiteUri   = $this->site->website_url;
            $city         = $this->site->city;

            // ── Tier 1: scrape their own website ──────────────────────────
            if ($websiteUri) {
                $services = $this->extractFromUrl($websiteUri, 'Tier 1 (own website)');
                if (!empty($services)) {
                    $this->persist($services, 'website');
                    return;
                }
            }

            // ── Tier 2: Claude web_search — finds the business on Fresha,
            //    Yelp, Checkatrade etc. and extracts services in one call ──
            if ($businessName) {
                $services = $this->searchInternetWithAI($businessName, $businessType, $city);
                if (!empty($services)) {
                    return; // persist() already called inside searchInternetWithAI
                }
            }

            // ── Tier 3: AI-generated typical services for this business type ──
            if ($businessType) {
                $services = $this->generateTypicalServices($businessType, $city);
                if (!empty($services)) {
                    $this->persist($services, 'ai');
                }
            }

        } catch (\Throwable $e) {
            Log::warning('FetchBusinessServices failed for site ' . $this->site->id . ': ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tier 2 — Claude web_search
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ask Claude (with the built-in web_search tool) to find this business on
     * known platforms and return a structured services list. No third-party
     * search API key required — uses the existing ANTHROPIC_API_KEY.
     *
     * @return array<int, array{id:string, name:string, description:string|null, price:string|null, show_price:bool, featured:bool}>
     */
    private function searchInternetWithAI(
        string $businessName,
        ?string $businessType,
        ?string $city
    ): array {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (empty($apiKey)) {
            return [];
        }

        // Build a human-readable list of platform domains for the prompt
        $platforms       = $this->platformsForType($businessType);
        $platformDomains = array_values(array_filter(
            array_map(fn($p) => $this->siteFilterForPlatform($p), $platforms)
        ));
        $platformList = implode(', ', $platformDomains);
        $locationStr  = $city ? " in {$city}" : '';

        $prompt = <<<PROMPT
Search for the business "{$businessName}"{$locationStr} on these platforms: {$platformList}.

Find their listing and extract the complete list of services or products they offer, including prices where shown.

Return ONLY a JSON array of objects. Each object must have:
- "name": string — the service or product name (required)
- "description": string or null — a brief description if one is provided on the listing
- "price": string or null — the price exactly as shown (e.g. "£18", "From £45", "£20–£35"), or null if not listed
- "source": string — the domain you found them on (e.g. "fresha.com", "yelp.com")

Rules:
- Only include actual services/products — not navigation items, testimonials, or generic headings
- Maximum 20 items
- If no services are found on any platform, return []
- Return ONLY the JSON array — no explanation, no markdown, no code block
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'anthropic-beta'    => 'web-search-2025-03-05',
                'content-type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 2000,
                'tools'      => [
                    ['type' => 'web_search_20250305', 'name' => 'web_search'],
                ],
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $response->throw();

            // The response content may contain tool_use + tool_result blocks
            // followed by a final text block with the JSON answer.
            $content = $response->json('content') ?? [];
            $text    = '';
            foreach (array_reverse($content) as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $text = trim($block['text'] ?? '');
                    break;
                }
            }

            if (empty($text)) {
                return [];
            }

            // Strip any accidental markdown fences
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $parsed = json_decode($text, true);

            if (!is_array($parsed) || empty($parsed)) {
                return [];
            }

            // Determine which platform Claude found the business on
            $rawSource   = $parsed[0]['source'] ?? 'internet';
            $foundSource = preg_replace('/\.(com|co\.uk|org|net)$/i', '', (string) $rawSource);

            $services = array_values(array_map(fn($item) => [
                'id'          => Str::uuid()->toString(),
                'name'        => (string) ($item['name'] ?? ''),
                'description' => isset($item['description']) && $item['description'] !== ''
                                    ? (string) $item['description'] : null,
                'price'       => isset($item['price']) && $item['price'] !== ''
                                    ? (string) $item['price'] : null,
                'show_price'  => !empty($item['price']),
                'featured'    => false,
            ], array_filter($parsed, fn($i) => !empty($i['name']))));

            if (!empty($services)) {
                $this->persist($services, $foundSource);
            }

            Log::info("FetchBusinessServices (Tier 2): found " . count($services) . " services via web_search for \"{$businessName}\" (source: {$foundSource}).");

            return $services;

        } catch (\Throwable $e) {
            Log::warning('FetchBusinessServices (Tier 2 AI web search): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Return the ordered list of platforms to check for a given business type.
     * Yelp is always included — it covers every business type.
     */
    private function platformsForType(?string $businessType): array
    {
        if (!$businessType) {
            return ['yelp'];
        }

        $type = strtolower($businessType);

        $typeMap = [
            // Beauty & wellness
            'hair'       => ['fresha', 'treatwell', 'booksy'],
            'salon'      => ['fresha', 'treatwell', 'booksy'],
            'barber'     => ['fresha', 'booksy'],
            'nail'       => ['fresha', 'treatwell', 'booksy'],
            'spa'        => ['fresha', 'treatwell'],
            'beauty'     => ['fresha', 'treatwell', 'booksy'],
            'lash'       => ['fresha', 'booksy'],
            'brow'       => ['fresha', 'booksy'],
            'tattoo'     => ['booksy'],
            'massage'    => ['fresha', 'treatwell'],
            // Trades
            'plumb'      => ['checkatrade', 'rated_people', 'trustatrader'],
            'paint'      => ['checkatrade', 'rated_people', 'trustatrader'],
            'electric'   => ['checkatrade', 'rated_people', 'trustatrader'],
            'builder'    => ['checkatrade', 'rated_people', 'trustatrader'],
            'carpenter'  => ['checkatrade', 'rated_people'],
            'landscap'   => ['checkatrade', 'rated_people'],
            'garden'     => ['checkatrade', 'rated_people'],
            'cleaner'    => ['checkatrade', 'bark'],
            'cleaning'   => ['checkatrade', 'bark'],
            'locksmith'  => ['checkatrade'],
            'roofer'     => ['checkatrade', 'rated_people'],
            'roofing'    => ['checkatrade', 'rated_people'],
            'handyman'   => ['checkatrade', 'bark'],
        ];

        $typePlatforms = [];
        foreach ($typeMap as $keyword => $platforms) {
            if (str_contains($type, $keyword)) {
                $typePlatforms = $platforms;
                break;
            }
        }

        // Yelp is always added — universal coverage
        if (!in_array('yelp', $typePlatforms, true)) {
            $typePlatforms[] = 'yelp';
        }

        return $typePlatforms ?: ['yelp'];
    }

    /**
     * Map platform names to their domain for use in the AI prompt.
     */
    private function siteFilterForPlatform(string $platform): ?string
    {
        return match ($platform) {
            'fresha'       => 'fresha.com',
            'treatwell'    => 'treatwell.co.uk',
            'booksy'       => 'booksy.com',
            'yelp'         => 'yelp.com',
            'checkatrade'  => 'checkatrade.com',
            'rated_people' => 'ratedpeople.com',
            'trustatrader' => 'trustatrader.com',
            'bark'         => 'bark.com',
            default        => null,
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tier 1 — scrape own website
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch a URL, strip to plain text, and send to Claude Haiku for extraction.
     *
     * @return array<int, array{id:string, name:string, description:string|null, price:string|null, show_price:bool, featured:bool}>
     */
    private function extractFromUrl(string $url, string $context): array
    {
        try {
            $response = Http::timeout(15)
                ->withUserAgent('Mozilla/5.0 (compatible; 321Sites/1.0)')
                ->get($url);

            if (!$response->successful()) {
                return [];
            }

            $text = $this->htmlToText($response->body());

            if (strlen($text) < 50) {
                return [];
            }

            // Truncate to stay within a reasonable token budget
            $text = Str::limit($text, 6000, '');

            return $this->aiExtractServices($text, $context);

        } catch (\Throwable $e) {
            Log::warning("FetchBusinessServices ({$context}): HTTP fetch failed for {$url}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Send plain text to Claude Haiku and ask it to extract a services list.
     *
     * @return array<int, array{id:string, name:string, description:string|null, price:string|null, show_price:bool, featured:bool}>
     */
    private function aiExtractServices(string $text, string $context): array
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (empty($apiKey)) {
            return [];
        }

        $prompt = <<<PROMPT
Extract the list of services or products (and their prices where shown) from the following business page text.

Return a JSON array of objects. Each object must have:
- "name": string — the service or product name (required)
- "description": string or null — a brief description if one is provided
- "price": string or null — the price exactly as shown (e.g. "£18", "From £45", "£20–£35"), or null if not listed

Rules:
- Include only actual services/products the business offers — not navigation items, testimonials, or generic headings.
- Maximum 20 items.
- If no services are found, return an empty array: []
- Return ONLY the JSON array — no explanation, no markdown, no code block.

Page text:
{$text}
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1500,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $response->throw();

            $raw = trim($response->json('content.0.text') ?? '');
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);

            $parsed = json_decode($raw, true);

            if (!is_array($parsed) || empty($parsed)) {
                return [];
            }

            return array_values(array_map(fn($item) => [
                'id'          => Str::uuid()->toString(),
                'name'        => (string) ($item['name'] ?? ''),
                'description' => isset($item['description']) && $item['description'] !== ''
                                    ? (string) $item['description'] : null,
                'price'       => isset($item['price']) && $item['price'] !== ''
                                    ? (string) $item['price'] : null,
                'show_price'  => !empty($item['price']),
                'featured'    => false,
            ], array_filter($parsed, fn($i) => !empty($i['name']))));

        } catch (\Throwable $e) {
            Log::warning("FetchBusinessServices ({$context}): AI extraction failed: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tier 3 — AI generation
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ask Claude to suggest typical services for this type of business.
     *
     * @return array<int, array{id:string, name:string, description:string|null, price:string|null, show_price:bool, featured:bool}>
     */
    private function generateTypicalServices(string $businessType, ?string $city): array
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (empty($apiKey)) {
            return [];
        }

        $region = $city ? "in {$city}, UK" : 'in the UK';

        $prompt = <<<PROMPT
List 6 typical services or products offered by a "{$businessType}" business {$region}.

Return a JSON array of objects. Each object must have:
- "name": string — a common service name for this type of business
- "description": string or null — one short sentence describing the service, or null
- "price": string or null — a realistic typical price range (e.g. "From £X", "£X–£Y"), or null if pricing varies too much

Return ONLY the JSON array — no explanation, no markdown, no code block.
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 800,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $response->throw();

            $raw = trim($response->json('content.0.text') ?? '');
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);

            $parsed = json_decode($raw, true);

            if (!is_array($parsed) || empty($parsed)) {
                return [];
            }

            return array_values(array_map(fn($item) => [
                'id'          => Str::uuid()->toString(),
                'name'        => (string) ($item['name'] ?? ''),
                'description' => isset($item['description']) && $item['description'] !== ''
                                    ? (string) $item['description'] : null,
                'price'       => isset($item['price']) && $item['price'] !== ''
                                    ? (string) $item['price'] : null,
                'show_price'  => !empty($item['price']),
                'featured'    => false,
            ], array_filter($parsed, fn($i) => !empty($i['name']))));

        } catch (\Throwable $e) {
            Log::warning('FetchBusinessServices (Tier 3 AI generation): ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Persist the extracted services to TemporarySite.data.
     */
    private function persist(array $services, string $source): void
    {
        $services = $this->sanitizeServices($services);

        if (empty($services)) {
            return;
        }

        $this->site->services = array_values($services);
        $this->site->save();

        Log::info('FetchBusinessServices: saved ' . count($services) . " services from \"{$source}\" for site {$this->site->id}.");
    }

    /**
     * Strip unusably short descriptions before they reach the setup UI.
     *
     * @param  array<int, array{id:string, name:string, description:string|null, price:string|null, show_price:bool, featured:bool}>  $services
     * @return array<int, array{id:string, name:string, description:string|null, price:string|null, show_price:bool, featured:bool}>
     */
    private function sanitizeServices(array $services): array
    {
        return array_values(array_map(function (array $service) {
            $description = isset($service['description']) ? trim((string) $service['description']) : null;

            if ($description !== null && mb_strlen($description) < 5) {
                $description = null;
            }

            $service['description'] = $description ?: null;

            // Strip leading currency symbols so the renderer doesn't double-prefix them
            if (!empty($service['price'])) {
                $service['price'] = preg_replace('/^[\s£$€¥₹₩฿₺₨]+/', '', (string) $service['price']) ?: null;
            }

            return $service;
        }, $services));
    }

    /**
     * Strip HTML tags and collapse whitespace to produce clean plain text
     * suitable for an AI prompt.
     */
    private function htmlToText(string $html): string
    {
        $html = preg_replace('/<(script|style|nav|footer|header|noscript)[^>]*>.*?<\/\1>/si', '', $html);
        $html = preg_replace('/<(br|p|div|li|tr|h[1-6])[^>]*>/i', "\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

}
