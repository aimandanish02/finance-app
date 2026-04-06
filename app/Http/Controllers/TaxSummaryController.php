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
        ]);
    }

    public function show(Request $request, string $deductionType): Response
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