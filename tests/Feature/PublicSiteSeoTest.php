<?php

use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('public subdomain site receives live seo props', function () {
    $user = User::factory()->create();

    Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-public-seo',
        'domain_type' => 'subdomain',
        'subdomain' => 'demo-business',
        'custom_domain' => 'demo-business.co.uk',
        'domain_verified' => true,
        'meta_title' => 'Demo Business - Barber in Stockport',
        'meta_description' => 'Professional barber in Stockport. See opening hours, reviews, photos, and contact details.',
        'data' => [
            'displayName' => ['text' => 'Demo Business'],
            'images' => ['images/demo-business.jpg'],
            'rating' => 4.9,
            'userRatingCount' => 87,
        ],
    ]);

    $response = $this->get('http://demo-business.instantsite.test');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('site/Index')
            ->where('metaTitle', 'Demo Business - Barber in Stockport')
            ->where('metaDescription', 'Professional barber in Stockport. See opening hours, reviews, photos, and contact details.')
            ->where('siteUrl', 'http://demo-business.instantsite.test')
            ->where('canonicalUrl', 'https://demo-business.co.uk')
            ->where('sitemapUrl', 'http://demo-business.instantsite.test/sitemap.xml')
        );
});

test('public site sitemap contains the homepage url', function () {
    $user = User::factory()->create();

    Site::create([
        'user_id' => $user->id,
        'places_id' => 'place-public-sitemap',
        'domain_type' => 'subdomain',
        'subdomain' => 'sitemap-demo',
        'data' => [
            'displayName' => ['text' => 'Sitemap Demo'],
        ],
    ]);

    $response = $this->get('http://sitemap-demo.instantsite.test/sitemap.xml');

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('http://sitemap-demo.instantsite.test', false);
});
