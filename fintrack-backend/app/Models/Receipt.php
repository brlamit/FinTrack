<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'mime_type',
        'path',
        'size',
        'ocr_data',
        'parsed_data',
        'processed',
    ];

    protected $casts = [
        'ocr_data' => 'array',
        'parsed_data' => 'array',
        'processed' => 'boolean',
        'size' => 'integer',
    ];

    /**
     * Get the user that owns the receipt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for the receipt.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the full URL for the receipt file.
     */
    public function getUrlAttribute()
    {
        return \Storage::disk('s3')->url($this->path);
    }

    /**
     * Scope a query to only include processed receipts.
     */
    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    /**
     * Scope a query to only include unprocessed receipts.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }
}