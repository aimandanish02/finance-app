<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        $monthlyIncome = (float) Expense::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $goals = Goal::where('user_id', $userId)
            ->orderBy('is_completed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($goal) => $this->formatGoal($goal, $monthlyIncome));

        return Inertia::render('Goals/Index', [
            'goals'         => $goals,
            'monthlyIncome' => $monthlyIncome,
            'currentMonth'  => now()->format('F Y'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Goals/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:100',
            'type'               => 'required|in:target_amount,monthly_percentage',
            'target_amount'      => 'nullable|required_if:type,target_amount|numeric|min:1',
            'target_percentage'  => 'nullable|required_if:type,monthly_percentage|numeric|min:1|max:100',
            'current_savings'    => 'nullable|numeric|min:0',
            'deadline'           => 'nullable|date|after:today',
            'color'              => 'nullable|string|max:50',
            'notes'              => 'nullable|string|max:500',
        ]);

        $goal = Goal::create([
            'user_id'           => Auth::id(),
            'name'              => $validated['name'],
            'type'              => $validated['type'],
            'target_amount'     => $validated['target_amount'] ?? null,
            'target_percentage' => $validated['target_percentage'] ?? null,
            'current_savings'   => $validated['current_savings'] ?? 0,
            'deadline'          => $validated['deadline'] ?? null,
            'color'             => $validated['color'] ?? '#6366f1',
            'notes'             => $validated['notes'] ?? null,
            'is_completed'      => false,
        ]);

        return redirect()->route('goals.show', $goal)
            ->with('success', 'Goal created.');
    }

    public function show(Goal $goal): Response
    {
        abort_if($goal->user_id !== Auth::id(), 403);

        $userId = Auth::id();

        $monthlyIncome = (float) Expense::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        // Monthly net savings for last 6 months (income - expenses)
        $savingsHistory = collect();
        for ($i = 5; $i >= 0; $i--) {
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

            $net = $income - $spent;

            $targetMonthly = $goal->type === 'monthly_percentage' && $income > 0
                ? ($goal->target_percentage / 100) * $income
                : null;

            $savingsHistory->push([
                'label'          => $date->format('M Y'),
                'month'          => $date->format('Y-m'),
                'net'            => $net,
                'income'         => $income,
                'spent'          => $spent,
                'target_monthly' => $targetMonthly,
            ]);
        }

        return Inertia::render('Goals/Show', [
            'goal'           => $this->formatGoal($goal, $monthlyIncome),
            'savingsHistory' => $savingsHistory,
            'monthlyIncome'  => $monthlyIncome,
            'currentMonth'   => now()->format('F Y'),
        ]);
    }

    public function edit(Goal $goal): Response
    {
        abort_if($goal->user_id !== Auth::id(), 403);

        return Inertia::render('Goals/Edit', [
            'goal' => [
                'id'                 => $goal->id,
                'name'               => $goal->name,
                'type'               => $goal->type,
                'target_amount'      => $goal->target_amount,
                'target_percentage'  => $goal->target_percentage,
                'current_savings'    => $goal->current_savings,
                'deadline'           => $goal->deadline?->format('Y-m-d'),
                'color'              => $goal->color,
                'notes'              => $goal->notes ?? '',
                'is_completed'       => $goal->is_completed,
            ],
        ]);
    }

    public function update(Request $request, Goal $goal): RedirectResponse
    {
        abort_if($goal->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'type'              => 'required|in:target_amount,monthly_percentage',
            'target_amount'     => 'nullable|required_if:type,target_amount|numeric|min:1',
            'target_percentage' => 'nullable|required_if:type,monthly_percentage|numeric|min:1|max:100',
            'current_savings'   => 'nullable|numeric|min:0',
            'deadline'          => 'nullable|date',
            'color'             => 'nullable|string|max:50',
            'notes'             => 'nullable|string|max:500',
            'is_completed'      => 'boolean',
        ]);

        $goal->update([
            'name'              => $validated['name'],
            'type'              => $validated['type'],
            'target_amount'     => $validated['target_amount'] ?? null,
            'target_percentage' => $validated['target_percentage'] ?? null,
            'current_savings'   => $validated['current_savings'] ?? 0,
            'deadline'          => $validated['deadline'] ?? null,
            'color'             => $validated['color'] ?? $goal->color,
            'notes'             => $validated['notes'] ?? null,
            'is_completed'      => $validated['is_completed'] ?? false,
        ]);

        return redirect()->route('goals.show', $goal)
            ->with('success', 'Goal updated.');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        abort_if($goal->user_id !== Auth::id(), 403);
        $goal->delete();

        return redirect()->route('goals.index')
            ->with('success', 'Goal deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatGoal(Goal $goal, float $monthlyIncome): array
    {
        $pct           = $goal->progressPct($monthlyIncome);
        $daysRemaining = $goal->daysRemaining();

        $targetMonthly = null;
        if ($goal->type === 'monthly_percentage' && $monthlyIncome > 0) {
            $targetMonthly = round(($goal->target_percentage / 100) * $monthlyIncome, 2);
        }

        // Forecast months to reach target (target_amount type only)
        $monthsToGoal = null;
        if ($goal->type === 'target_amount' && $goal->target_amount > 0) {
            $remaining = max(0, (float) $goal->target_amount - (float) $goal->current_savings);
            // Use average monthly net savings from last 3 months
            $avgMonthlySavings = $this->avgMonthlySavings($goal->user_id, 3);
            if ($avgMonthlySavings > 0) {
                $monthsToGoal = (int) ceil($remaining / $avgMonthlySavings);
            }
        }

        return [
            'id'                => $goal->id,
            'name'              => $goal->name,
            'type'              => $goal->type,
            'target_amount'     => $goal->target_amount ? (float) $goal->target_amount : null,
            'target_percentage' => $goal->target_percentage ? (float) $goal->target_percentage : null,
            'target_monthly'    => $targetMonthly,
            'current_savings'   => (float) $goal->current_savings,
            'deadline'          => $goal->deadline?->format('Y-m-d'),
            'color'             => $goal->color,
            'notes'             => $goal->notes,
            'is_completed'      => $goal->is_completed,
            'pct'               => $pct,
            'days_remaining'    => $daysRemaining,
            'months_to_goal'    => $monthsToGoal,
        ];
    }

    private function avgMonthlySavings(int $userId, int $months): float
    {
        $total = 0;
        for ($i = 1; $i <= $months; $i++) {
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

            $total += max(0, $income - $spent);
        }

        return $months > 0 ? round($total / $months, 2) : 0;
    }
}