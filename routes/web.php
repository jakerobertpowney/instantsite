<?php

use App\Http\Controllers\PreviewController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::domain('{domain}.' . env('APP_DOMAIN'))->group(function () {
   Route::get('/', [SiteController::class, 'index'])->name('site.index');
});

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('discover/{id}', [PreviewController::class, 'discover'])->name('preview.discover');
Route::get('setup/{id}', [PreviewController::class, 'setup'])->name('preview.setup');
Route::post('setup/{id}', [PreviewController::class, 'store'])->name('preview.store');
Route::get('preview/{id}', [PreviewController::class, 'show'])->name('preview.show');
Route::post('setup/{id}/complete', [PreviewController::class, 'complete'])->name('preview.complete');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
