<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\TaxProfile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();
        $year   = now()->year;
        $month  = now()->month;

        // ── 1. FINANCIAL OVERVIEW ─────────────────────────────────────────

        $allTime = Expense::where('user_id', $userId)
            ->selectRaw("
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_expenses,
                SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) as total_income
            ")->first();

        $thisMonthTotals = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)->whereMonth('expense_date', $month)
            ->selectRaw("
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as spent,
                SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) as income
            ")->first();

        $thisMonthSpent  = (float) $thisMonthTotals->spent;
        $thisMonthIncome = (float) $thisMonthTotals->income;

        // Forecast: daily avg × remaining days
        $dayOfMonth    = (int) now()->format('j');
        $daysInMonth   = (int) now()->format('t');
        $daysRemaining = $daysInMonth - $dayOfMonth;
        $dailyAvg      = $dayOfMonth > 0 ? $thisMonthSpent / $dayOfMonth : 0;
        $forecast      = round($thisMonthSpent + ($dailyAvg * $daysRemaining), 2);

        // ── 2. TAX CLAIMABLE + EST. SAVINGS ──────────────────────────────

        $deductibleExpenses = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->with('category')
            ->get();

        $totalClaimable = $deductibleExpenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->sum(function ($items) {
                $limit = $items->first()->category->annual_limit;
                $spent = $items->sum('amount');
                return $limit !== null ? min((float) $spent, (float) $limit) : (float) $spent;
            });

        // Estimate tax saved at 19% (RM70k–RM100k bracket — most common working adult)
        $estTaxSaved = round($totalClaimable * 0.19);

        // ── 3. BUDGET ALERTS ─────────────────────────────────────────────

        $budgetAlerts = Budget::with('category')
            ->where('user_id', $userId)
            ->get()
            ->map(function ($budget) use ($userId, $month, $year) {
                $query = Expense::where('user_id', $userId)
                    ->where('type', 'expense')
                    ->whereMonth('expense_date', $month)
                    ->whereYear('expense_date', $year);
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

        // ── 4. SAVINGS GOALS ─────────────────────────────────────────────

        $monthlyIncome = $thisMonthIncome;
        $goals = Goal::where('user_id', $userId)
            ->where('is_completed', false)
            ->orderBy('created_at')
            ->take(3)
            ->get()
            ->map(function ($goal) use ($monthlyIncome, $userId) {
                $pct = $goal->progressPct($monthlyIncome);

                // months to goal
                $monthsToGoal = null;
                if ($goal->type === 'target_amount' && $goal->target_amount > 0) {
                    $remaining = max(0, (float) $goal->target_amount - (float) $goal->current_savings);
                    $avg = $this->avgMonthlySavings($userId, 3);
                    if ($avg > 0) $monthsToGoal = (int) ceil($remaining / $avg);
                }

                return [
                    'id'              => $goal->id,
                    'name'            => $goal->name,
                    'type'            => $goal->type,
                    'target_amount'   => $goal->target_amount ? (float) $goal->target_amount : null,
                    'target_pct'      => $goal->target_percentage ? (float) $goal->target_percentage : null,
                    'current_savings' => (float) $goal->current_savings,
                    'color'           => $goal->color,
                    'pct'             => $pct,
                    'months_to_goal'  => $monthsToGoal,
                ];
            });

        // ── 5. TAX DEDUCTION LIMIT ALERTS ────────────────────────────────

        $taxLimitAlerts = $deductibleExpenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->map(function ($items, $type) {
                $category    = $items->first()->category;
                $totalSpent  = (float) $items->sum('amount');
                $annualLimit = $category->annual_limit ? (float) $category->annual_limit : null;
                if (!$annualLimit) return null;
                $pct    = min(100, round(($totalSpent / $annualLimit) * 100));
                $status = $pct >= 100 ? 'exceeded' : ($pct >= 80 ? 'warning' : 'ok');
                return [
                    'deduction_type'  => $type,
                    'deduction_label' => Category::deductionTypes()[$type] ?? $type,
                    'total_spent'     => $totalSpent,
                    'annual_limit'    => $annualLimit,
                    'pct'             => $pct,
                    'status'          => $status,
                ];
            })
            ->filter(fn ($r) => $r && $r['status'] !== 'ok')
            ->sortByDesc('pct')
            ->values()
            ->take(3);

        // ── 6. SPENDING TREND (last 6 months) ────────────────────────────

        $spendingTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $totals = Expense::where('user_id', $userId)
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->selectRaw("
                    SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as spent,
                    SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) as income
                ")->first();
            $spendingTrend->push([
                'label'  => $date->format('M'),
                'month'  => $date->format('Y-m'),
                'spent'  => (float) $totals->spent,
                'income' => (float) $totals->income,
            ]);
        }

        // Top category this month
        $topCategory = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        // Recurring count
        $thisMonthTitles = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->pluck('title')
            ->map(fn ($t) => strtolower(trim($t)));

        $recurringCount = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->subMonth()->month)
            ->whereYear('expense_date', now()->subMonth()->year)
            ->get()
            ->filter(fn ($e) => $thisMonthTitles->contains(strtolower(trim($e->title))))
            ->groupBy(fn ($e) => strtolower(trim($e->title)))
            ->count();

        // Mom change
        $lastMonthSpent = (float) Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->subMonth()->month)
            ->whereYear('expense_date', now()->subMonth()->year)
            ->sum('amount');
        $momPct = $lastMonthSpent > 0
            ? round((($thisMonthSpent - $lastMonthSpent) / $lastMonthSpent) * 100, 1)
            : null;

        // ── 7. RECENT TRANSACTIONS ────────────────────────────────────────

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
                'category'     => $e->category
                    ? ['name' => $e->category->name, 'color' => $e->category->color]
                    : null,
            ]);

        // ── 8. PERSONAL TAX RELIEFS ───────────────────────────────────────

        $taxProfile = TaxProfile::firstOrCreate(
            ['user_id' => $userId],
            ['has_spouse' => false, 'self_disabled' => false, 'children' => [], 'has_parents_medical' => false, 'has_disabled_equipment' => false]
        );
        $reliefs = $taxProfile->calculateReliefs();

        // ── 9. NET WORTH (12-month cumulative) ────────────────────────────

        $netWorthHistory = collect();
        $cumulative = 0;
        for ($i = 11; $i >= 0; $i--) {
            $date   = now()->subMonths($i);
            $income = (float) Expense::where('user_id', $userId)
                ->where('type', 'income')
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');
            $spent  = (float) Expense::where('user_id', $userId)
                ->where('type', 'expense')
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');
            $cumulative += ($income - $spent);
            $netWorthHistory->push([
                'label'      => $date->format('M'),
                'month'      => $date->format('Y-m'),
                'cumulative' => round($cumulative, 2),
            ]);
        }

        $avgMonthlySavings = $this->avgMonthlySavings($userId, 6);
        $bestMonth = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->selectRaw("DATE_FORMAT(expense_date,'%Y-%m') as month,
                SUM(CASE WHEN type='income' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as net")
            ->groupBy('month')
            ->orderByDesc('net')
            ->first();

        // ── 10. RECEIPTS / AUDIT ──────────────────────────────────────────

        $totalReceipts = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->withCount('receipts')
            ->get()
            ->sum('receipts_count');

        $indexedReceipts = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->whereHas('receipts', fn ($q) => $q->where('is_indexed', true))
            ->withCount(['receipts' => fn ($q) => $q->where('is_indexed', true)])
            ->get()
            ->sum('receipts_count');

        $missingReceipts = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->doesntHave('receipts')
            ->count();

        return Inertia::render('Dashboard', [
            // Row 1 — overview
            'overview' => [
                'net_balance'      => round((float) $allTime->total_income - (float) $allTime->total_expenses, 2),
                'month_spent'      => round($thisMonthSpent, 2),
                'month_income'     => round($thisMonthIncome, 2),
                'forecast'         => $forecast,
                'daily_avg'        => round($dailyAvg, 2),
                'days_remaining'   => $daysRemaining,
                'total_claimable'  => round($totalClaimable, 2),
                'est_tax_saved'    => $estTaxSaved,
                'current_month'    => now()->format('F Y'),
            ],
            // Row 2 — alerts + goals + tax limits
            'budgetAlerts'    => $budgetAlerts,
            'goals'           => $goals,
            'taxLimitAlerts'  => $taxLimitAlerts,
            // Row 3 — spending + recent
            'spendingTrend'   => $spendingTrend,
            'topCategory'     => $topCategory ? [
                'name'  => $topCategory->category?->name ?? 'Uncategorised',
                'color' => $topCategory->category?->color ?? '#94a3b8',
                'total' => (float) $topCategory->total,
                'pct'   => $thisMonthSpent > 0 ? round(($topCategory->total / $thisMonthSpent) * 100) : 0,
            ] : null,
            'recurringCount'  => $recurringCount,
            'momPct'          => $momPct,
            'recent'          => $recent,
            // Row 4 — reliefs + net worth + receipts
            'reliefs'             => $reliefs,
            'netWorthHistory'     => $netWorthHistory,
            'netWorthCurrent'     => round($cumulative, 2),
            'avgMonthlySavings'   => round($avgMonthlySavings, 2),
            'bestMonth'           => $bestMonth ? [
                'label' => \Carbon\Carbon::createFromFormat('Y-m', $bestMonth->month)->format('M Y'),
                'net'   => round((float) $bestMonth->net, 2),
            ] : null,
            'receipts' => [
                'total'   => (int) $totalReceipts,
                'indexed' => (int) $indexedReceipts,
                'missing' => (int) $missingReceipts,
            ],
            'year' => $year,
        ]);
    }

    private function avgMonthlySavings(int $userId, int $months): float
    {
        $total = 0;
        for ($i = 1; $i <= $months; $i++) {
            $date = now()->subMonths($i);
            $income = (float) Expense::where('user_id', $userId)->where('type', 'income')
                ->whereMonth('expense_date', $date->month)->whereYear('expense_date', $date->year)->sum('amount');
            $spent  = (float) Expense::where('user_id', $userId)->where('type', 'expense')
                ->whereMonth('expense_date', $date->month)->whereYear('expense_date', $date->year)->sum('amount');
            $total += max(0, $income - $spent);
        }
        return $months > 0 ? round($total / $months, 2) : 0;
    }
}