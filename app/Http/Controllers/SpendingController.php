<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SpendingController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $months = (int) $request->query('months', 6);
        $months = in_array($months, [3, 6, 12]) ? $months : 6;

        // ── Monthly totals ────────────────────────────────────────────────
        $monthly = collect();
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $totals = Expense::where('user_id', $userId)
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->selectRaw("
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as spent,
                    SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as income
                ")
                ->first();

            $monthly->push([
                'label'  => $date->format('M Y'),
                'month'  => $date->format('Y-m'),
                'spent'  => (float) $totals->spent,
                'income' => (float) $totals->income,
                'net'    => (float) $totals->income - (float) $totals->spent,
            ]);
        }

        // ── Category breakdown — current month ────────────────────────────
        $categoryBreakdown = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->category?->name ?? 'Uncategorised',
                'color' => $row->category?->color ?? '#94a3b8',
                'code'  => $row->category?->code ?? 'OTHER',
                'total' => (float) $row->total,
            ])
            ->sortByDesc('total')
            ->values();

        $monthTotal = $categoryBreakdown->sum('total');

        $categoryBreakdown = $categoryBreakdown->map(fn ($c) => array_merge($c, [
            'pct' => $monthTotal > 0 ? round(($c['total'] / $monthTotal) * 100) : 0,
        ]));

        // ── Month-on-month comparison ─────────────────────────────────────
        $thisMonth = (float) Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $lastMonth = (float) Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->subMonth()->month)
            ->whereYear('expense_date', now()->subMonth()->year)
            ->sum('amount');

        $momChange   = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : null;
        $momAbsolute = $thisMonth - $lastMonth;

        // ── Top spending category this month ──────────────────────────────
        $topCategory = $categoryBreakdown->first();

        // ── Recurring expense detection ───────────────────────────────────
        $thisMonthTitles = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->pluck('title')
            ->map(fn ($t) => strtolower(trim($t)));

        $recurring = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', now()->subMonth()->month)
            ->whereYear('expense_date', now()->subMonth()->year)
            ->get()
            ->filter(fn ($e) => $thisMonthTitles->contains(strtolower(trim($e->title))))
            ->groupBy(fn ($e) => strtolower(trim($e->title)))
            ->map(fn ($group) => [
                'title'    => $group->first()->title,
                'amount'   => (float) $group->first()->amount,
                'category' => $group->first()->category?->name ?? 'Uncategorised',
                'color'    => $group->first()->category?->color ?? '#94a3b8',
            ])
            ->values()
            ->take(5);

        // ── Spending forecast ─────────────────────────────────────────────
        $dayOfMonth    = (int) now()->format('j');
        $daysInMonth   = (int) now()->format('t');
        $daysRemaining = $daysInMonth - $dayOfMonth;
        $dailyAvg      = $dayOfMonth > 0 ? $thisMonth / $dayOfMonth : 0;
        $forecast      = round($thisMonth + ($dailyAvg * $daysRemaining), 2);

        // ── Net worth snapshot — cumulative over time ─────────────────────
        $netWorth = collect();
        $cumulative = 0;
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $income = (float) Expense::where('user_id', $userId)
                ->where('type', 'income')
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');

            $spent = (float) Expense::where('user_id', $userId)
                ->where('type', 'expense')
                ->whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');

            $cumulative += ($income - $spent);

            $netWorth->push([
                'label'      => $date->format('M Y'),
                'month'      => $date->format('Y-m'),
                'net'        => round($income - $spent, 2),
                'cumulative' => round($cumulative, 2),
            ]);
        }

        return Inertia::render('Spending/Index', [
            'monthly'           => $monthly,
            'categoryBreakdown' => $categoryBreakdown,
            'monthTotal'        => $monthTotal,
            'momChange'         => $momChange,
            'momAbsolute'       => $momAbsolute,
            'topCategory'       => $topCategory,
            'recurring'         => $recurring,
            'currentMonth'      => now()->format('F Y'),
            'lastMonth'         => now()->subMonth()->format('F Y'),
            'selectedMonths'    => $months,
            'forecast'          => $forecast,
            'forecastDailyAvg'  => round($dailyAvg, 2),
            'daysRemaining'     => $daysRemaining,
            'netWorth'          => $netWorth,
        ]);
    }
}