<?php

use App\Models\Site;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('sitemap lists only published indexable sites', function () {
    $owner = User::factory()->create();

    Site::create([
        'user_id' => $owner->id,
        'places_id' => 'published-subdomain',
        'domain_type' => 'subdomain',
        'subdomain' => 'live-site',
        'data' => [],
    ]);

    Site::create([
        'user_id' => $owner->id,
        'places_id' => 'published-custom',
        'domain_type' => 'custom',
        'custom_domain' => 'example-business.co.uk',
        'domain_verified' => true,
        'data' => [],
    ]);

    Site::create([
        'user_id' => $owner->id,
        'places_id' => 'draft-site',
        'domain_type' => 'draft',
        'subdomain' => 'draft-site',
        'data' => [],
    ]);

    Site::create([
        'user_id' => $owner->id,
        'places_id' => 'hidden-site',
        'domain_type' => 'subdomain',
        'subdomain' => 'hidden-site',
        'data' => ['allow_indexing' => false],
    ]);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    $response->assertSee('https://live-site.instantsite.test', false);
    $response->assertSee('https://example-business.co.uk', false);
    $response->assertDontSee('https://draft-site.instantsite.test', false);
    $response->assertDontSee('https://hidden-site.instantsite.test', false);
});

test('legal pages can be viewed', function () {
    $this->get(route('terms'))->assertOk();
    $this->get(route('privacy'))->assertOk();
});
