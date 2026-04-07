<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class PdfReportController extends Controller
{
    public function taxSummary(Request $request): Response
    {
        $year   = (int) $request->query('year', now()->year);
        $userId = Auth::id();
        $user   = Auth::user();

        // Deductible expenses
        $expenses = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->get();

        // Group by deduction type
        $breakdown = $expenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->map(function ($items, $deductionType) {
                $category    = $items->first()->category;
                $totalSpent  = (float) $items->sum('amount');
                $annualLimit = $category->annual_limit ? (float) $category->annual_limit : null;
                $claimable   = $annualLimit !== null ? min($totalSpent, $annualLimit) : $totalSpent;

                return [
                    'deduction_type'  => $deductionType,
                    'deduction_label' => Category::deductionTypes()[$deductionType] ?? $deductionType,
                    'total_spent'     => $totalSpent,
                    'annual_limit'    => $annualLimit,
                    'claimable'       => $claimable,
                    'over_limit'      => $annualLimit !== null && $totalSpent > $annualLimit,
                    'entries_count'   => $items->count(),
                    'categories'      => $items->groupBy('category_id')->map(fn ($g) => [
                        'name'  => $g->first()->category->name,
                        'total' => (float) $g->sum('amount'),
                    ])->values(),
                ];
            })
            ->sortByDesc('claimable')
            ->values();

        $totalClaimable = $breakdown->sum('claimable');
        $totalSpent     = $breakdown->sum('total_spent');

        $data = compact('year', 'user', 'breakdown', 'totalClaimable', 'totalSpent');

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.tax-summary', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "tax-deduction-summary-{$year}.pdf";

        return $pdf->download($filename);
    }

    public function exportScheduleA(Request $request): \Illuminate\Http\JsonResponse
    {
        $year   = (int) $request->query('year', now()->year);
        $userId = Auth::id();

        $expenses = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->get();

        $breakdown = $expenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->map(function ($items, $type) {
                $category    = $items->first()->category;
                $totalSpent  = (float) $items->sum('amount');
                $annualLimit = $category->annual_limit ? (float) $category->annual_limit : null;
                $claimable   = $annualLimit !== null ? min($totalSpent, $annualLimit) : $totalSpent;

                return [
                    'deduction_type'  => $type,
                    'deduction_label' => Category::deductionTypes()[$type] ?? $type,
                    'claimable_myr'   => round($claimable, 2),
                    'annual_limit_myr'=> $annualLimit,
                    'total_spent_myr' => round($totalSpent, 2),
                    'entries'         => $items->count(),
                ];
            })
            ->values();

        return response()->json([
            'year'            => $year,
            'total_claimable' => round($breakdown->sum('claimable_myr'), 2),
            'generated_at'    => now()->toISOString(),
            'note'            => 'Use these figures when completing Schedule A (Deductions) in LHDN MyTax e-Filing. Verify each amount against your receipts before submission.',
            'breakdown'       => $breakdown,
        ]);
    }
}