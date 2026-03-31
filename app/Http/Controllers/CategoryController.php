<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::withCount('expenses')
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id'               => $c->id,
                'name'             => $c->name,
                'code'             => $c->code,
                'color'            => $c->color,
                'is_tax_deductible'=> $c->is_tax_deductible,
                'is_active'        => $c->is_active,
                'deduction_type'   => $c->deduction_type,
                'annual_limit'     => $c->annual_limit,
                'description'      => $c->description,
                'expenses_count'   => $c->expenses_count,
            ]);

        return Inertia::render('Categories/Index', [
            'categories'     => $categories,
            'deductionTypes' => Category::deductionTypes(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Categories/Create', [
            'deductionTypes' => Category::deductionTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:50',
            'code'              => 'required|string|max:50|unique:categories,code',
            'color'             => 'nullable|string|max:50',
            'is_tax_deductible' => 'boolean',
            'is_active'         => 'boolean',
            'deduction_type'    => 'required|string',
            'annual_limit'      => 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:500',
        ]);

        $category = Category::create($validated);

        return redirect()->route('categories.show', $category)
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): Response
    {
        $category->loadCount('expenses');

        $expenses = $category->expenses()
            ->latest('expense_date')
            ->limit(10)
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'title'        => $e->title,
                'amount'       => (float) $e->amount,
                'type'         => $e->type,
                'expense_date' => $e->expense_date->format('Y-m-d'),
                'description'  => $e->description,
            ]);

        $totals = $category->expenses()
            ->selectRaw("
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses,
                SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as total_income
            ")
            ->first();

        return Inertia::render('Categories/Show', [
            'category' => [
                'id'               => $category->id,
                'name'             => $category->name,
                'code'             => $category->code,
                'color'            => $category->color,
                'is_tax_deductible'=> $category->is_tax_deductible,
                'is_active'        => $category->is_active,
                'deduction_type'   => $category->deduction_type,
                'deduction_label'  => Category::deductionTypes()[$category->deduction_type] ?? $category->deduction_type,
                'annual_limit'     => $category->annual_limit,
                'description'      => $category->description,
                'expenses_count'   => $category->expenses_count,
                'created_at'       => $category->created_at->format('Y-m-d H:i'),
            ],
            'expenses'      => $expenses,
            'totalExpenses' => (float) $totals->total_expenses,
            'totalIncome'   => (float) $totals->total_income,
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Categories/Edit', [
            'category' => [
                'id'               => $category->id,
                'name'             => $category->name,
                'code'             => $category->code,
                'color'            => $category->color ?? '#6366f1',
                'is_tax_deductible'=> $category->is_tax_deductible,
                'is_active'        => $category->is_active,
                'deduction_type'   => $category->deduction_type,
                'annual_limit'     => $category->annual_limit,
                'description'      => $category->description ?? '',
            ],
            'deductionTypes' => Category::deductionTypes(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:50',
            'code'              => 'required|string|max:50|unique:categories,code,' . $category->id,
            'color'             => 'nullable|string|max:50',
            'is_tax_deductible' => 'boolean',
            'is_active'         => 'boolean',
            'deduction_type'    => 'required|string',
            'annual_limit'      => 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:500',
        ]);

        $category->update($validated);

        return redirect()->route('categories.show', $category)
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->expenses()->exists()) {
            return back()->with('error', 'Cannot delete a category that has expenses linked to it.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}