<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OcrService
{
    public function extractAmount(string $text): ?string
    {
        $text = preg_replace('/\s+/', ' ', strtoupper($text));

        $totalKeywords = 'TOTAL|GRAND\s*TOTAL|JUMLAH|AMAUN|AMOUNT\s*DUE|AMOUNT\s*PAYABLE|BAYARAN|NET\s*TOTAL|SUB\s*TOTAL|SUBTOTAL';
        preg_match_all(
            '/(?:' . $totalKeywords . ')[\s:]*(?:RM|MYR)?\s*([0-9]{1,6}(?:[,\.][0-9]{3})*(?:[,\.][0-9]{1,2})?)/i',
            $text,
            $totalMatches
        );

        if (!empty($totalMatches[1])) {
            $raw = end($totalMatches[1]);
            $result = $this->normaliseAmount($raw);
            Log::info('[OCR] Amount found via total keyword', ['raw' => $raw, 'normalised' => $result]);
            return $result;
        }

        preg_match_all(
            '/(?:RM|MYR)\s*([0-9]{1,6}(?:[,\.][0-9]{3})*(?:[,\.][0-9]{1,2})?)/i',
            $text,
            $rmMatches
        );

        if (!empty($rmMatches[1])) {
            $amounts  = array_map(fn ($v) => (float) str_replace(',', '', $v), $rmMatches[1]);
            $maxIndex = array_search(max($amounts), $amounts);
            $result   = $this->normaliseAmount($rmMatches[1][$maxIndex]);
            Log::info('[OCR] Amount found via RM/MYR prefix', ['all' => $rmMatches[1], 'chosen' => $result]);
            return $result;
        }

        preg_match_all('/\b([0-9]{1,4}\.[0-9]{2})\b/', $text, $priceMatches);

        if (!empty($priceMatches[1])) {
            $amounts = array_map('floatval', $priceMatches[1]);
            $result  = number_format(max($amounts), 2, '.', '');
            Log::info('[OCR] Amount found via decimal pattern', ['all' => $priceMatches[1], 'chosen' => $result]);
            return $result;
        }

        Log::info('[OCR] No amount detected');
        return null;
    }

    public function extractDate(string $text): ?string
    {
        $text = preg_replace('/\s+/', ' ', $text);

        $months = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|'
                . 'January|February|March|April|May|June|July|August|September|October|November|December';

        $patterns = [
            '/\b(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})\b/',
            '/\b(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})\b/',
            '/\b(\d{1,2})[\s\-]('. $months .')[\s\-,]*(\d{4})\b/i',
            '/\b('. $months .')\s+(\d{1,2}),?\s*(\d{4})\b/i',
        ];

        $patternNames = ['DD/MM/YYYY', 'YYYY/MM/DD', 'DD Mon YYYY', 'Mon DD YYYY'];

        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $text, $m)) {
                Log::info('[OCR] Date pattern matched', ['pattern' => $patternNames[$i], 'match' => $m]);
                try {
                    switch ($i) {
                        case 0:
                            $date = \Carbon\Carbon::createFromFormat('d/m/Y', "{$m[1]}/{$m[2]}/{$m[3]}");
                            break;
                        case 1:
                            $date = \Carbon\Carbon::createFromFormat('Y/m/d', "{$m[1]}/{$m[2]}/{$m[3]}");
                            break;
                        case 2:
                        case 3:
                            $date = \Carbon\Carbon::parse("{$m[1]} {$m[2]} {$m[3]}");
                            break;
                        default:
                            continue 2;
                    }

                    if ($date->isFuture() && $date->diffInDays(now()) > 365) {
                        Log::info('[OCR] Date rejected — too far in future', ['date' => $date->toDateString()]);
                        continue;
                    }
                    if ($date->year < 2000) {
                        Log::info('[OCR] Date rejected — before year 2000', ['date' => $date->toDateString()]);
                        continue;
                    }

                    Log::info('[OCR] Date accepted', ['date' => $date->format('Y-m-d')]);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::warning('[OCR] Date parse failed', ['error' => $e->getMessage(), 'match' => $m]);
                    continue;
                }
            }
        }

        Log::info('[OCR] No date detected');
        return null;
    }

    private function normaliseAmount(string $raw): string
    {
        $clean = preg_replace('/,(?=\d{3}\b)/', '', $raw);
        $clean = str_replace(',', '.', $clean);
        return number_format((float) $clean, 2, '.', '');
    }
}