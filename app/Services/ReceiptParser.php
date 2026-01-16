<?php

namespace App\Services;

use Carbon\Carbon;

class ReceiptParser
{
    public function parse(string $ocrText): array
    {
        return [
            'date' => $this->extractDate($ocrText),
            'store' => $this->extractStore($ocrText),
            'total' => $this->extractTotal($ocrText),
            'articles' => $this->extractArticles($ocrText),
        ];
    }

    protected function extractDate(string $text): ?Carbon
    {
        if (preg_match('/Le\s+(\d{1,2})\s+(\w+)\s+(\d{4})/i', $text, $matches)) {
            $day = $matches[1];
            $month = $this->parseMonthName($matches[2]);
            $year = $matches[3];

            return Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-{$day}");
        }

        return null;
    }

    protected function extractStore(string $text): ?string
    {
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'AUCHAN') !== false && strlen($line) < 50) {
                return $line;
            }
        }

        return null;
    }

    protected function extractTotal(string $text): ?float
    {
        if (preg_match('/Total\s*[:\s]*(\d+)[,\s]+(\d+)/i', $text, $matches)) {
            return (float) ($matches[1].'.'.$matches[2]);
        }

        return null;
    }

    protected function extractArticles(string $text): array
    {
        $articles = [];
        $lines = explode("\n", $text);
        $inSelfscan = false;
        $articleLines = [];
        $priceLines = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if (stripos($line, 'Début Selfscan') !== false) {
                $inSelfscan = true;

                continue;
            }

            if (stripos($line, 'Fin Selfscan') !== false) {
                $inSelfscan = false;

                continue;
            }

            if ($inSelfscan && str_starts_with($line, '*')) {
                $articleLines[] = $line;
            }

            if (! $inSelfscan && preg_match('/^(\d+)[,\.\s]+(\d+)\s*€?$/', $line, $matches)) {
                $price = (float) ($matches[1].'.'.$matches[2]);
                if ($price > 0 && $price < 1000) {
                    $priceLines[] = $price;
                }
            }
        }

        foreach ($articleLines as $index => $line) {
            $name = substr($line, 1);
            $name = trim(preg_replace('/\s+x\d+$/', '', $name));
            $quantity = $this->extractQuantity($line);

            $price = $priceLines[$index] ?? null;

            $articles[] = [
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        return $articles;
    }

    protected function extractQuantity(string $line): int
    {
        if (preg_match('/x(\d+)$/i', $line, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    protected function parseMonthName(string $monthName): string
    {
        $months = [
            'janvier' => '01',
            'février' => '02',
            'fevrier' => '02',
            'mars' => '03',
            'avril' => '04',
            'mai' => '05',
            'juin' => '06',
            'juillet' => '07',
            'août' => '08',
            'aout' => '08',
            'septembre' => '09',
            'octobre' => '10',
            'novembre' => '11',
            'décembre' => '12',
            'decembre' => '12',
        ];

        return $months[strtolower($monthName)] ?? '01';
    }
}
