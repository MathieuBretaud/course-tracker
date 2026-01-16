<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Receipt;
use App\Services\ReceiptParser;
use Codesmiths\LaravelOcrSpace\Enums\Language;
use Codesmiths\LaravelOcrSpace\Enums\OcrSpaceEngine;
use Codesmiths\LaravelOcrSpace\Facades\OcrSpace;
use Codesmiths\LaravelOcrSpace\OcrSpaceOptions;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::with('articles')->latest()->get();

        return inertia('Receipts/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load('articles');

        return inertia('Receipts/Show', [
            'receipt' => $receipt,
        ]);
    }

    public function store(Request $request)
    {
        //        $receipt = new Receipt();
        //        $receipt->save();

        //        if (!$request->hasFile('photo')) {
        //            return redirect()->back()->with('error', 'Aucune photo fournie');
        //        }

        //        $receipt->addMedia($request->file('photo'))->toMediaCollection('ticket');

        $receipt = Receipt::first();
        $this->processOcr($receipt);

        return redirect()->route('receipts.index')->with('success', 'Ticket enregistré avec succès');
    }

    protected function processOcr(Receipt $receipt): void
    {
        $mediaItems = $receipt->getMedia('ticket');

        if ($mediaItems->isEmpty()) {
            return;
        }

        $ocrTexts = [];

        foreach ($mediaItems as $media) {
            $filePath = $media->getPath();

            $ocr = OcrSpace::parseImageFile(
                $filePath,
                OcrSpaceOptions::make()
                    ->language(Language::French)
                    ->ocrEngine(OcrSpaceEngine::Engine2)
                    ->isTable(true)
                    ->scale(true),
            );

            $text = $ocr->getParsedResults()->first()->getParsedText();
            if ($text) {
                // Mettre le texte avec la date en premier (haut du ticket)
                if (preg_match('/Le\s+\d{1,2}\s+\w+\s+\d{4}/i', $text)) {
                    array_unshift($ocrTexts, $text);
                } else {
                    $ocrTexts[] = $text;
                }
            }
        }

        $ocrText = implode("\n", $ocrTexts);

        if (! $ocrText) {
            return;
        }

        $parser = new ReceiptParser;
        $parsedData = $parser->parse($ocrText);

        //        dd([
        //            'articles_count' => count($parsedData['articles']),
        //            'articles' => $parsedData['articles'],
        //            'date' => $parsedData['date'],
        //            'store' => $parsedData['store'],
        //            'total' => $parsedData['total'],
        //        ]);

        $receipt->update([
            'date' => $parsedData['date'],
            'store' => $parsedData['store'],
            'total' => $parsedData['total'],
            'ocr_raw_text' => $ocrText,
        ]);

        foreach ($parsedData['articles'] as $articleData) {
            if ($articleData['price'] === null) {
                continue;
            }

            $article = Article::firstOrCreate(
                ['name' => $articleData['name']],
            );

            $receipt->articles()->attach($article->id, [
                'price' => $articleData['price'],
                'quantity' => $articleData['quantity'],
            ]);
        }
    }
}
