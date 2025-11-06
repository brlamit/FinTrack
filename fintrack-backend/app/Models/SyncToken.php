<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'token',
        'last_sync_at',
        'sync_state',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'sync_state' => 'array',
    ];

    /**
     * Get the user that owns the sync token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new sync token.
     */
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Update the last sync timestamp.
     */
    public function updateLastSync()
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Get the sync state for a specific entity.
     */
    public function getSyncState($entity)
    {
        return $this->sync_state[$entity] ?? null;
    }

    /**
     * Update the sync state for a specific entity.
     */
    public function updateSyncState($entity, $state)
    {
        $syncState = $this->sync_state ?? [];
        $syncState[$entity] = $state;
        $this->update(['sync_state' => $syncState]);
    }
}