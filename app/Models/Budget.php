<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'name',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Whether this is the overall budget (no category).
     */
    public function isOverall(): bool
    {
        return $this->category_id === null;
    }

    /**
     * Get the display label for this budget.
     */
    public function getLabel(): string
    {
        if ($this->name) return $this->name;
        if ($this->isOverall()) return 'Overall Monthly Budget';
        return $this->category?->name ?? 'Unknown Category';
    }
}