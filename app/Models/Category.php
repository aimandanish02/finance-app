<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'is_tax_deductible',
        'is_active',
    ];

    protected $casts = [
        'is_tax_deductible' => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}