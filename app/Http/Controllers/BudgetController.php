<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();
        $month  = now()->month;
        $year   = now()->year;

        $budgets = Budget::with('category')
            ->where('user_id', $userId)
            ->get()
            ->map(fn ($budget) => $this->formatBudget($budget, $userId, $month, $year));

        return Inertia::render('Budgets/Index', [
            'budgets'      => $budgets,
            'currentMonth' => now()->format('F Y'),
        ]);
    }

    public function create(): Response
    {
        // Get categories not already budgeted by this user
        $usedCategoryIds = Budget::where('user_id', Auth::id())
            ->whereNotNull('category_id')
            ->pluck('category_id');

        $hasOverall = Budget::where('user_id', Auth::id())
            ->whereNull('category_id')
            ->exists();

        $categories = Category::where('is_active', true)
            ->whereNotIn('id', $usedCategoryIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'color']);

        return Inertia::render('Budgets/Create', [
            'categories' => $categories,
            'hasOverall' => $hasOverall,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'amount'      => 'required|numeric|min:1',
            'name'        => 'nullable|string|max:100',
        ]);

        // Prevent duplicate
        $exists = Budget::where('user_id', Auth::id())
            ->where('category_id', $validated['category_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->withErrors(['category_id' => 'A budget for this category already exists.']);
        }

        $budget = Budget::create([
            'user_id'     => Auth::id(),
            'category_id' => $validated['category_id'] ?? null,
            'amount'      => $validated['amount'],
            'name'        => $validated['name'] ?? null,
        ]);

        return redirect()->route('budgets.show', $budget)
            ->with('success', 'Budget created.');
    }

    public function show(Budget $budget): Response
    {
        abort_if($budget->user_id !== Auth::id(), 403);

        $userId = Auth::id();
        $month  = now()->month;
        $year   = now()->year;

        // Last 6 months of spending for this budget
        $history = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $spent = $this->getSpentForBudget($budget, $userId, $date->month, $date->year);
            $history->push([
                'label'  => $date->format('M Y'),
                'month'  => $date->format('Y-m'),
                'spent'  => $spent,
                'budget' => (float) $budget->amount,
                'pct'    => $budget->amount > 0 ? min(100, round(($spent / $budget->amount) * 100)) : 0,
            ]);
        }

        return Inertia::render('Budgets/Show', [
            'budget'       => $this->formatBudget($budget, $userId, $month, $year),
            'history'      => $history,
            'currentMonth' => now()->format('F Y'),
        ]);
    }

    public function edit(Budget $budget): Response
    {
        abort_if($budget->user_id !== Auth::id(), 403);

        $budget->load('category');

        return Inertia::render('Budgets/Edit', [
            'budget' => [
                'id'          => $budget->id,
                'category_id' => $budget->category_id,
                'amount'      => $budget->amount,
                'name'        => $budget->name ?? '',
                'label'       => $budget->getLabel(),
                'is_overall'  => $budget->isOverall(),
            ],
        ]);
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'name'   => 'nullable|string|max:100',
        ]);

        $budget->update([
            'amount' => $validated['amount'],
            'name'   => $validated['name'] ?? null,
        ]);

        return redirect()->route('budgets.show', $budget)
            ->with('success', 'Budget updated.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== Auth::id(), 403);
        $budget->delete();

        return redirect()->route('budgets.index')
            ->with('success', 'Budget deleted.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatBudget(Budget $budget, int $userId, int $month, int $year): array
    {
        $budget->loadMissing('category');
        $spent    = $this->getSpentForBudget($budget, $userId, $month, $year);
        $limit    = (float) $budget->amount;
        $pct      = $limit > 0 ? min(100, round(($spent / $limit) * 100)) : 0;
        $remaining = max(0, $limit - $spent);

        return [
            'id'          => $budget->id,
            'label'       => $budget->getLabel(),
            'is_overall'  => $budget->isOverall(),
            'category_id' => $budget->category_id,
            'category'    => $budget->category ? [
                'name'  => $budget->category->name,
                'color' => $budget->category->color,
                'code'  => $budget->category->code,
            ] : null,
            'amount'    => $limit,
            'spent'     => $spent,
            'remaining' => $remaining,
            'pct'       => $pct,
            'status'    => $pct >= 100 ? 'exceeded' : ($pct >= 80 ? 'warning' : 'ok'),
        ];
    }

    private function getSpentForBudget(Budget $budget, int $userId, int $month, int $year): float
    {
        $query = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year);

        if (!$budget->isOverall()) {
            $query->where('category_id', $budget->category_id);
        }

        return (float) $query->sum('amount');
    }
}