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
        $year = (int) $request->query('year', now()->year);
        $userId = Auth::id();

        // ── Available years (years the user has any expenses) ─────────────
        $availableYears = Expense::where('user_id', $userId)
            ->selectRaw('YEAR(expense_date) as year')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);

        // Ensure current year is always in the list
        if (!$availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }

        // ── All deductible expenses for the year ──────────────────────────
        $expenses = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->get();

        // ── Group by deduction_type, aggregate totals ─────────────────────
        $grouped = $expenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->map(function ($items, $deductionType) {
                $category = $items->first()->category;
                $totalSpent = $items->sum('amount');
                $annualLimit = $category->annual_limit;
                $claimable = $annualLimit !== null
                    ? min((float) $totalSpent, (float) $annualLimit)
                    : (float) $totalSpent;
                $overLimit = $annualLimit !== null && $totalSpent > $annualLimit;
                $usagePct = $annualLimit
                    ? min(100, round(($totalSpent / $annualLimit) * 100))
                    : null;

                // Collect all unique categories under this deduction type
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

        // ── Receipts uploaded for the year ────────────────────────────────
        $receiptsCount = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->withCount('receipts')
            ->get()
            ->sum('receipts_count');

        // ── Grand totals ──────────────────────────────────────────────────
        $totalClaimable   = $grouped->sum('claimable');
        $totalSpent       = $grouped->sum('total_spent');
        $categoriesOver   = $grouped->where('over_limit', true)->count();

        return Inertia::render('TaxSummary/Index', [
            'year'              => $year,
            'availableYears'    => $availableYears->values(),
            'breakdown'         => $grouped,
            'totalClaimable'    => (float) $totalClaimable,
            'totalSpent'        => (float) $totalSpent,
            'nonDeductibleTotal'=> (float) $nonDeductibleTotal,
            'receiptsCount'     => (int) $receiptsCount,
            'categoriesOver'    => (int) $categoriesOver,
        ]);
    }

    public function show(Request $request, string $deductionType): Response
    {
        $year   = (int) $request->query('year', now()->year);
        $userId = Auth::id();

        // Validate deduction type exists
        $deductionTypes = Category::deductionTypes();
        abort_unless(array_key_exists($deductionType, $deductionTypes), 404);

        // ── All expenses for this deduction type in the year ──────────────
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

        $totalSpent = $expenses->sum('amount');

        // Get limit from first matching category (they share the same deduction type)
        $category   = Category::where('deduction_type', $deductionType)
            ->where('is_tax_deductible', true)
            ->first();
        $annualLimit = $category?->annual_limit ? (float) $category->annual_limit : null;
        $claimable   = $annualLimit !== null
            ? min($totalSpent, $annualLimit)
            : $totalSpent;

        return Inertia::render('TaxSummary/Show', [
            'year'            => $year,
            'deductionType'   => $deductionType,
            'deductionLabel'  => $deductionTypes[$deductionType],
            'expenses'        => $expenses,
            'totalSpent'      => (float) $totalSpent,
            'annualLimit'     => $annualLimit,
            'claimable'       => (float) $claimable,
            'overLimit'       => $annualLimit !== null && $totalSpent > $annualLimit,
        ]);
    }
}