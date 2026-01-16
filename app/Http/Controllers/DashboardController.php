<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $receipts = Receipt::withCount('articles')
            ->latest('date')
            ->get();

        // Top 10 articles les plus achetés (somme des quantités)
        $topArticles = Article::select('articles.id', 'articles.name')
            ->join('article_receipt', 'articles.id', '=', 'article_receipt.article_id')
            ->selectRaw('SUM(article_receipt.quantity) as total_quantity')
            ->groupBy('articles.id', 'articles.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Top 10 articles avec la plus grande hausse de prix
        $priceChanges = DB::table('article_receipt')
            ->join('articles', 'articles.id', '=', 'article_receipt.article_id')
            ->join('receipts', 'receipts.id', '=', 'article_receipt.receipt_id')
            ->select('articles.id', 'articles.name', 'article_receipt.price', 'receipts.date')
            ->orderBy('receipts.date')
            ->get()
            ->groupBy('id')
            ->map(function ($prices) {
                if ($prices->count() < 2) {
                    return null;
                }

                $firstPrice = $prices->first()->price;
                $lastPrice = $prices->last()->price;
                $change = $lastPrice - $firstPrice;
                $changePercent = $firstPrice > 0 ? ($change / $firstPrice) * 100 : 0;

                return [
                    'name' => $prices->first()->name,
                    'first_price' => $firstPrice,
                    'last_price' => $lastPrice,
                    'change' => round($change, 2),
                    'change_percent' => round($changePercent, 1),
                ];
            })
            ->filter()
            ->sortByDesc('change_percent')
            ->take(10)
            ->values();

        return Inertia::render('Dashboard', [
            'receipts' => $receipts,
            'topArticles' => $topArticles,
            'priceChanges' => $priceChanges,
        ]);
    }
}
