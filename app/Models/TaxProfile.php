<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxProfile extends Model
{
    protected $fillable = [
        'user_id', 'has_spouse', 'spouse_disabled', 'self_disabled',
        'children', 'has_parents_medical', 'has_disabled_equipment',
    ];

    protected $casts = [
        'has_spouse'             => 'boolean',
        'spouse_disabled'        => 'boolean',
        'self_disabled'          => 'boolean',
        'children'               => 'array',
        'has_parents_medical'    => 'boolean',
        'has_disabled_equipment' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateReliefs(): array
    {
        $items = [];
        $items[] = ['label' => 'Self relief', 'amount' => 9000];

        if ($this->self_disabled) {
            $items[] = ['label' => 'Disabled self (additional)', 'amount' => 7000];
        }
        if ($this->has_spouse) {
            $items[] = ['label' => 'Spouse relief', 'amount' => 4000];
            if ($this->spouse_disabled) {
                $items[] = ['label' => 'Disabled spouse (additional)', 'amount' => 6000];
            }
        }

        foreach ($this->children ?? [] as $i => $child) {
            $n        = $i + 1;
            $type     = $child['type'] ?? 'u18';
            $disabled = (bool) ($child['disabled'] ?? false);
            $amount   = match($type) { 'degree' => 8000, default => 2000 };
            $label    = match($type) {
                'u18'       => "Child {$n} (below 18)",
                'predegree' => "Child {$n} (18+, pre-degree)",
                'degree'    => "Child {$n} (18+, diploma or higher)",
                default     => "Child {$n}",
            };
            $items[] = ['label' => $label, 'amount' => $amount];
            if ($disabled) {
                $items[] = ['label' => "Child {$n} — disabled", 'amount' => 8000];
            }
        }

        if ($this->has_parents_medical) {
            $items[] = ['label' => 'Medical expenses for parents', 'amount' => 8000];
        }
        if ($this->has_disabled_equipment) {
            $items[] = ['label' => 'Disability supporting equipment', 'amount' => 6000];
        }

        return ['items' => $items, 'total' => array_sum(array_column($items, 'amount'))];
    }
}