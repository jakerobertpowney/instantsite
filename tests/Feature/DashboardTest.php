<?php

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-dashboard',
        'domain_type' => 'subdomain',
        'subdomain' => 'dashboard-site',
        'data' => [],
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard component logo uploads are stored on the public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-123',
        'domain_type' => 'subdomain',
        'subdomain' => 'test-site',
        'data' => [],
    ]);

    $response = $this
        ->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('dashboard.components'), [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

    $response->assertRedirect(route('dashboard'));

    Storage::disk('public')->assertExists("images/{$site->places_id}/logo.png");

    expect($site->fresh()->data['overrides']['logo_path'])
        ->toBe("/storage/images/{$site->places_id}/logo.png");
});

test('seo and visibility settings update meta fields and indexing preferences', function () {
    $user = User::factory()->create();
    $site = Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-seo-settings',
        'domain_type' => 'subdomain',
        'subdomain' => 'seo-settings-site',
        'meta_title' => 'Old title',
        'meta_description' => 'Old description',
        'data' => [
            'allow_indexing' => true,
        ],
    ]);

    $response = $this
        ->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('dashboard.settings'), [
            'meta_title' => 'Painter and Decorator in Stockport',
            'meta_description' => 'Professional painting and decorating in Stockport. Free quotes and reliable local service.',
            'allow_indexing' => false,
        ]);

    $response->assertRedirect(route('dashboard'));

    expect($site->fresh()->meta_title)->toBe('Painter and Decorator in Stockport')
        ->and($site->fresh()->meta_description)->toBe('Professional painting and decorating in Stockport. Free quotes and reliable local service.')
        ->and($site->fresh()->data['allow_indexing'])->toBeFalse();
});
