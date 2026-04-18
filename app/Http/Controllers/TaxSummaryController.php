<?php
 
namespace App\Http\Controllers;
 
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
 
class TaxSummaryController extends Controller
{
    public function index(Request $request): Response
    {
        $year   = (int) $request->query('year', now()->year);
        $userId = Auth::id();
 
        // ── Available years ───────────────────────────────────────────────
        $availableYears = Expense::where('user_id', $userId)
            ->selectRaw('YEAR(expense_date) as year')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);
 
        if (!$availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }
 
        // ── Deductible expenses for the year ──────────────────────────────
        $expenses = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->get();
 
        // ── Group by deduction type ───────────────────────────────────────
        $grouped = $expenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->map(function ($items, $deductionType) {
                $category    = $items->first()->category;
                $totalSpent  = $items->sum('amount');
                $annualLimit = $category->annual_limit;
                $claimable   = $annualLimit !== null
                    ? min((float) $totalSpent, (float) $annualLimit)
                    : (float) $totalSpent;
                $overLimit   = $annualLimit !== null && $totalSpent > $annualLimit;
                $usagePct    = $annualLimit
                    ? min(100, round(($totalSpent / $annualLimit) * 100))
                    : null;
 
                $categories = $items
                    ->groupBy('category_id')
                    ->map(fn ($catItems) => [
                        'id'    => $catItems->first()->category->id,
                        'name'  => $catItems->first()->category->name,
                        'color' => $catItems->first()->category->color,
                        'total' => (float) $catItems->sum('amount'),
                    ])
                    ->values();
 
                return [
                    'deduction_type'  => $deductionType,
                    'deduction_label' => Category::deductionTypes()[$deductionType] ?? $deductionType,
                    'total_spent'     => (float) $totalSpent,
                    'annual_limit'    => $annualLimit ? (float) $annualLimit : null,
                    'claimable'       => $claimable,
                    'over_limit'      => $overLimit,
                    'usage_pct'       => $usagePct,
                    'entries_count'   => $items->count(),
                    'categories'      => $categories,
                ];
            })
            ->sortByDesc('claimable')
            ->values();
 
        // ── Non-deductible total ──────────────────────────────────────────
        $nonDeductibleTotal = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', false))
            ->sum('amount');
 
        // ── Receipts count ────────────────────────────────────────────────
        $receiptsCount = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->withCount('receipts')
            ->get()
            ->sum('receipts_count');
 
        // ── Totals ────────────────────────────────────────────────────────
        $totalClaimable = $grouped->sum('claimable');
        $totalSpent     = $grouped->sum('total_spent');
        $categoriesOver = $grouped->where('over_limit', true)->count();
 
        // ── Deduction limit alerts (≥80% used, for current year) ──────────
        $limitAlerts = $grouped
            ->filter(fn ($row) => $row['usage_pct'] !== null && $row['usage_pct'] >= 80)
            ->map(fn ($row) => [
                'deduction_type'  => $row['deduction_type'],
                'deduction_label' => $row['deduction_label'],
                'usage_pct'       => $row['usage_pct'],
                'total_spent'     => $row['total_spent'],
                'annual_limit'    => $row['annual_limit'],
                'claimable'       => $row['claimable'],
                'over_limit'      => $row['over_limit'],
                'status'          => $row['over_limit'] ? 'exceeded' : 'warning',
            ])
            ->sortByDesc('usage_pct')
            ->values();
 
        // ── Malaysian income tax brackets (YA2025) ────────────────────────
        $taxBrackets = [
            ['min' => 0,       'max' => 5000,    'rate' => 0,    'label' => 'Up to RM5,000'],
            ['min' => 5001,    'max' => 20000,   'rate' => 1,    'label' => 'RM5,001 – RM20,000'],
            ['min' => 20001,   'max' => 35000,   'rate' => 3,    'label' => 'RM20,001 – RM35,000'],
            ['min' => 35001,   'max' => 50000,   'rate' => 6,    'label' => 'RM35,001 – RM50,000'],
            ['min' => 50001,   'max' => 70000,   'rate' => 11,   'label' => 'RM50,001 – RM70,000'],
            ['min' => 70001,   'max' => 100000,  'rate' => 19,   'label' => 'RM70,001 – RM100,000'],
            ['min' => 100001,  'max' => 400000,  'rate' => 25,   'label' => 'RM100,001 – RM400,000'],
            ['min' => 400001,  'max' => 600000,  'rate' => 26,   'label' => 'RM400,001 – RM600,000'],
            ['min' => 600001,  'max' => 2000000, 'rate' => 28,   'label' => 'RM600,001 – RM2,000,000'],
            ['min' => 2000001, 'max' => null,    'rate' => 30,   'label' => 'Above RM2,000,000'],
        ];


        // ── Tax rule guidance per deduction type ─────────────────────────
        $taxGuidance = [
            'PERSONAL'             => ['title' => 'Self Relief', 'limit' => 'RM9,000', 'notes' => 'Automatic relief for all resident taxpayers. No receipts needed.', 'qualifies' => ['Automatic for all resident individuals filing taxes'], 'receipts' => 'None required'],
            'SPOUSE'               => ['title' => 'Spouse Relief', 'limit' => 'RM4,000', 'notes' => 'Claimable if spouse has no income or elects for joint assessment.', 'qualifies' => ['Spouse with no income', 'Spouse electing joint assessment'], 'receipts' => 'Marriage certificate'],
            'ALIMONY'              => ['title' => 'Alimony', 'limit' => 'RM4,000', 'notes' => 'Alimony payments to former wife.', 'qualifies' => ['Alimony payments to divorced spouse'], 'receipts' => 'Court order + payment receipts'],
            'CHILD'                => ['title' => 'Child Relief', 'limit' => 'RM2,000–RM8,000 per child', 'notes' => 'Below 18: RM2,000. Above 18 in matriculation/A-Level: RM2,000. Above 18 in diploma or higher: RM8,000.', 'qualifies' => ['Unmarried children below 18', 'Children above 18 in full-time education'], 'receipts' => 'Birth certificate, enrollment letter'],
            'DISABLED_SELF'        => ['title' => 'Disabled Self', 'limit' => 'RM7,000 (additional)', 'notes' => 'Additional relief on top of RM9,000 self relief. Must be registered with JKM.', 'qualifies' => ['Taxpayer registered as disabled with JKM'], 'receipts' => 'JKM disability registration certificate'],
            'DISABLED_SPOUSE'      => ['title' => 'Disabled Spouse', 'limit' => 'RM6,000 (additional)', 'notes' => 'Additional on top of spouse relief.', 'qualifies' => ['Disabled spouse registered with JKM'], 'receipts' => 'JKM certificate, marriage certificate'],
            'DISABLED_CHILD'       => ['title' => 'Disabled Child', 'limit' => 'RM8,000 per child', 'notes' => 'Additional RM8,000 if disabled child is 18+ in higher education.', 'qualifies' => ['Physically or mentally handicapped child', 'Registered with JKM'], 'receipts' => 'JKM certificate, birth certificate'],
            'DISABILITY_EQUIPMENT' => ['title' => 'Disability Equipment', 'limit' => 'RM6,000', 'notes' => 'Basic supporting equipment only. Spectacles and optical lenses are excluded.', 'qualifies' => ['Wheelchair, crutches, hearing aids, haemodialysis machines', 'Artificial limbs, prosthetics'], 'receipts' => 'Purchase receipts + prescription if applicable'],
            'MEDICAL_SELF'         => ['title' => 'Medical (Self/Spouse/Child)', 'limit' => 'RM10,000 total', 'notes' => 'Multiple sub-limits apply within the RM10,000 cap: Dental ≤RM1,000 | Vaccination ≤RM1,000 | Medical exam/self-test/mental health ≤RM1,000 | Learning disability ≤RM6,000.', 'qualifies' => ['Serious disease treatment (cancer, heart disease, AIDS, Parkinsons, etc.)', 'Fertility treatment (IUI, IVF)', 'Dental by registered Malaysian Dental Council practitioners', 'COVID-19 tests, mental health consultations', 'Vaccination (approved by MOH)', 'Learning disability diagnosis, early intervention, rehabilitation for children under 18'], 'receipts' => 'Medical bills, prescription receipts, dental receipts'],
            'MEDICAL_PARENT'       => ['title' => 'Medical (Parents)', 'limit' => 'RM8,000', 'notes' => "For parents' medical, dental, and care expenses. Includes home nursing and daycare.", 'qualifies' => ['Medical treatment for parents', 'Dental treatment', 'Full medical examination (vaccination ≤RM1,000 within this limit)', 'Special needs or carer/nursing home expenses'], 'receipts' => 'Medical bills, carer receipts, home/daycare invoices'],
            'EPF'                  => ['title' => 'EPF Contribution', 'limit' => 'RM4,000', 'notes' => 'Covers mandatory employee EPF contributions and additional voluntary contributions.', 'qualifies' => ['Mandatory EPF deductions from salary', 'Additional voluntary EPF top-up (i-Saraan, i-Suri)'], 'receipts' => 'EPF statement or EA form'],
            'LIFE_INSURANCE'       => ['title' => 'Life Insurance / Takaful', 'limit' => 'RM3,000', 'notes' => 'Combined with voluntary EPF. Total EPF + Life Insurance = RM7,000 max.', 'qualifies' => ['Life insurance premium payments', 'Family takaful contributions', 'Additional voluntary EPF contributions'], 'receipts' => 'Insurance premium receipts or statements'],
            'EDUCATION_INSURANCE'  => ['title' => 'Education & Medical Insurance', 'limit' => 'RM4,000', 'notes' => 'Insurance specifically for education or medical benefits. Separate from life insurance relief.', 'qualifies' => ['Education insurance premiums for self, spouse, or child', 'Medical insurance premiums (not employer-provided)'], 'receipts' => 'Insurance premium receipts'],
            'PRS'                  => ['title' => 'Private Retirement Scheme', 'limit' => 'RM3,000', 'notes' => 'Contributions to SC-approved PRS providers. Extended until YA2030.', 'qualifies' => ['PRS contributions to approved providers (e.g. Kenanga, CIMB, Manulife PRS)', 'Deferred annuity scheme contributions'], 'receipts' => 'PRS contribution statements'],
            'SOCSO'                => ['title' => 'SOCSO / EIS', 'limit' => 'RM350', 'notes' => 'Employee contributions to SOCSO (PERKESO) and EIS only. Not employer contributions.', 'qualifies' => ['Employee SOCSO monthly deductions', 'Employment Insurance System (EIS) contributions'], 'receipts' => 'EA form or salary slip showing SOCSO/EIS deductions'],
            'SSPN'                 => ['title' => 'SSPN Deposit', 'limit' => 'RM8,000', 'notes' => 'Net deposit (total deposits minus withdrawals) in SSPN account. Claimable by either parent. Available YA2025–YA2027.', 'qualifies' => ['Deposits into Skim Simpanan Pendidikan Nasional (SSPN) account'], 'receipts' => 'SSPN deposit slips or account statement'],
            'EDUCATION_SELF'       => ['title' => 'Education Fees (Self)', 'limit' => 'RM7,000', 'notes' => 'Upskilling/self-enhancement courses capped at RM2,000 within the RM7,000 limit (YA2022–YA2026).', 'qualifies' => ['Course fees at local institution up to tertiary level in law, accounting, Islamic finance, technical, vocational, scientific fields', 'Masters or Doctorate for any skill', 'Upskilling courses recognised by Director General of Skills Development (≤RM2,000)'], 'receipts' => 'Official receipt from institution, course enrollment letter'],
            'LIFESTYLE'            => ['title' => 'Lifestyle Relief', 'limit' => 'RM2,500', 'notes' => 'Covers books, internet, smartphone, laptop, and upskilling. Physical books only (not audiobooks).', 'qualifies' => ['Books, journals, magazines, newspapers (print or digital)', 'Personal computer, smartphone, or tablet', 'Internet subscription (home broadband or mobile data plan)', 'Upskilling/self-enhancement courses'], 'receipts' => 'Purchase receipts, subscription invoices'],
            'LIFESTYLE_SPORTS'     => ['title' => 'Lifestyle — Sports', 'limit' => 'RM1,000 (separate from RM2,500 lifestyle)', 'notes' => 'Gym membership is included from YA2024 onwards.', 'qualifies' => ['Sports equipment purchase', 'Entry/rental fees for sports facilities', 'Sports competition registration fees', 'Gym membership or fitness centre fees', 'Sports training/coaching fees'], 'receipts' => 'Purchase receipts, membership invoices'],
            'BREASTFEEDING'        => ['title' => 'Breastfeeding Equipment', 'limit' => 'RM1,000 every 2 years', 'notes' => 'For women taxpayers only. Claimable once every 2 years for child under 2.', 'qualifies' => ['Breast pump purchase', 'Breastfeeding accessories (bottles, bags, sterilisers)'], 'receipts' => 'Purchase receipts. Child birth certificate to verify age.'],
            'CHILDCARE'            => ['title' => 'Childcare & Kindergarten', 'limit' => 'RM3,000', 'notes' => 'Only for registered childcare centres and kindergartens. Child must be below 6 years old.', 'qualifies' => ['Fees paid to registered taska (childcare)', 'Registered tadika (kindergarten) fees', 'Child must be below 6 years old'], 'receipts' => 'Official receipts from registered centre, registration certificate of centre'],
            'EV_CHARGING'          => ['title' => 'EV Charging / Food Composter', 'limit' => 'RM2,500', 'notes' => 'EV charging available YA2023–YA2027. Food waste composter available YA2025–YA2027.', 'qualifies' => ['EV charging facility installation at home', 'EV charging equipment rental or hire-purchase', 'EV charging subscription fees', 'Food waste composting machine for household use'], 'receipts' => 'Installation invoice, subscription receipts'],
            'HOUSING_LOAN'         => ['title' => 'Housing Loan Interest', 'limit' => 'RM7,000 (≤RM500k property) or RM5,000 (RM500k–RM750k)', 'notes' => 'Only for first 3 consecutive years. SPA must be signed Jan 2025–Dec 2027.', 'qualifies' => ['Interest on home loan for residential property valued ≤RM750,000', 'First 3 years of loan repayment only', 'SPA executed between 1 Jan 2025 – 31 Dec 2027'], 'receipts' => 'Bank loan statement showing interest portion, SPA copy'],
            'ZAKAT'                => ['title' => 'Zakat / Fitrah', 'limit' => 'Unlimited (full rebate against tax charged)', 'notes' => 'This is a TAX REBATE, not a relief. It directly reduces tax payable, not chargeable income.', 'qualifies' => ['Zakat pendapatan (income zakat)', 'Zakat harta (wealth zakat)', 'Zakat fitrah'], 'receipts' => 'Official receipt from authorised zakat institution (e.g. LZS, MAIWP, MAIS)'],
            'DONATION'             => ['title' => 'Donation to Approved Institution', 'limit' => '10% of aggregate income', 'notes' => 'Only donations to LHDN-approved institutions qualify. Keep the official receipt.', 'qualifies' => ['Cash donations to LHDN-approved institutions/organisations/funds', 'Wakaf to religious authorities or public universities', 'Endowment to public universities'], 'receipts' => 'Official receipt from approved institution showing their approval number'],
            'EMPLOYMENT'           => ['title' => 'Employment Expenses', 'limit' => 'Actual amount (no fixed limit)', 'notes' => 'Must be wholly and exclusively for employment duties. Private or domestic expenses not allowed.', 'qualifies' => ['Professional membership subscriptions (Bar Council, MIA, MICPA)', 'Work-related expenses not reimbursed by employer'], 'receipts' => 'Professional body receipts, employer confirmation if needed'],
            'BUSINESS'             => ['title' => 'Business Expenses', 'limit' => 'Actual amount (no fixed limit)', 'notes' => 'Expenses must be wholly incurred in producing business income.', 'qualifies' => ['Home office costs (proportionate)', 'Vehicle expenses for business use', 'Business supplies and equipment', 'Professional fees, subscriptions'], 'receipts' => 'Invoices, receipts, logbooks for vehicle usage'],
            'NOT_DEDUCTIBLE'       => ['title' => 'Not Tax Deductible', 'limit' => 'N/A', 'notes' => 'These expenses do not qualify for LHDN tax deductions.', 'qualifies' => [], 'receipts' => 'N/A'],
        ];

return Inertia::render('TaxSummary/Index', [
            'year'              => $year,
            'availableYears'    => $availableYears->values(),
            'breakdown'         => $grouped,
            'totalClaimable'    => (float) $totalClaimable,
            'totalSpent'        => (float) $totalSpent,
            'nonDeductibleTotal'=> (float) $nonDeductibleTotal,
            'receiptsCount'     => (int) $receiptsCount,
            'categoriesOver'    => (int) $categoriesOver,
            'limitAlerts'       => $limitAlerts,
            'taxBrackets'       => $taxBrackets,
            'taxGuidance'       => $taxGuidance,
        ]);
    }
 
    public function compare(Request $request): Response
    {
        $userId = Auth::id();
 
        $availableYears = Expense::where('user_id', $userId)
            ->selectRaw('YEAR(expense_date) as year')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);
 
        if (!$availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }
 
        // Compare up to 3 most recent years
        $yearsToCompare = $availableYears->take(3)->values();
 
        $deductionTypes = Category::deductionTypes();
 
        // Build data per year
        $byYear = $yearsToCompare->map(function ($year) use ($userId, $deductionTypes) {
            $expenses = Expense::with('category')
                ->where('user_id', $userId)
                ->where('type', 'expense')
                ->whereYear('expense_date', $year)
                ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
                ->get();
 
            $breakdown = $expenses
                ->groupBy(fn ($e) => $e->category->deduction_type)
                ->map(function ($items) {
                    $category   = $items->first()->category;
                    $spent      = (float) $items->sum('amount');
                    $limit      = $category->annual_limit ? (float) $category->annual_limit : null;
                    $claimable  = $limit !== null ? min($spent, $limit) : $spent;
                    return [
                        'total_spent' => $spent,
                        'claimable'   => $claimable,
                        'annual_limit'=> $limit,
                    ];
                });
 
            return [
                'year'           => $year,
                'breakdown'      => $breakdown,
                'totalClaimable' => round($breakdown->sum('claimable'), 2),
                'totalSpent'     => round($breakdown->sum('total_spent'), 2),
            ];
        });
 
        // Collect all deduction types present across all years
        $allTypes = $byYear
            ->flatMap(fn ($y) => $y['breakdown']->keys())
            ->unique()
            ->filter(fn ($t) => isset($deductionTypes[$t]))
            ->values();
 
        // Build comparison rows
        $rows = $allTypes->map(function ($type) use ($byYear, $deductionTypes) {
            $row = [
                'deduction_type'  => $type,
                'deduction_label' => $deductionTypes[$type] ?? $type,
                'years'           => [],
            ];
            foreach ($byYear as $yearData) {
                $entry = $yearData['breakdown'][$type] ?? null;
                $row['years'][$yearData['year']] = $entry ? [
                    'claimable'    => $entry['claimable'],
                    'total_spent'  => $entry['total_spent'],
                    'annual_limit' => $entry['annual_limit'],
                ] : null;
            }
            return $row;
        });
 
        return Inertia::render('TaxSummary/Compare', [
            'years'          => $yearsToCompare,
            'rows'           => $rows->values(),
            'byYear'         => $byYear->keyBy('year'),
            'availableYears' => $availableYears->values(),
        ]);
    }

    public function show(Request $request, $deductionType): Response
    {
        $year   = (int) $request->query('year', now()->year);
        $userId = Auth::id();
 
        $deductionTypes = Category::deductionTypes();
        abort_unless(array_key_exists($deductionType, $deductionTypes), 404);
 
        $expenses = Expense::with('category', 'receipts')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q
                ->where('is_tax_deductible', true)
                ->where('deduction_type', $deductionType)
            )
            ->latest('expense_date')
            ->get()
            ->map(fn ($e) => [
                'id'             => $e->id,
                'title'          => $e->title,
                'amount'         => (float) $e->amount,
                'expense_date'   => $e->expense_date->format('Y-m-d'),
                'description'    => $e->description,
                'receipts_count' => $e->receipts->count(),
                'category'       => [
                    'id'    => $e->category->id,
                    'name'  => $e->category->name,
                    'color' => $e->category->color,
                ],
            ]);
 
        $totalSpent  = $expenses->sum('amount');
        $category    = Category::where('deduction_type', $deductionType)
            ->where('is_tax_deductible', true)
            ->first();
        $annualLimit = $category?->annual_limit ? (float) $category->annual_limit : null;
        $claimable   = $annualLimit !== null ? min($totalSpent, $annualLimit) : $totalSpent;
 
        return Inertia::render('TaxSummary/Show', [
            'year'           => $year,
            'deductionType'  => $deductionType,
            'deductionLabel' => $deductionTypes[$deductionType],
            'expenses'       => $expenses,
            'totalSpent'     => (float) $totalSpent,
            'annualLimit'    => $annualLimit,
            'claimable'      => (float) $claimable,
            'overLimit'      => $annualLimit !== null && $totalSpent > $annualLimit,
        ]);
    }
}
