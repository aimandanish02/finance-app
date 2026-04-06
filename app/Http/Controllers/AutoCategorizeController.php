<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutoCategorizeController extends Controller
{
    /**
     * Suggest top 3 LHDN categories for an expense based on title + description.
     * Primary: Gemini API. Fallback: keyword matching.
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
        ]);

        $title       = trim($request->input('title'));
        $description = trim($request->input('description', ''));
        $text        = $title . ($description ? ' ' . $description : '');

        // Load all active categories
        $categories = Category::where('is_active', true)
            ->get(['id', 'name', 'code', 'color', 'deduction_type', 'description'])
            ->keyBy('code');

        // Try Gemini first
        $apiKey = config('services.gemini.api_key');
        if ($apiKey) {
            try {
                $suggestions = $this->suggestViaGemini($text, $categories, $apiKey);
                if (!empty($suggestions)) {
                    return response()->json(['suggestions' => $suggestions, 'source' => 'ai']);
                }
            } catch (\Exception $e) {
                Log::warning('[AUTOCATEGORIZE] Gemini failed, falling back to keywords', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: keyword matching
        $suggestions = $this->suggestViaKeywords($text, $categories);
        return response()->json(['suggestions' => $suggestions, 'source' => 'keywords']);
    }

    // ── Gemini API ────────────────────────────────────────────────────────

    private function suggestViaGemini($text, $categories, string $apiKey): array
    {
        $categoryList = $categories->map(fn ($c) =>
            "- {$c->code}: {$c->name}" . ($c->description ? " ({$c->description})" : '')
        )->implode("\n");

        $prompt = <<<PROMPT
You are a Malaysian personal finance assistant. Given an expense description, suggest the top 3 most relevant LHDN (Inland Revenue Board Malaysia) tax deduction categories from the list below.

Expense: "{$text}"

Available categories:
{$categoryList}

Rules:
- Return ONLY a JSON array of exactly 3 category codes from the list above, ordered by relevance (most relevant first)
- Use ONLY codes from the list — do not invent new ones
- If the expense is clearly non-deductible (food, entertainment, shopping), use NOT_DEDUCTIBLE categories like FOOD, TRANSPORT, SHOPPING, etc.
- Consider Malaysian context: "klinik" = clinic, "farmasi" = pharmacy, "kedai" = shop, "makan" = food, "petrol" = fuel

Example response: ["MEDICAL_SELF", "LIFESTYLE", "FOOD"]

Respond with ONLY the JSON array, no explanation.
PROMPT;

        $response = Http::withoutVerifying()
            ->timeout(8)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 50,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API returned ' . $response->status());
        }

        $raw = $response->json('candidates.0.content.parts.0.text', '');
        $raw = trim(preg_replace('/```json|```/i', '', $raw));

        $codes = json_decode($raw, true);
        if (!is_array($codes)) {
            throw new \Exception('Gemini returned invalid JSON: ' . $raw);
        }

        return $this->codesToSuggestions(array_slice($codes, 0, 3), $categories);
    }

    // ── Keyword matching ──────────────────────────────────────────────────

    private function suggestViaKeywords(string $text, $categories): array
    {
        $text  = strtolower($text);
        $scores = [];

        foreach ($this->keywordMap() as $code => $keywords) {
            if (!$categories->has($code)) continue;
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $score += strlen($keyword); // longer match = higher confidence
                }
            }
            if ($score > 0) {
                $scores[$code] = $score;
            }
        }

        arsort($scores);
        $topCodes = array_slice(array_keys($scores), 0, 3);

        // Pad with sensible defaults if fewer than 3 matches
        $defaults = ['OTHER', 'FOOD', 'TRANSPORT'];
        foreach ($defaults as $d) {
            if (count($topCodes) >= 3) break;
            if (!in_array($d, $topCodes) && $categories->has($d)) {
                $topCodes[] = $d;
            }
        }

        return $this->codesToSuggestions($topCodes, $categories);
    }

    private function codesToSuggestions(array $codes, $categories): array
    {
        return collect($codes)
            ->filter(fn ($code) => $categories->has($code))
            ->map(fn ($code) => [
                'id'             => $categories[$code]->id,
                'name'           => $categories[$code]->name,
                'code'           => $code,
                'color'          => $categories[$code]->color,
                'deduction_type' => $categories[$code]->deduction_type,
            ])
            ->values()
            ->toArray();
    }

    private function keywordMap(): array
    {
        return [
            // Medical
            'MEDICAL_SELF'   => ['hospital', 'clinic', 'klinik', 'doctor', 'doktor', 'pharmacy', 'farmasi',
                                 'ubat', 'medicine', 'dental', 'dentist', 'gigi', 'optik', 'optician',
                                 'lab', 'x-ray', 'xray', 'ct scan', 'mri', 'specialist', 'pakar',
                                 'vaccination', 'vaksin', 'covid', 'pcr', 'fertility', 'ivf', 'iui',
                                 'physiotherapy', 'fisio', 'mental health', 'psychiatrist', 'psychologist'],
            'MEDICAL_PARENT' => ['parents', 'ibu bapa', 'ayah', 'ibu', 'father', 'mother', 'mum', 'dad',
                                 'elderly', 'senior', 'old folks', 'nursing home'],

            // Insurance & savings
            'EPF'            => ['epf', 'kwsp', 'provident fund', 'pencen', 'pension'],
            'LIFE_INSURANCE' => ['insurance', 'insurans', 'takaful', 'life policy', 'polisi hayat', 'aia',
                                 'great eastern', 'prudential', 'allianz', 'sun life', 'manulife'],
            'SOCSO'          => ['socso', 'perkeso', 'eis', 'employment insurance'],
            'PRS'            => ['prs', 'private retirement', 'deferred annuity', 'persaraan'],
            'EDU_INSURANCE'  => ['education insurance', 'medical insurance', 'insurans pendidikan',
                                 'insurans perubatan', 'medishield'],

            // Education
            'EDU_SELF'       => ['tuition', 'yuran', 'university', 'universiti', 'college', 'kolej',
                                 'course', 'kursus', 'training', 'latihan', 'upskilling', 'diploma',
                                 'degree', 'masters', 'master', 'phd', 'doctorate', 'ptptn', 'sspn',
                                 'online course', 'certification', 'exam fee', 'professional exam',
                                 'hrdc', 'hrdf', 'udemy', 'coursera'],
            'SSPN'           => ['sspn', 'simpanan pendidikan nasional', 'national education savings'],

            // Lifestyle
            'LIFESTYLE'      => ['book', 'buku', 'magazine', 'majalah', 'newspaper', 'akhbar', 'journal',
                                 'internet', 'broadband', 'wifi', 'streamyx', 'unifi', 'maxis',
                                 'digi', 'celcom', 'phone plan', 'data plan', 'laptop', 'computer',
                                 'tablet', 'ipad', 'smartphone', 'handphone', 'hp'],
            'LIFESTYLE_SPORTS' => ['gym', 'fitness', 'sport', 'sukan', 'badminton', 'tennis', 'swimming',
                                   'renang', 'football', 'futsal', 'cycling', 'basikal', 'running',
                                   'marathon', 'yoga', 'pilates', 'dumbbell', 'barbell', 'equipment'],

            // Children
            'CHILDCARE'      => ['tadika', 'kindergarten', 'nursery', 'taska', 'daycare', 'childcare',
                                 'babysitter', 'pengasuh'],
            'EV_CHARGING'    => ['ev', 'electric vehicle', 'tesla', 'charging', 'cas kereta', 'kenderaan elektrik'],

            // Housing
            'HOUSING_LOAN_500' => ['loan interest', 'mortgage', 'rumah', 'house loan', 'home loan',
                                   'pinjaman perumahan', 'bank negara', 'property loan'],

            // Zakat & Donations
            'ZAKAT'          => ['zakat', 'fitrah', 'zakat pendapatan', 'zakat harta', 'lzs', 'maidam',
                                 'zakat selangor', 'tabung haji'],
            'DONATION'       => ['donation', 'derma', 'charity', 'amal', 'wakaf', 'infaq', 'sadaqah',
                                 'ngo', 'relief', 'flood', 'banjir', 'approved institution'],

            // Disability
            'DISABILITY_EQUIPMENT' => ['wheelchair', 'kerusi roda', 'hearing aid', 'alat bantu dengar',
                                       'prosthetic', 'crutch', 'tongkat', 'disabled', 'oku'],
            'BREASTFEEDING'  => ['breastpump', 'breast pump', 'pam susu', 'nursing', 'breastfeeding',
                                 'susu ibu', 'medela', 'spectra'],

            // Employment
            'EMPLOYMENT'     => ['professional membership', 'bar council', 'mia', 'micpa', 'pam',
                                 'ikram', 'professional fee', 'annual subscription', 'professional body'],

            // Business
            'BUSINESS'       => ['business', 'perniagaan', 'invoice', 'receipt', 'supplier', 'vendor',
                                 'client entertainment', 'office supplies', 'stationery', 'alat tulis'],

            // Non-deductible
            'FOOD'           => ['food', 'makanan', 'makan', 'restaurant', 'restoran', 'cafe', 'kafe',
                                 'mamak', 'kopitiam', 'mcdonald', 'kfc', 'pizza', 'burger', 'nasi',
                                 'roti', 'teh', 'kopi', 'grab food', 'foodpanda', 'delivery'],
            'TRANSPORT'      => ['petrol', 'minyak', 'grab', 'taxi', 'bas', 'bus', 'lrt', 'mrt',
                                 'commuter', 'toll', 'parking', 'touch n go', 'tng', 'myrapid',
                                 'uber', 'indriver', 'fuel'],
            'SHOPPING'       => ['shopping', 'clothes', 'pakaian', 'shoes', 'kasut', 'bag', 'beg',
                                 'shopee', 'lazada', 'zalora', 'watson', 'guardian', 'aeon', 'giant',
                                 'tesco', 'mydin', 'parkson', 'h&m', 'uniqlo', 'zara'],
            'ENTERTAINMENT'  => ['movie', 'wayang', 'cinema', 'tgv', 'gsc', 'mbo', 'netflix',
                                 'spotify', 'astro', 'games', 'gaming', 'steam', 'concert',
                                 'karaoke', 'bowling', 'holiday', 'hotel', 'travel', 'flight',
                                 'airasia', 'malindo', 'mas', 'firefly'],
            'UTILITIES'      => ['electric', 'elektrik', 'tnb', 'water', 'air', 'syabas', 'gas',
                                 'ica', 'sewa', 'rent', 'maintenance fee', 'cukai pintu'],
        ];
    }
}