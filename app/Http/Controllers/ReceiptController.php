<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    public function index($expenseId)
    {
        $expense = Expense::findOrFail($expenseId);

        $receipts = Receipt::where('expense_id', $expenseId)->get();

        return response()->json($receipts);
    }

    public function store(Request $request, $expenseId)
    {
        $expense = Expense::findOrFail($expenseId);

        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'original_name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'mime_type' => 'required|string|max:255',
            'file_path' => 'required|string|max:255',
            'is_indexed' => 'boolean',
            'ocr_text' => 'nullable:text',
        ]);

        $receipt = Receipt::create($validated);

        return response()->json($receipt, 201);
    }

    public function show($expenseId, $receiptId)
    {
        $receipt = Receipt::where('expense_id', $expenseId)->findOrFail($receiptId);

        return response()->json($receipt);
    }

    public function update(Request $request, $expenseId, $receiptId)
    {
        $receipt = Receipt::where('expense_id', $expenseId)->findOrFail($receiptId);

        $validated = $request->validate([
            'filename' => 'sometimes|string|max:255',
            'original_name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:50',
            'mime_type' => 'sometimes|string|max:255',
            'file_path' => 'sometimes|string|max:255',
            'is_indexed' => 'boolean',
            'ocr_text' => 'nullable:text',
        ]);

        $receipt->update($validated);

        return response()->json($receipt);
    }

    public function destroy($expenseId, $receiptId)
    {
        $receipt = Receipt::where('expense_id', $expenseId)->findOrFail($receiptId);

        // Delete file
        if (Storage::disk('public')->exists($receipt->file_path)) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        $receipt->delete();

        return response()->json(null, 204);
    }
}
