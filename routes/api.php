<?php

use App\Http\Controllers\Api\GenerateDescriptionController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::post('search/places', [SearchController::class, 'places'])
    ->middleware('throttle:20,1')
    ->name('search.places');
Route::post('search/discover', [SearchController::class, 'discover'])->name('search.discover');

Route::get('search/discover/{batchId}/poll', [SearchController::class, 'poll'])->name('search.discover.poll');

Route::post('setup/{id}/generate-description', GenerateDescriptionController::class)->name('setup.generate-description');
