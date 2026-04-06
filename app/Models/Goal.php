<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'target_amount',
        'target_percentage',
        'current_savings',
        'deadline',
        'color',
        'notes',
        'is_completed',
    ];

    protected $casts = [
        'target_amount'     => 'decimal:2',
        'target_percentage' => 'decimal:2',
        'current_savings'   => 'decimal:2',
        'deadline'          => 'date',
        'is_completed'      => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Progress percentage (0-100) toward the goal.
     * For target_amount: current_savings / target_amount
     * For monthly_percentage: this month's net savings / target monthly savings
     */
    public function progressPct(float $monthlyIncome = 0): int
    {
        if ($this->type === 'target_amount') {
            if (!$this->target_amount || $this->target_amount <= 0) return 0;
            return min(100, (int) round(($this->current_savings / $this->target_amount) * 100));
        }

        // monthly_percentage
        if (!$this->target_percentage || $monthlyIncome <= 0) return 0;
        $targetMonthly = ($this->target_percentage / 100) * $monthlyIncome;
        return min(100, (int) round(($this->current_savings / $targetMonthly) * 100));
    }

    /**
     * Days remaining until deadline.
     */
    public function daysRemaining(): ?int
    {
        if (!$this->deadline) return null;
        return max(0, (int) now()->startOfDay()->diffInDays($this->deadline, false));
    }
}