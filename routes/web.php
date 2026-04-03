<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReceiptOcrController;
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

    // Tax Summary
    Route::get('/tax-summary', [TaxSummaryController::class, 'index'])->name('tax-summary.index');
    Route::get('/tax-summary/{deductionType}', [TaxSummaryController::class, 'show'])->name('tax-summary.show');

    // Receipt OCR
    Route::post('/receipts/ocr', [ReceiptOcrController::class, 'extract'])->name('receipts.ocr');
});

require __DIR__ . '/settings.php';