<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Receipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        $expenses = Expense::with('category', 'receipts')
            ->where('user_id', Auth::id())
            ->latest('expense_date')
            ->paginate(15)
            ->through(fn ($expense) => [
                'id'           => $expense->id,
                'title'        => $expense->title,
                'amount'       => $expense->amount,
                'type'         => $expense->type,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'description'  => $expense->description,
                'notes'        => $expense->notes,
                'category'     => $expense->category ? [
                    'id'   => $expense->category->id,
                    'name' => $expense->category->name,
                    'code' => $expense->category->code,
                    'color' => $expense->category->color,
                ] : null,
                'receipts_count' => $expense->receipts->count(),
            ]);

        $totals = Expense::where('user_id', Auth::id())
            ->selectRaw("
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income
            ")
            ->first();

        return Inertia::render('Expenses/Index', [
            'expenses'      => $expenses,
            'totalExpenses' => (float) $totals->total_expenses,
            'totalIncome'   => (float) $totals->total_income,
        ]);
    }

    public function create(): Response
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'color']);

        return Inertia::render('Expenses/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:100',
            'category_id'  => 'required|exists:categories,id',
            'amount'       => 'required|numeric|min:0',
            'type'         => 'required|in:income,expense',
            'expense_date' => 'required|date',
            'description'  => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
            'receipts'     => 'nullable|array',
            'receipts.*'   => 'file|max:10240|mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp,application/pdf',
        ]);

        DB::beginTransaction();

        try {
            $expense = Expense::create([
                'user_id'      => Auth::id(),
                'category_id'  => $validated['category_id'],
                'title'        => $validated['title'],
                'amount'       => $validated['amount'],
                'type'         => $validated['type'],
                'expense_date' => $validated['expense_date'],
                'description'  => $validated['description'] ?? null,
                'notes'        => $validated['notes'] ?? null,
                'is_active'    => true,
            ]);

            if ($request->hasFile('receipts')) {
                foreach ($request->file('receipts') as $file) {
                    $uploadPath = $file->store('expenses/receipts', 'public');

                    Receipt::create([
                        'expense_id'    => $expense->id,
                        'filename'      => basename($uploadPath),
                        'original_name' => $file->getClientOriginalName(),
                        'type'          => $file->guessExtension() ?: 'file',
                        'mime_type'     => $file->getMimeType(),
                        'file_path'     => $uploadPath,
                        'is_indexed'    => false,
                        'ocr_text'      => null,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('expenses.index')
                ->with('success', 'Expense created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Failed to create expense. Please try again.');
        }
    }

    public function show(Expense $expense): Response
    {
        // Ensure the expense belongs to the authenticated user
        abort_if($expense->user_id !== Auth::id(), 403);

        $expense->load('category', 'receipts');

        return Inertia::render('Expenses/Show', [
            'expense' => [
                'id'           => $expense->id,
                'title'        => $expense->title,
                'amount'       => $expense->amount,
                'type'         => $expense->type,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'description'  => $expense->description,
                'notes'        => $expense->notes,
                'is_active'    => $expense->is_active,
                'category'     => $expense->category ? [
                    'id'    => $expense->category->id,
                    'name'  => $expense->category->name,
                    'code'  => $expense->category->code,
                    'color' => $expense->category->color,
                ] : null,
                'receipts' => $expense->receipts->map(fn ($r) => [
                    'id'            => $r->id,
                    'original_name' => $r->original_name,
                    'mime_type'     => $r->mime_type,
                    'url'           => asset('storage/' . $r->file_path),
                ]),
                'created_at' => $expense->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function edit(Expense $expense): Response
    {
        abort_if($expense->user_id !== Auth::id(), 403);

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'color']);

        return Inertia::render('Expenses/Edit', [
            'expense' => [
                'id'           => $expense->id,
                'title'        => $expense->title,
                'amount'       => $expense->amount,
                'type'         => $expense->type,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'description'  => $expense->description,
                'notes'        => $expense->notes,
                'category_id'  => $expense->category_id,
            ],
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        abort_if($expense->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title'        => 'required|string|max:100',
            'category_id'  => 'required|exists:categories,id',
            'amount'       => 'required|numeric|min:0',
            'type'         => 'required|in:income,expense',
            'expense_date' => 'required|date',
            'description'  => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
            'receipts'     => 'nullable|array',
            'receipts.*'   => 'file|max:10240|mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp,application/pdf',
        ]);

        DB::beginTransaction();

        try {
            $expense->update([
                'title'        => $validated['title'],
                'category_id'  => $validated['category_id'],
                'amount'       => $validated['amount'],
                'type'         => $validated['type'],
                'expense_date' => $validated['expense_date'],
                'description'  => $validated['description'] ?? null,
                'notes'        => $validated['notes'] ?? null,
            ]);

            if ($request->hasFile('receipts')) {
                foreach ($request->file('receipts') as $file) {
                    $uploadPath = $file->store('expenses/receipts', 'public');

                    Receipt::create([
                        'expense_id'    => $expense->id,
                        'filename'      => basename($uploadPath),
                        'original_name' => $file->getClientOriginalName(),
                        'type'          => $file->guessExtension() ?: 'file',
                        'mime_type'     => $file->getMimeType(),
                        'file_path'     => $uploadPath,
                        'is_indexed'    => false,
                        'ocr_text'      => null,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('expenses.show', $expense)
                ->with('success', 'Expense updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Failed to update expense. Please try again.');
        }
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        abort_if($expense->user_id !== Auth::id(), 403);

        foreach ($expense->receipts as $receipt) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}