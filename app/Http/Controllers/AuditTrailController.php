<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuditTrailController extends Controller
{
    public function index(Request $request): Response
    {
        $year   = (int) $request->query('year', now()->year);
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

        // All deductible expenses with receipts eager loaded
        $expenses = Expense::with(['category', 'receipts'])
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->orderBy('expense_date', 'desc')
            ->get()
            ->map(fn ($e) => [
                'id'             => $e->id,
                'title'          => $e->title,
                'amount'         => (float) $e->amount,
                'expense_date'   => $e->expense_date->format('Y-m-d'),
                'description'    => $e->description,
                'deduction_type' => $e->category->deduction_type,
                'deduction_label'=> Category::deductionTypes()[$e->category->deduction_type] ?? $e->category->deduction_type,
                'category'       => [
                    'name'  => $e->category->name,
                    'color' => $e->category->color,
                ],
                'receipts'       => $e->receipts->map(fn ($r) => [
                    'id'         => $r->id,
                    'filename'   => $r->original_name,
                    'type'       => $r->type,
                    'is_indexed' => $r->is_indexed,
                ]),
                'has_receipt'    => $e->receipts->count() > 0,
                'receipt_count'  => $e->receipts->count(),
            ]);

        // Stats
        $totalExpenses      = $expenses->count();
        $withReceipts       = $expenses->filter(fn ($e) => $e['has_receipt'])->count();
        $withoutReceipts    = $totalExpenses - $withReceipts;
        $totalClaimable     = $expenses->sum('amount');

        // Group by deduction type for summary
        $byType = $expenses
            ->groupBy('deduction_type')
            ->map(fn ($items, $type) => [
                'deduction_type'  => $type,
                'deduction_label' => Category::deductionTypes()[$type] ?? $type,
                'count'           => $items->count(),
                'total'           => (float) $items->sum('amount'),
                'missing'         => $items->filter(fn ($e) => !$e['has_receipt'])->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return Inertia::render('AuditTrail/Index', [
            'year'           => $year,
            'availableYears' => $availableYears->values(),
            'expenses'       => $expenses,
            'byType'         => $byType,
            'stats'          => [
                'total'          => $totalExpenses,
                'with_receipts'  => $withReceipts,
                'without'        => $withoutReceipts,
                'total_claimable'=> round($totalClaimable, 2),
                'coverage_pct'   => $totalExpenses > 0
                    ? round(($withReceipts / $totalExpenses) * 100)
                    : 100,
            ],
        ]);
    }
}