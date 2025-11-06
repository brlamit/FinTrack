<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
        'alert_thresholds',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'alert_thresholds' => 'array',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for the budget.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by period.
     */
    public function scopePeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Get the current spending for this budget.
     */
    public function getCurrentSpendingAttribute()
    {
        if (!$this->category_id) {
            // Overall budget - sum all expenses for the user in the period
            return Transaction::where('user_id', $this->user_id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
                ->sum('amount');
        }

        // Category-specific budget
        return Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
            ->sum('amount');
    }

    /**
     * Get the remaining amount for this budget.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->current_spending;
    }

    /**
     * Get the spending percentage.
     */
    public function getSpendingPercentageAttribute()
    {
        if ($this->amount == 0) return 0;
        return ($this->current_spending / $this->amount) * 100;
    }
}