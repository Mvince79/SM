<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Contracts\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::published()->latest('published_at')->paginate(12);

        return view('articles.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('articles.show', compact('article', 'related'));
    }
}