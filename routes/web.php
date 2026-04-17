<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\StockPhotoController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::domain('{domain}.' . env('APP_DOMAIN'))->group(function () {
   Route::get('/', [SiteController::class, 'index'])->name('site.index');
   Route::post('/contact', [SiteController::class, 'contact'])->name('site.contact');
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('discover/{id}', [PreviewController::class, 'discover'])->name('preview.discover');
Route::get('setup/{id}', [PreviewController::class, 'setup'])->name('preview.setup');
Route::post('setup/{id}', [PreviewController::class, 'store'])->name('preview.store');
Route::get('preview/{id}', [PreviewController::class, 'show'])->name('preview.show');
Route::post('setup/{id}/complete', [PreviewController::class, 'complete'])->name('preview.complete');

Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/stock-photos', StockPhotoController::class)->name('dashboard.stock-photos');
    Route::post('dashboard/site', [DashboardController::class, 'site'])->name('dashboard.site');
    Route::post('dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('dashboard/components', [DashboardController::class, 'components'])->name('dashboard.components');

    // Billing
    Route::get('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
