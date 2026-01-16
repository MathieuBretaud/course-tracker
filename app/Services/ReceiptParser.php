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
        // Cherche le total avec le symbole € (ex: "26 , 28 €")
        if (preg_match('/(\d+)\s*[,.\s]+\s*(\d+)\s*€/i', $text, $matches)) {
            return (float) ($matches[1].'.'.$matches[2]);
        }

        return null;
    }

    protected function extractArticles(string $text): array
    {
        $articles = [];
        $lines = explode("\n", $text);
        $inSelfscan = false;

        foreach ($lines as $line) {
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
                $article = $this->parseArticleLine($line);
                if ($article) {
                    $articles[] = $article;
                }
            }
        }

        return $articles;
    }

    protected function parseArticleLine(string $line): ?array
    {
        // Enlever le * au début
        $line = ltrim($line, '*');

        // Séparer par tabulation
        $parts = explode("\t", $line);

        if (count($parts) < 2) {
            return null;
        }

        $name = trim($parts[0]);
        $quantity = 1;
        $price = null;

        // Le dernier élément est toujours le prix total
        $lastPart = end($parts);
        if (preg_match('/(\d+)[,.](\d+)/', $lastPart, $matches)) {
            $price = (float) ($matches[1].'.'.$matches[2]);
        }

        // Chercher la quantité dans le format "2*2,48" ou "X30" dans le nom
        if (count($parts) >= 2) {
            $secondPart = $parts[1] ?? '';
            if (preg_match('/^(\d+)\*/', $secondPart, $matches)) {
                $quantity = (int) $matches[1];
            }
        }

        // Chercher quantité dans le nom (ex: "OEUFS FRAIS X30")
        if (preg_match('/\s+X(\d+)$/i', $name, $matches)) {
            $quantity = (int) $matches[1];
            $name = preg_replace('/\s+X\d+$/i', '', $name);
        }

        return [
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
        ];
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
