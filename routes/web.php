<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DomainProviderController;
use App\Http\Controllers\Admin\GenerateDashboardDescriptionController;
use App\Http\Controllers\Admin\SubmissionsController;
use App\Http\Controllers\Api\StockPhotoController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\SiteController;
use App\Models\Site;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::domain('{domain}.' . env('APP_DOMAIN'))->group(function () {
   Route::get('/', [SiteController::class, 'index'])->name('site.index');
   Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('site.sitemap');
   Route::post('/contact', [SiteController::class, 'contact'])->name('site.contact');
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// ─── Demo contact form sink — returns success so the demo form shows "sent" ───
// The demo route renders site/Index with :preview="false" on the bottom Contact
// component, so the form POSTs to /contact on the main domain. This route
// returns 200 JSON so the component shows the success state instead of an error.
Route::post('contact', function () {
    return response()->json(['ok' => true]);
})->name('demo.contact');

// ─── Demo photo proxy — redirects /demo-photo/{id} → Pexels CDN ──────────────
// The Gallery component prepends '/' to every path, so demo image paths must be
// root-relative. This redirect route makes that work for external stock images.
Route::get('demo-photo/{id}', function (string $id) {
    abort_unless(ctype_digit($id), 404);
    return redirect()->away(
        "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1"
    );
})->where('id', '[0-9]+')->name('demo.photo');

// ─── Demo preview (used by the marketing hero iframe) ─────────────────────────
Route::get('demo', function () {
    $data = [
        'displayName'              => ['text' => "Dave's Painting & Decorating"],
        'primaryTypeDisplayName'   => ['text' => 'Painter & Decorator'],
        'formattedAddress'         => '42 Ancoats Street, Manchester, M4 5AB',
        'addressComponents'        => [
            ['longText' => 'Manchester', 'shortText' => 'Manchester', 'types' => ['locality', 'political']],
            ['longText' => 'England',    'shortText' => 'England',    'types' => ['administrative_area_level_1', 'political']],
            ['longText' => 'United Kingdom', 'shortText' => 'GB',    'types' => ['country', 'political']],
        ],
        'editorialSummary' => ['text' => "Family-run painting & decorating service covering Manchester and the surrounding areas. Over 20 years of experience transforming homes and commercial spaces. Fully insured, fully certified, and always tidy. Free no-obligation quotes available seven days a week."],
        'nationalPhoneNumber'      => '0161 234 5678',
        'internationalPhoneNumber' => '+44 161 234 5678',
        'rating'                   => 4.9,
        'userRatingCount'          => 47,
        'regularOpeningHours'      => [
            'periods' => [
                ['open' => ['day' => 1, 'hour' => 8, 'minute' => 0], 'close' => ['day' => 1, 'hour' => 18, 'minute' => 0]],
                ['open' => ['day' => 2, 'hour' => 8, 'minute' => 0], 'close' => ['day' => 2, 'hour' => 18, 'minute' => 0]],
                ['open' => ['day' => 3, 'hour' => 8, 'minute' => 0], 'close' => ['day' => 3, 'hour' => 18, 'minute' => 0]],
                ['open' => ['day' => 4, 'hour' => 8, 'minute' => 0], 'close' => ['day' => 4, 'hour' => 18, 'minute' => 0]],
                ['open' => ['day' => 5, 'hour' => 8, 'minute' => 0], 'close' => ['day' => 5, 'hour' => 17, 'minute' => 0]],
            ],
            'weekdayDescriptions' => [
                'Monday: 8:00 AM – 6:00 PM',
                'Tuesday: 8:00 AM – 6:00 PM',
                'Wednesday: 8:00 AM – 6:00 PM',
                'Thursday: 8:00 AM – 6:00 PM',
                'Friday: 8:00 AM – 5:00 PM',
                'Saturday: Closed',
                'Sunday: Closed',
            ],
        ],
        'whatsapp_number' => '447961234567',
        'contact'         => 'dave@davespainting.co.uk',
        'images'  => [
            'demo-photo/5691677',
            'demo-photo/6474471',
            'demo-photo/5798984',
            'demo-photo/8481708',
            'demo-photo/5317151',
            'demo-photo/5799135',
            'demo-photo/34046208',
        ],
        'reviews' => [
            [
                'relativePublishTimeDescription' => '2 months ago',
                'rating' => 5,
                'text'   => ['text' => "Dave and his team did a fantastic job on our living room and hallway. Neat, tidy, and finished on time. Would highly recommend to anyone looking for a reliable decorator.", 'languageCode' => 'en'],
                'authorAttribution' => ['displayName' => 'Sarah Mitchell', 'uri' => '', 'photoUri' => ''],
                'publishTime' => '2024-02-15T10:00:00Z',
            ],
            [
                'relativePublishTimeDescription' => '4 months ago',
                'rating' => 5,
                'text'   => ['text' => "Excellent service from start to finish. Very professional and the results speak for themselves. Our kitchen looks absolutely incredible.", 'languageCode' => 'en'],
                'authorAttribution' => ['displayName' => 'James Thornton', 'uri' => '', 'photoUri' => ''],
                'publishTime' => '2023-12-10T14:00:00Z',
            ],
            [
                'relativePublishTimeDescription' => '6 months ago',
                'rating' => 5,
                'text'   => ['text' => "Used Dave's for a full interior repaint of our 3-bed semi. Great attention to detail and very reasonable prices. Will definitely be using them again.", 'languageCode' => 'en'],
                'authorAttribution' => ['displayName' => 'Lisa Patel', 'uri' => '', 'photoUri' => ''],
                'publishTime' => '2023-10-05T09:00:00Z',
            ],
        ],
        'quickLinks' => [
            ['label' => 'Book Appointment', 'link' => '#'],
        ],
        'services' => [
            [
                'id'          => 'svc-1',
                'name'        => 'Interior Painting',
                'description' => 'Full interior repaints including walls, ceilings, skirting boards, and woodwork. All surfaces prepped and primed for a flawless, long-lasting finish.',
                'price'       => null,
                'currency'    => 'GBP',
                'show_price'  => false,
                'featured'    => true,
            ],
            [
                'id'          => 'svc-2',
                'name'        => 'Exterior Painting',
                'description' => 'Render, masonry, fencing, gates, and fascias. Weather-resistant paints applied to protect your property and boost kerb appeal.',
                'price'       => null,
                'currency'    => 'GBP',
                'show_price'  => false,
                'featured'    => true,
            ],
            [
                'id'          => 'svc-3',
                'name'        => 'Wallpaper Hanging',
                'description' => null,
                'price'       => null,
                'currency'    => 'GBP',
                'show_price'  => false,
                'featured'    => false,
            ],
            [
                'id'          => 'svc-4',
                'name'        => 'Coving & Plasterwork',
                'description' => null,
                'price'       => null,
                'currency'    => 'GBP',
                'show_price'  => false,
                'featured'    => false,
            ],
            [
                'id'          => 'svc-5',
                'name'        => 'Colour Consultation',
                'description' => null,
                'price'       => null,
                'currency'    => 'GBP',
                'show_price'  => false,
                'featured'    => false,
            ],
        ],
        'components' => [
            'header'        => ['enabled' => true],
            'description'   => ['enabled' => true],
            'gallery'       => ['enabled' => true],
            'quick_actions' => ['enabled' => true],
            'reviews'       => ['enabled' => true],
            'contact'       => ['enabled' => true],
            'contact_form'  => ['enabled' => true],
            'services'      => ['enabled' => true],
        ],
        'overrides' => [
            'description'   => '',
            'logo_path'     => '',
            'contact_email' => 'dave@davespainting.co.uk',
            'header_bg'     => [
                'type'       => 'stock',
                'value'      => 'https://images.pexels.com/photos/6474471/pexels-photo-6474471.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
                'credit'     => 'Tima Miroshnichenko',
                'credit_url' => 'https://www.pexels.com/photo/man-in-white-dress-shirt-painting-wall-6474471/',
            ],
        ],
    ];

    return Inertia::render('site/Index', [
        'data'            => $data,
        'isPremium'       => true,
        'metaTitle'       => "Dave's Painting & Decorating",
        'metaDescription' => "Family-run painting & decorating in Manchester.",
        'siteUrl'         => config('app.url') . '/demo',
        'canonicalUrl'    => config('app.url') . '/demo',
        'sitemapUrl'      => null,
        'isOwner'         => false,
        'dashboardUrl'    => config('app.url') . '/dashboard',
    ]);
})->name('demo');

Route::get('terms', function () {
    return Inertia::render('Terms');
})->name('terms');

Route::get('privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

Route::get('help', [HelpController::class, 'index'])->name('help');
Route::get('help/{slug}', [HelpController::class, 'show'])->name('help.article');

Route::get('sitemap.xml', function () {
    $appDomain = env('APP_DOMAIN', parse_url(config('app.url'), PHP_URL_HOST));

    $urls = Site::query()
        ->where('domain_type', '!=', 'draft')
        ->get(['subdomain', 'custom_domain', 'domain_type', 'domain_verified', 'updated_at', 'data'])
        ->filter(function (Site $site) {
            if (data_get($site->data, 'allow_indexing', true) === false) {
                return false;
            }

            if ($site->domain_type === 'custom') {
                return filled($site->custom_domain) && $site->domain_verified;
            }

            return filled($site->subdomain);
        })
        ->map(function (Site $site) use ($appDomain) {
            $url = $site->domain_type === 'custom'
                ? 'https://' . $site->custom_domain
                : 'https://' . $site->subdomain . '.' . $appDomain;

            return [
                'loc' => $url,
                'lastmod' => $site->updated_at?->toAtomString() ?? now()->toAtomString(),
            ];
        });

    $entries = $urls->map(function (array $entry) {
        $loc = htmlspecialchars($entry['loc'], ENT_XML1);
        $lastmod = htmlspecialchars($entry['lastmod'], ENT_XML1);

        return <<<XML
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
    </url>
XML;
    })->implode("\n");

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$entries}
</urlset>
XML;

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
})->name('sitemap');

Route::get('discover/{id}', [PreviewController::class, 'discover'])->name('preview.discover');
Route::get('setup/{id}', [PreviewController::class, 'setup'])->name('preview.setup');
Route::post('setup/{id}', [PreviewController::class, 'store'])->name('preview.store');
Route::get('preview/{id}', [PreviewController::class, 'show'])->name('preview.show');
Route::post('setup/{id}/complete', [PreviewController::class, 'complete'])->name('preview.complete');

Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('submissions', [SubmissionsController::class, 'index'])->name('submissions');
    Route::post('submissions/{submission}/read', [SubmissionsController::class, 'markRead'])->name('submissions.read');
    Route::get('dashboard/stock-photos', StockPhotoController::class)->name('dashboard.stock-photos');
    Route::post('dashboard/site', [DashboardController::class, 'site'])->name('dashboard.site');
    Route::post('dashboard/site/verify-domain', [DashboardController::class, 'verifyDomain'])->name('dashboard.site.verify-domain');
    Route::post('dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('dashboard/components', [DashboardController::class, 'components'])->name('dashboard.components');
    Route::post('dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
    Route::post('dashboard/generate-description', GenerateDashboardDescriptionController::class)->name('dashboard.generate-description');

    // Domain Connect (open standard — GoDaddy, Cloudflare, IONOS, NameSilo, …)
    Route::post('dashboard/domain/connect/probe',        [DomainProviderController::class, 'probeAndRedirectDomainConnect'])->name('domain.connect.probe');
    Route::get( 'dashboard/domain/connect/callback',     [DomainProviderController::class, 'handleDomainConnectCallback'])->name('domain.connect.callback');

    // Cloudflare direct OAuth (fallback for users not on Domain Connect providers)
    Route::get( 'dashboard/domain/cloudflare/redirect',  [DomainProviderController::class, 'redirectToCloudflare'])->name('domain.cloudflare.redirect');
    Route::get( 'dashboard/domain/cloudflare/callback',  [DomainProviderController::class, 'handleCloudflareCallback'])->name('domain.cloudflare.callback');

    // GoDaddy API-key fallback (for accounts not yet migrated to Domain Connect)
    Route::post('dashboard/domain/godaddy/connect',      [DomainProviderController::class, 'connectGodaddy'])->name('domain.godaddy.connect');

    Route::post('dashboard/domain/disconnect',           [DomainProviderController::class, 'disconnect'])->name('domain.disconnect');

    // Billing
    Route::get('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// ─── Mail previews (local dev only) ───────────────────────────────────────────
if (app()->isLocal()) {
    Route::get('/__mail/contact-form', function () {
        return new \App\Mail\ContactFormMail(
            senderEmail: 'jane@example.com',
            mailSubject: 'Appointment enquiry',
            messageBody: "Hi there,\n\nI'd love to book an appointment for next Tuesday if you have availability. Please let me know what times work best.\n\nThanks,\nJane",
            businessName: 'Acme Hair Studio',
            preferredContactTime: 'Tuesday 10 am – 12 pm',
        );
    })->name('mail.preview.contact-form');

    Route::get('/__mail/verify-email', function () {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Confirm your 321Sites email address')
            ->greeting('Welcome to 321Sites')
            ->line('Confirm your email address to publish your site and start receiving customer enquiries.')
            ->action('Confirm email address', url('/'))
            ->line('If you did not create an account, you can safely ignore this email.');
    })->name('mail.preview.verify-email');

    Route::get('/__mail/reset-password', function () {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Reset your 321Sites password')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset password', url('/'))
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    })->name('mail.preview.reset-password');
}
