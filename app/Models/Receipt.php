<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'expense_id',
        'filename',
        'original_name',
        'type',
        'mime_type',
        'file_path',
        'is_indexed',
        'ocr_text',
    ];

    protected $casts = [
        'is_indexed' => 'boolean',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}