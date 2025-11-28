<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'category_id',
        'amount',
        'description',
        'transaction_date',
        'type',
        'receipt_id',
        'tags',
        'is_recurring',
        'recurring_frequency',
        'recurring_end_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'tags' => 'array',
        'is_recurring' => 'boolean',
        'recurring_end_date' => 'date',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for the transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the group associated with the transaction.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the receipt for the transaction.
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    /**
     * Scope a query to only include income transactions.
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope a query to only include expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope a query to only include recurring transactions.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Get the formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute()
    {
        return ($this->type === 'expense' ? '-' : '+') . '$' . number_format((float) $this->amount, 2);
    }

    /**
     * Boot model events to evaluate budgets when transactions change.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function (Transaction $transaction) {
            try {
                if ($transaction->user) {
                    $transaction->user->evaluateBudgetsForTransaction($transaction, 0);
                }
            } catch (\Throwable $e) {
                // swallow to avoid breaking transaction flow; logging could be added
            }
        });

        static::updated(function (Transaction $transaction) {
            try {
                $original = $transaction->getOriginal('amount') ?? 0;
                if ($transaction->user) {
                    $transaction->user->evaluateBudgetsForTransaction($transaction, (float) $original);
                }
            } catch (\Throwable $e) {
                // swallow
            }
        });
    }
}