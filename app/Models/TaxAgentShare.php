<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxAgentShare extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'year',
        'label',
        'expires_at',
        'last_accessed_at',
        'access_count',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count'     => 'integer',
        'year'             => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}