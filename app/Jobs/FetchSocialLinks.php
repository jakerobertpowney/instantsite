<?php

namespace App\Jobs;

use App\Models\TemporarySite;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchSocialLinks implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Maximum seconds to wait for the website response.
     */
    public int $timeout = 15;

    /**
     * Do not retry on failure — social scraping is best-effort.
     */
    public int $tries = 1;

    public function __construct(
        private readonly TemporarySite $site,
        private readonly string $websiteUri
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withUserAgent('Mozilla/5.0 (compatible; 321Sites/1.0)')
                ->get($this->websiteUri);

            if (!$response->successful()) {
                return;
            }

            $html = $response->body();

            $socials      = $this->extractSocials($html);
            $email        = $this->extractEmail($html);
            $bookingLinks = $this->extractBookingLinks($html);

            if (empty($socials) && !$email && empty($bookingLinks)) {
                return;
            }

            $site = $this->site->fresh();
            if (! $site) {
                return;
            }

            if (!empty($socials)) {
                $site->socials = array_merge($site->socials ?? [], $socials);
            }

            if ($email && !$site->contact_email) {
                $site->contact_email = $email;
            }

            if (!empty($bookingLinks)) {
                $existingLinks = $site->quick_links ?? [];
                foreach ($bookingLinks as $bookingLink) {
                    $existingLinks[] = [
                        'label' => $bookingLink['label'],
                        'link'  => $bookingLink['url'],
                    ];
                }
                $site->quick_links = $existingLinks;
            }

            $site->save();

        } catch (\Throwable $e) {
            // Best-effort — log and move on without failing the batch
            Log::warning('FetchSocialLinks failed for ' . $this->websiteUri . ': ' . $e->getMessage());
        }
    }

    /**
     * Extract social media profile URLs from raw HTML.
     * Returns an array keyed by platform name.
     */
    private function extractSocials(string $html): array
    {
        $patterns = [
            'instagram' => '#https?://(www\.)?instagram\.com/[a-zA-Z0-9_.]+/?#i',
            'facebook'  => '#https?://(www\.)?facebook\.com/[a-zA-Z0-9_./-]+/?#i',
            'x'         => '#https?://(www\.)?(?:twitter|x)\.com/[a-zA-Z0-9_]+/?#i',
            'linkedin'  => '#https?://(www\.)?linkedin\.com/(company|in)/[a-zA-Z0-9_-]+/?#i',
        ];

        // Noise URLs we should ignore (platform's own marketing pages, share widgets, etc.)
        $blocklist = [
            'instagram.com/p/',
            'instagram.com/reel/',
            'facebook.com/sharer',
            'facebook.com/share',
            'facebook.com/dialog',
            'twitter.com/share',
            'twitter.com/intent',
            'x.com/intent',
            'linkedin.com/share',
            'linkedin.com/shareArticle',
        ];

        $found = [];

        foreach ($patterns as $platform => $pattern) {
            if (preg_match_all($pattern, $html, $matches)) {
                foreach ($matches[0] as $url) {
                    // Skip noise URLs
                    $blocked = false;
                    foreach ($blocklist as $noise) {
                        if (stripos($url, $noise) !== false) {
                            $blocked = true;
                            break;
                        }
                    }

                    if (!$blocked) {
                        // Normalise: trim trailing slash, lowercase domain
                        $url = rtrim($url, '/');
                        $found[$platform] = $url;
                        break; // Take the first valid match per platform
                    }
                }
            }
        }

        return $found;
    }

    /**
     * Extract the first contact email address from mailto: links in the HTML.
     */
    private function extractEmail(string $html): ?string
    {
        // Primary: look for mailto: links — these are intentionally listed contact emails
        if (preg_match('#mailto:([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})#i', $html, $matches)) {
            $email = strtolower($matches[1]);

            // Skip obvious no-reply / info noise addresses that aren't useful as a contact target
            $blocklist = ['noreply', 'no-reply', 'donotreply', 'do-not-reply', 'webmaster', 'postmaster'];
            foreach ($blocklist as $noise) {
                if (str_starts_with($email, $noise . '@')) {
                    return null;
                }
            }

            return $email;
        }

        return null;
    }

    /**
     * Detect booking / appointment platform links in the HTML.
     * Returns an array of [ 'platform' => string, 'label' => string, 'url' => string ].
     */
    private function extractBookingLinks(string $html): array
    {
        $platforms = [
            'calendly'   => [
                'pattern' => '#https?://(www\.)?calendly\.com/[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)?/?#i',
                'label'   => 'Book a visit',
            ],
            'booksy'     => [
                'pattern' => '#https?://(www\.)?booksy\.com/[^\s"\'<>]+#i',
                'label'   => 'Book an appointment',
            ],
            'fresha'     => [
                'pattern' => '#https?://(www\.)?fresha\.com/[^\s"\'<>]+#i',
                'label'   => 'Book an appointment',
            ],
            'treatwell'  => [
                'pattern' => '#https?://(www\.)?treatwell\.(co\.uk|com)/[^\s"\'<>]+#i',
                'label'   => 'Book a treatment',
            ],
            'vagaro'     => [
                'pattern' => '#https?://(www\.)?vagaro\.com/[a-zA-Z0-9_-]+/?#i',
                'label'   => 'Book an appointment',
            ],
            'acuity'     => [
                'pattern' => '#https?://[a-zA-Z0-9_-]+\.acuityscheduling\.com/[^\s"\'<>]*#i',
                'label'   => 'Book an appointment',
            ],
            'simplybook' => [
                'pattern' => '#https?://[a-zA-Z0-9_-]+\.simplybook\.(me|it)/[^\s"\'<>]*#i',
                'label'   => 'Book an appointment',
            ],
            'opentable'  => [
                'pattern' => '#https?://(www\.)?opentable\.(com|co\.uk)/[^\s"\'<>]+#i',
                'label'   => 'Make a reservation',
            ],
        ];

        $found = [];

        foreach ($platforms as $platform => $config) {
            if (preg_match($config['pattern'], $html, $matches)) {
                $found[] = [
                    'platform' => $platform,
                    'label'    => $config['label'],
                    'url'      => rtrim($matches[0], '/'),
                ];
            }
        }

        return $found;
    }
}
