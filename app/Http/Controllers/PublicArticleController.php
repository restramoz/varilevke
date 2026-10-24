<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Support\WhatsApp;
use Illuminate\View\View;

class PublicArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->published()
            ->with(['catalogItem', 'city'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'callout' => WhatsApp::callout(),
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        $related = Article::query()
            ->published()
            ->where('id', '!=', $article->id)
            ->when($article->catalog_item_id, fn ($q) => $q->where('catalog_item_id', $article->catalog_item_id))
            ->when($article->city_id, fn ($q) => $q->orWhere('city_id', $article->city_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('articles.show', [
            'article' => $article,
            'related' => $related,
            'callout' => WhatsApp::callout(),
            'catalogLinks' => $article->catalog_item_id !== null
                ? CatalogItem::query()->whereIn('id', [$article->catalog_item_id])->get()
                : collect(),
        ]);
    }
}
