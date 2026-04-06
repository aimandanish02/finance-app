<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        $totals = Expense::where('user_id', $userId)
            ->selectRaw("
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses,
                SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as total_income,
                COUNT(*) as total_count
            ")
            ->first();

        $monthTotals = Expense::where('user_id', $userId)
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->selectRaw("
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as month_expenses,
                SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as month_income
            ")
            ->first();

        $monthlyData = Expense::where('user_id', $userId)
            ->where('expense_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("
                DATE_FORMAT(expense_date, '%Y-%m') as month,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as income
            ")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month'    => $row->month,
                'label'    => \Carbon\Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
                'expenses' => (float) $row->expenses,
                'income'   => (float) $row->income,
            ]);

        $filledMonthly = collect();
        for ($i = 5; $i >= 0; $i--) {
            $key   = now()->subMonths($i)->format('Y-m');
            $label = now()->subMonths($i)->format('M Y');
            $found = $monthlyData->firstWhere('month', $key);
            $filledMonthly->push($found ?? ['month' => $key, 'label' => $label, 'expenses' => 0, 'income' => 0]);
        }

        $byCategory = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->category?->name ?? 'Uncategorised',
                'color' => $row->category?->color ?? '#94a3b8',
                'total' => (float) $row->total,
            ])
            ->sortByDesc('total')
            ->values();

        $recent = Expense::with('category')
            ->where('user_id', $userId)
            ->latest('expense_date')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'amount'       => (float) $e->amount,
                'type'         => $e->type,
                'expense_date' => $e->expense_date->format('Y-m-d'),
                'category'     => $e->category ? [
                    'name'  => $e->category->name,
                    'color' => $e->category->color,
                ] : null,
            ]);

        // Budget alerts — only show budgets at ≥80% or exceeded
        $budgetAlerts = Budget::with('category')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($budget) use ($userId) {
                $query = Expense::where('user_id', $userId)
                    ->where('type', 'expense')
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year);

                if ($budget->category_id) {
                    $query->where('category_id', $budget->category_id);
                }

                $spent  = (float) $query->sum('amount');
                $limit  = (float) $budget->amount;
                $pct    = $limit > 0 ? min(100, round(($spent / $limit) * 100)) : 0;
                $status = $pct >= 100 ? 'exceeded' : ($pct >= 80 ? 'warning' : 'ok');

                return [
                    'id'         => $budget->id,
                    'label'      => $budget->getLabel(),
                    'is_overall' => $budget->isOverall(),
                    'color'      => $budget->category?->color ?? '#6366f1',
                    'amount'     => $limit,
                    'spent'      => $spent,
                    'pct'        => $pct,
                    'status'     => $status,
                ];
            })
            ->filter(fn ($b) => $b['status'] !== 'ok')
            ->sortByDesc('pct')
            ->values();

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalExpenses' => (float) $totals->total_expenses,
                'totalIncome'   => (float) $totals->total_income,
                'netBalance'    => (float) $totals->total_income - (float) $totals->total_expenses,
                'totalCount'    => (int)   $totals->total_count,
                'monthExpenses' => (float) $monthTotals->month_expenses,
                'monthIncome'   => (float) $monthTotals->month_income,
                'monthNet'      => (float) $monthTotals->month_income - (float) $monthTotals->month_expenses,
            ],
            'monthlyChart'  => $filledMonthly->values(),
            'byCategory'    => $byCategory,
            'recent'        => $recent,
            'budgetAlerts'  => $budgetAlerts,
        ]);
    }
}