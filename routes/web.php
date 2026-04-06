<?php

use App\Http\Controllers\AutoCategorizeController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ReceiptOcrController;
use App\Http\Controllers\SpendingController;
use App\Http\Controllers\TaxSummaryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Expenses
    Route::resource('expenses', ExpenseController::class);

    // Categories
    Route::resource('categories', CategoryController::class);

    // Budgets
    Route::resource('budgets', BudgetController::class);

    // Goals
    Route::resource('goals', GoalController::class);

    // Spending Intelligence
    Route::get('/spending', [SpendingController::class, 'index'])->name('spending.index');

    // Tax Summary
    Route::get('/tax-summary', [TaxSummaryController::class, 'index'])->name('tax-summary.index');
    Route::get('/tax-summary/{deductionType}', [TaxSummaryController::class, 'show'])->name('tax-summary.show');

    // Receipt OCR
    Route::post('/receipts/ocr', [ReceiptOcrController::class, 'extract'])->name('receipts.ocr');

    // Auto-categorize
    Route::post('/expenses/categorize', [AutoCategorizeController::class, 'suggest'])->name('expenses.categorize');
});

require __DIR__ . '/settings.php';