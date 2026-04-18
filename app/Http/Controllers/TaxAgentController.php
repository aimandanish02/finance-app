<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\TaxAgentShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaxAgentController extends Controller
{
    // ── Authenticated: manage share links ─────────────────────────────────

    public function index(): Response
    {
        $shares = TaxAgentShare::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($s) => [
                'id'               => $s->id,
                'year'             => $s->year,
                'label'            => $s->label ?? "YA{$s->year} Report",
                'token'            => $s->token,
                'url'              => url("/share/tax/{$s->token}"),
                'expires_at'       => $s->expires_at?->format('Y-m-d'),
                'is_expired'       => $s->isExpired(),
                'last_accessed_at' => $s->last_accessed_at?->format('d M Y H:i'),
                'access_count'     => $s->access_count,
                'created_at'       => $s->created_at->format('d M Y'),
            ]);

        $availableYears = Expense::where('user_id', Auth::id())
            ->selectRaw('YEAR(expense_date) as year')
            ->groupBy('year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y);

        if (!$availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }

        return Inertia::render('TaxAgent/Index', [
            'shares'         => $shares,
            'availableYears' => $availableYears->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year'       => 'required|integer|min:2020|max:2030',
            'label'      => 'nullable|string|max:100',
            'expires_in' => 'nullable|integer|in:7,30,90',
        ]);

        $share = TaxAgentShare::create([
            'user_id'    => Auth::id(),
            'token'      => Str::random(48),
            'year'       => $validated['year'],
            'label'      => $validated['label'] ?? null,
            'expires_at' => isset($validated['expires_in'])
                ? now()->addDays($validated['expires_in'])
                : null,
        ]);

        return back()->with('success', 'Share link created.');
    }

    public function destroy(TaxAgentShare $taxAgentShare): RedirectResponse
    {
        abort_if($taxAgentShare->user_id !== Auth::id(), 403);
        $taxAgentShare->delete();

        return back()->with('success', 'Share link deleted.');
    }

    // ── Public: read-only report via token (no auth required) ─────────────

    public function show(string $token): Response|\Illuminate\Http\Response
    {
        $share = TaxAgentShare::where('token', $token)->firstOrFail();

        if ($share->isExpired()) {
            return Inertia::render('TaxAgent/Expired');
        }

        // Track access
        $share->increment('access_count');
        $share->update(['last_accessed_at' => now()]);

        $year   = $share->year;
        $userId = $share->user_id;

        $expenses = Expense::with('category')
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereYear('expense_date', $year)
            ->whereHas('category', fn ($q) => $q->where('is_tax_deductible', true))
            ->get();

        $breakdown = $expenses
            ->groupBy(fn ($e) => $e->category->deduction_type)
            ->map(function ($items, $type) {
                $category    = $items->first()->category;
                $totalSpent  = (float) $items->sum('amount');
                $annualLimit = $category->annual_limit ? (float) $category->annual_limit : null;
                $claimable   = $annualLimit !== null ? min($totalSpent, $annualLimit) : $totalSpent;

                return [
                    'deduction_type'  => $type,
                    'deduction_label' => Category::deductionTypes()[$type] ?? $type,
                    'total_spent'     => $totalSpent,
                    'annual_limit'    => $annualLimit,
                    'claimable'       => $claimable,
                    'over_limit'      => $annualLimit !== null && $totalSpent > $annualLimit,
                    'entries_count'   => $items->count(),
                ];
            })
            ->sortByDesc('claimable')
            ->values();

        return Inertia::render('TaxAgent/Report', [
            'year'           => $year,
            'label'          => $share->label ?? "YA{$year} Tax Deduction Report",
            'breakdown'      => $breakdown,
            'totalClaimable' => round($breakdown->sum('claimable'), 2),
            'generatedAt'    => now()->format('d M Y, H:i'),
            'expiresAt'      => $share->expires_at?->format('d M Y'),
            'sharedBy'       => $share->user->name,
        ]);
    }
}