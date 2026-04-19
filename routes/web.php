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
