<?php

use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AutoCategorizeController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\PdfReportController;
use App\Http\Controllers\ReceiptOcrController;
use App\Http\Controllers\Settings\TaxProfileController;
use App\Http\Controllers\SpendingController;
use App\Http\Controllers\TaxAgentController;
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
    Route::get('/tax-summary/compare', [TaxSummaryController::class, 'compare'])->name('tax-summary.compare');
    Route::get('/tax-summary/{deductionType}', [TaxSummaryController::class, 'show'])->name('tax-summary.show');

    // PDF & Export
    Route::get('/tax-summary/export/pdf', [PdfReportController::class, 'taxSummary'])->name('tax-summary.pdf');
    Route::get('/tax-summary/export/schedule-a', [PdfReportController::class, 'exportScheduleA'])->name('tax-summary.schedule-a');

    // Audit trail
    Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');

    // Tax agent sharing
    Route::get('/tax-agent', [TaxAgentController::class, 'index'])->name('tax-agent.index');
    Route::post('/tax-agent', [TaxAgentController::class, 'store'])->name('tax-agent.store');
    Route::delete('/tax-agent/{taxAgentShare}', [TaxAgentController::class, 'destroy'])->name('tax-agent.destroy');

    // Receipt OCR
    Route::post('/receipts/ocr', [ReceiptOcrController::class, 'extract'])->name('receipts.ocr');

    // Auto-categorize
    Route::post('/expenses/categorize', [AutoCategorizeController::class, 'suggest'])->name('expenses.categorize');

    // Tax profile settings
    Route::get('/settings/tax-profile', [TaxProfileController::class, 'edit'])->name('settings.tax-profile.edit');
    Route::put('/settings/tax-profile', [TaxProfileController::class, 'update'])->name('settings.tax-profile.update');
});

require __DIR__ . '/settings.php';

// Public tax agent share link (no auth required)
Route::get('/share/tax/{token}', [TaxAgentController::class, 'show'])->name('tax-agent.show');