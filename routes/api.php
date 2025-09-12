<?php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::post('search/places', [SearchController::class, 'places'])->name('search.places');
Route::post('search/discover', [SearchController::class, 'discover'])->name('search.discover');

Route::get('search/discover/{batchId}/poll', [SearchController::class, 'poll'])->name('search.discover.poll');
