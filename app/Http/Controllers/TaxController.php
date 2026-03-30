$taxYear = request('tax_year') ?? date('Y');
// Filter expenses by tax_year
$expenses = Expense::where('user_id', $request->user()->id)
    ->where('tax_year', $taxYear)
    ->with(['category'])
    ->get();

$totalExpenses = $expenses->sum('amount');
$totalTaxDeductible = $expenses->where('category.is_tax_deductible', true)->sum('amount');

return response()->json([
    'tax_year' => $taxYear,
    'total_spent' => $totalExpenses,
    'tax_deductible_potential' => $totalTaxDeductible,
    'personal_expenses' => $totalExpenses - $totalTaxDeductible
]);