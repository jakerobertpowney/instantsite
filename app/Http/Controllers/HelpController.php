<?php

namespace App\Http\Controllers;

use App\Support\HelpCenter;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Help', [
            'categories' => HelpCenter::categoriesWithCounts(),
            'articles' => HelpCenter::articleSummaries(),
            'featuredArticles' => HelpCenter::featuredArticles(),
            'faqs' => HelpCenter::faqs(),
        ]);
    }

    public function show(string $slug): Response
    {
        $article = HelpCenter::findArticle($slug);

        abort_unless($article, 404);

        return Inertia::render('HelpArticle', [
            'article' => $article,
            'relatedArticles' => HelpCenter::relatedArticles($article),
        ]);
    }
}
