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
        if (empty($this->path)) {
            return $this->path;
        }

        // If the stored path is already a full URL, try to normalize it to include bucket when configured
        if (strpos($this->path, 'http://') === 0 || strpos($this->path, 'https://') === 0) {
            $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
            $diskConfig = config("filesystems.disks.{$disk}", []);
            $diskUrl = $diskConfig['url'] ?? null;
            $bucket = $diskConfig['bucket'] ?? env('SUPABASE_PUBLIC_BUCKET');

            if (!empty($diskUrl) && strpos($this->path, $diskUrl) === 0) {
                $relative = ltrim(substr($this->path, strlen($diskUrl)), '/');
                if (!empty($bucket) && strpos($relative, trim($bucket, '/')) !== 0) {
                    $encoded = implode('/', array_map('rawurlencode', explode('/', $relative)));
                    return rtrim($diskUrl, '/') . '/' . trim($bucket, '/') . '/' . $encoded;
                }
            }

            return $this->path;
        }

        // Path is a storage key; attempt to get a disk url (and ensure bucket included)
        $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
        $diskConfig = config("filesystems.disks.{$disk}", []);
        $diskUrl = $diskConfig['url'] ?? null;
        $bucket = $diskConfig['bucket'] ?? env('SUPABASE_PUBLIC_BUCKET');

        try {
            $generated = \Storage::disk($disk)->url($this->path);
        } catch (\Throwable $e) {
            $generated = null;
        }

        // If generated url doesn't include the bucket, rebuild using disk config + bucket
        if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
            $generated = null;
        }

        if (empty($generated) && !empty($diskUrl)) {
            $encodedKey = implode('/', array_map('rawurlencode', explode('/', $this->path)));
            if (!empty($bucket)) {
                $generated = rtrim($diskUrl, '/') . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
            } else {
                $generated = rtrim($diskUrl, '/') . '/' . ltrim($encodedKey, '/');
            }
        }

        return $generated ?: $this->path;
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