<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'target_amount',
        'current_amount',
        'target_date',
        'status',
        'milestones',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'target_date' => 'date',
        'milestones' => 'array',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active goals.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include completed goals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->target_amount == 0) return 0;
        return ($this->current_amount / $this->target_amount) * 100;
    }

    /**
     * Get the remaining amount needed.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->target_amount - $this->current_amount;
    }

    /**
     * Check if the goal is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->current_amount >= $this->target_amount;
    }

    /**
     * Check if the goal is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->target_date < now() && !$this->isCompleted();
    }
}