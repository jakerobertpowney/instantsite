<?php

namespace App\Services;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FakeBusinessDataService
{
    private \Faker\Generator $faker;

    public function __construct()
    {
        $this->faker = Faker::create('en_GB');
        // Use a stable seed so repeated loads of the same places_id look the same
    }

    /**
     * Fill any missing fields in the data array with realistic fake content.
     * All fake fields are marked with a `_is_fake` flag in a separate key for debugging.
     */
    public function fill(array $data, string $seed): array
    {
        // Seed Faker so the same places_id always produces the same fake data
        $this->faker->seed(crc32($seed));

        $name = $data['business_name'] ?? 'This Business';
        $type = $data['business_type'] ?? null;

        if (empty($data['description'])) {
            $data['description'] = $this->generateDescription($name, $type, $data);
        }

        if (empty($data['rating'])) {
            $data['rating'] = round($this->faker->randomFloat(1, 4.1, 5.0), 1);
        }

        if (empty($data['review_count'])) {
            $data['review_count'] = $this->faker->numberBetween(14, 180);
        }

        if (empty($data['reviews'])) {
            $data['reviews'] = $this->fakeReviews($name);
        }

        if (empty($data['opening_hours'])) {
            $data['opening_hours'] = $this->fakeOpeningHours();
        }

        if (empty($data['images'])) {
            $data['images'] = $this->fakeImages($seed);
        }

        if (empty($data['phone'])) {
            $data['phone'] = $this->fakeUkPhone();
        }

        if (empty($data['services'])) {
            $data['services'] = $this->generateServices($name, $type);
        }

        return $data;
    }

    /**
     * Generate a description using Claude Haiku, falling back to generic Faker templates
     * if the API key is missing or the call fails.
     */
    private function generateDescription(string $name, ?string $type, array $data): string
    {
        $apiKey = env('ANTHROPIC_API_KEY');

        if ($apiKey) {
            $description = $this->aiDescription($name, $type, $data, $apiKey);
            if ($description) {
                return $description;
            }
        }

        return $this->fakerDescription($name, $type);
    }

    private function aiDescription(string $name, ?string $type, array $data, string $apiKey): ?string
    {
        $cacheKey = 'claim_ai_desc_' . md5($name . '|' . $type);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $contextParts = array_filter([
            "Business name: {$name}",
            $type                           ? "Business type: {$type}"                  : null,
            !empty($data['formatted_address']) ? "Location: {$data['formatted_address']}" : null,
            !empty($data['phone'])          ? "Phone: {$data['phone']}"                 : null,
            !empty($data['rating'])         ? "Google rating: {$data['rating']} stars"  : null,
        ]);

        $context = implode("\n", $contextParts);

        $prompt = <<<PROMPT
You are helping a small business create their website. Based on the following business information, write a friendly and professional 2–3 sentence description that would appear on their homepage.

{$context}

Requirements:
- Write in third person (e.g. "{$name} is…")
- Sound like it was written by someone who knows the business well — specific and warm, not generic
- Reference the type of services or specialisms that would be typical for this kind of business
- Do not invent specific claims (awards, years in business, etc.) unless provided in the context
- Maximum 60 words
- Return only the description text — no quotes, no preamble

Description:
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(8)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 150,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            $text = trim($response->json('content.0.text') ?? '');

            if ($text) {
                Cache::put($cacheKey, $text, now()->addDays(30));
            }

            return $text ?: null;
        } catch (\Throwable $e) {
            Log::warning('FakeBusinessDataService: AI description failed — ' . $e->getMessage());
            return null;
        }
    }

    private function fakerDescription(string $name, ?string $type): string
    {
        $type = $type ? strtolower($type) : 'local business';

        $templates = [
            "{name} is a trusted local {type} with a reputation for quality and exceptional customer service. We pride ourselves on getting the job done right the first time. Contact us today.",
            "Welcome to {name} — your local {type} specialists. Our experienced team delivers outstanding results on every visit, big or small. We're highly rated by our customers and always happy to help.",
            "{name} combines competitive pricing with a genuine commitment to quality. As a local {type}, we care about every customer and always go the extra mile. Get in touch and see what we can do for you.",
        ];

        $template = $this->faker->randomElement($templates);

        return str_replace(['{name}', '{type}'], [$name, $type], $template);
    }

    private function fakeReviews(string $businessName): array
    {
        $firstNames = ['James', 'Sarah', 'Michael', 'Emma', 'David', 'Olivia', 'Chris', 'Sophie', 'Mark', 'Lucy', 'Tom', 'Hannah', 'Jack', 'Grace', 'Ben', 'Megan', 'Paul', 'Rachel', 'Dan', 'Amy'];
        $lastNames  = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Davies', 'Evans', 'Wilson', 'Taylor', 'Thomas', 'Roberts', 'Walker', 'White', 'Thompson', 'Harris'];

        $templates = [
            "Really impressed with the service from {name}. Professional from start to finish and the results are brilliant. Wouldn't hesitate to recommend.",
            "Used {name} and couldn't be happier. Great communication, turned up when they said they would, and the quality of work is superb.",
            "Excellent service! {name} went above and beyond to make sure we were completely satisfied. Very fair pricing too. Will definitely use again.",
            "Highly recommend {name}. Friendly, reliable and genuinely good at what they do. Made the whole process easy from start to finish.",
            "Fantastic job by the team at {name}. Clean, tidy and professional throughout. The finished result looks amazing — thanks so much!",
        ];

        $reviews = [];
        $usedNames = [];

        for ($i = 0; $i < 3; $i++) {
            $tries = 0;
            do {
                $author = $this->faker->randomElement($firstNames) . ' ' . $this->faker->randomElement($lastNames);
                $tries++;
            } while (in_array($author, $usedNames, true) && $tries < 20);

            $usedNames[] = $author;

            $template = $templates[$i % count($templates)];
            $text      = str_replace('{name}', $businessName, $template);

            $reviews[] = [
                'author' => $author,
                'rating' => $this->faker->randomElement([4, 5, 5, 5]),
                'date'   => now()->subDays($this->faker->numberBetween(10, 400))->format('Y-m-d'),
                'text'   => $text,
            ];
        }

        return $reviews;
    }

    private function fakeOpeningHours(): array
    {
        return [
            ['day' => 'Sunday',    'open' => '10:00', 'close' => '16:00', 'closed' => true],
            ['day' => 'Monday',    'open' => '09:00', 'close' => '17:30', 'closed' => false],
            ['day' => 'Tuesday',   'open' => '09:00', 'close' => '17:30', 'closed' => false],
            ['day' => 'Wednesday', 'open' => '09:00', 'close' => '17:30', 'closed' => false],
            ['day' => 'Thursday',  'open' => '09:00', 'close' => '17:30', 'closed' => false],
            ['day' => 'Friday',    'open' => '09:00', 'close' => '17:00', 'closed' => false],
            ['day' => 'Saturday',  'open' => '09:00', 'close' => '13:00', 'closed' => false],
        ];
    }

    /**
     * Return paths for the claim-photo proxy route.
     * Format: claim-photo/{base64UrlEncodedSeed}-{index}
     * The proxy route will use picsum.photos seeded images.
     */
    private function fakeImages(string $seed): array
    {
        $images = [];
        for ($i = 1; $i <= 6; $i++) {
            // Use picsum with a seeded hash so images are consistent per business
            $hash = substr(md5($seed . $i), 0, 8);
            $images[] = "https://picsum.photos/seed/{$hash}/1200/800";
        }
        return $images;
    }

    private function fakeUkPhone(): string
    {
        $prefixes = ['01', '02', '07'];
        $prefix   = $this->faker->randomElement($prefixes);
        $digits   = str_pad((string) $this->faker->numberBetween(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        return $prefix . substr($digits, 0, 9 - strlen($prefix));
    }

    // ── Services ──────────────────────────────────────────────────────────────

    private function generateServices(string $name, ?string $type): array
    {
        return $this->fakerServices($type);
    }

    /**
     * Hardcoded services with realistic UK prices, grouped by broad business category.
     * Each entry: [name, description, price]
     */
    private function fakerServices(?string $type): array
    {
        $t = strtolower($type ?? '');

        $map = [
            'barber' => [
                ['name' => 'Haircut',               'description' => 'Classic or fade cuts tailored to your style.',                   'price' => 18],
                ['name' => 'Beard Trim & Shape',    'description' => 'Shape, trim and condition for a sharp, clean look.',              'price' => 12],
                ['name' => 'Cut & Beard Combo',     'description' => 'Haircut and beard tidy in one visit.',                           'price' => 28],
                ['name' => 'Hot Towel Shave',        'description' => 'Traditional wet shave with hot towel and straight razor.',       'price' => 22],
                ['name' => 'Kids Cut',               'description' => 'Relaxed, friendly haircuts for children.',                      'price' => 12],
            ],
            'hair|salon' => [
                ['name' => 'Cut & Blow Dry',        'description' => 'Precision cut finished with a professional blow dry.',           'price' => 45],
                ['name' => 'Colour & Highlights',   'description' => 'Full colour, highlights, balayage and toning services.',         'price' => 85],
                ['name' => 'Blow Dry & Finish',     'description' => 'Professional blow dry for a sleek, long-lasting finish.',        'price' => 28],
                ['name' => 'Treatments',             'description' => 'Deep conditioning and repair treatments for all hair types.',    'price' => 25],
                ['name' => 'Updo & Occasion Styling','description' => 'Elegant styling for weddings, events and special occasions.',   'price' => 65],
            ],
            'nail|beauty|aesthet' => [
                ['name' => 'Manicure',              'description' => 'Classic or gel manicure with cuticle care and polish.',           'price' => 30],
                ['name' => 'Pedicure',              'description' => 'Full foot treatment including soak, scrub and polish.',            'price' => 35],
                ['name' => 'Lash Extensions',       'description' => 'Natural to dramatic lash sets applied by a trained technician.',  'price' => 60],
                ['name' => 'Waxing',                'description' => 'Full-body waxing for smooth, long-lasting hair removal.',         'price' => 25],
                ['name' => 'Facials',               'description' => 'Deep-cleanse, exfoliate and hydrate for glowing skin.',           'price' => 50],
            ],
            'plumb' => [
                ['name' => 'Boiler Service',        'description' => 'Annual boiler service to keep your heating running safely.',      'price' => 90],
                ['name' => 'Leak Repair',           'description' => 'Fast, reliable repair of leaks and burst pipes.',                 'price' => 120],
                ['name' => 'Bathroom Installation', 'description' => 'Full bathroom fit-outs from design to completion.',               'price' => 2500],
                ['name' => 'Central Heating',       'description' => 'Installation and repair of central heating systems.',             'price' => 3500],
                ['name' => 'Emergency Call-Out',    'description' => '24/7 emergency plumbing when you need it most.',                  'price' => 150],
            ],
            'electric' => [
                ['name' => 'Consumer Unit Upgrade', 'description' => 'Upgrade your fuse board to the latest safety standards.',        'price' => 600],
                ['name' => 'Socket & Switch Work',  'description' => 'New sockets, switches and USB outlets installed safely.',         'price' => 80],
                ['name' => 'EV Charger Install',    'description' => 'Home EV charger installation by a qualified electrician.',        'price' => 900],
                ['name' => 'Fault Finding',         'description' => 'Diagnose and fix electrical faults quickly and safely.',          'price' => 100],
                ['name' => 'EICR Testing',          'description' => 'Electrical installation condition reports for homes and landlords.','price' => 175],
            ],
        ];

        foreach ($map as $keywords => $services) {
            foreach (explode('|', $keywords) as $kw) {
                if (str_contains($t, $kw)) {
                    return $this->formatFakerServices($services);
                }
            }
        }

        // Generic fallback
        $generic = [
            ['name' => 'Consultation',    'description' => 'Free initial consultation to discuss your requirements.',              'price' => 0],
            ['name' => 'Standard Service','description' => 'Our core service, delivered with care and attention to detail.',        'price' => 75],
            ['name' => 'Premium Package', 'description' => 'A comprehensive package for the best possible results.',               'price' => 150],
            ['name' => 'Maintenance Plan','description' => 'Ongoing support and maintenance to keep things running smoothly.',     'price' => 50],
            ['name' => 'Emergency Cover', 'description' => 'Priority response when you need urgent assistance.',                   'price' => 120],
        ];

        return $this->formatFakerServices($generic);
    }

    private function formatFakerServices(array $services): array
    {
        return array_values(array_map(function ($svc, $i) {
            return [
                'id'          => 'svc-preview-' . ($i + 1),
                'name'        => $svc['name'],
                'description' => $svc['description'],
                'price'       => $svc['price'] ?? null,
                'currency'    => 'GBP',
                'show_price'  => isset($svc['price']),
                'featured'    => $i < 3,
            ];
        }, $services, array_keys($services)));
    }
}
