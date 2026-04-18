<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TaxProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TaxProfileController extends Controller
{
    public function edit(): Response
    {
        $profile = TaxProfile::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'has_spouse'             => false,
                'spouse_disabled'        => false,
                'self_disabled'          => false,
                'children'               => [],
                'has_parents_medical'    => false,
                'has_disabled_equipment' => false,
            ]
        );

        return Inertia::render('settings/TaxProfile', [
            'profile' => [
                'has_spouse'             => $profile->has_spouse,
                'spouse_disabled'        => $profile->spouse_disabled,
                'self_disabled'          => $profile->self_disabled,
                'children'               => $profile->children ?? [],
                'has_parents_medical'    => $profile->has_parents_medical,
                'has_disabled_equipment' => $profile->has_disabled_equipment,
            ],
            'reliefs' => $profile->calculateReliefs(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'has_spouse'             => 'boolean',
            'spouse_disabled'        => 'boolean',
            'self_disabled'          => 'boolean',
            'children'               => 'nullable|array',
            'children.*.type'        => 'required|in:u18,predegree,degree',
            'children.*.disabled'    => 'boolean',
            'has_parents_medical'    => 'boolean',
            'has_disabled_equipment' => 'boolean',
        ]);

        $profile = TaxProfile::firstOrCreate(['user_id' => Auth::id()]);
        $profile->update($validated);

        return back()->with('success', 'Tax profile updated.');
    }
}