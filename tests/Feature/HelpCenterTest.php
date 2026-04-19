<?php

use Inertia\Testing\AssertableInertia as Assert;

test('help centre hub can be viewed', function () {
    $response = $this->get(route('help'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Help')
            ->has('categories')
            ->has('articles')
            ->has('featuredArticles')
            ->has('faqs')
        );
});

test('help article can be viewed', function () {
    $response = $this->get(route('help.article', ['slug' => 'connect-a-custom-domain']));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('HelpArticle')
            ->where('article.slug', 'connect-a-custom-domain')
            ->where('article.title', 'Connect your own custom domain')
            ->has('relatedArticles')
        );
});

test('unknown help article returns not found', function () {
    $this->get(route('help.article', ['slug' => 'missing-article']))
        ->assertNotFound();
});
