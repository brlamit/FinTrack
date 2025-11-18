<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use App\Services\AvatarUrlResolver;

/*
|--------------------------------------------------------------------------
| User Model - Refactored & Optimized
|--------------------------------------------------------------------------
|
| Features:
| • Smart avatar URL resolution (local fallback + Supabase support)
| • Robust budget threshold alerts with delta handling
| • No duplicate alerts within short time
| • Clean, testable, maintainable code
|
*/

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'username',
        'password',
        'phone',
        'avatar',
        'avatar_disk',
        'role',
        'invited_by',
        'invited_at',
        'status',
        'password_changed_at',
        'first_login_done',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'invited_at'           => 'datetime',
            'password_changed_at'  => 'datetime',
            'password'             => 'hashed',
            'first_login_done'     => 'boolean',
        ];
    }

    // =====================================================================
    // Avatar Handling
    // =====================================================================

    /**
     * Get the public URL for the user's avatar.
     */
   // Smart Avatar URL — works with Supabase + local fallback
   // app/Models/User.php
public function getAvatarAttribute($value)
{
    if (!$value || $value === 'default.png') {
        return asset('assets/uploads/images/default.png');
    }

    $disk = $this->avatar_disk ?? 'public';

    if ($disk === 'supabase') {
        return env('SUPABASE_PUBLIC_URL') . '/' . ltrim($value, '/');
    }

    return Storage::disk($disk)->url($value);
}

    /**
     * Helper: Raw avatar path (without accessor)
     */
    public function getRawAvatarPath(): ?string
    {
        return $this->getRawOriginal('avatar');
    }

    // =====================================================================
    // Relationships
    // =====================================================================

    public function otps()
    {
        return $this->hasMany(UserOtp::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function notifications()
    {
        return $this->hasMany(AppNotification::class); // Renamed model recommended
    }

    public function syncTokens()
    {
        return $this->hasMany(SyncToken::class);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members');
    }

    public function ownedGroups()
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    // =====================================================================
    // Budget Alert Evaluation
    // =====================================================================

    /**
     * Evaluate all active budgets when an expense transaction is created/updated.
     * Fires notifications only when crossing thresholds.
     */
    public function evaluateBudgetsForTransaction(Transaction $transaction, float $previousAmount = 0.0): void
    {
        if ($transaction->type !== 'expense') {
            return;
        }

        $delta = $transaction->amount - $previousAmount;

        // Only increases in spending can trigger new alerts
        if ($delta <= 0) {
            return;
        }

        $date = $transaction->transaction_date instanceof Carbon
            ? $transaction->transaction_date->format('Y-m-d')
            : $transaction->transaction_date;

        $affectedBudgets = $this->budgets()
            ->active()
            ->where(function ($query) use ($transaction) {
                $query->whereNull('category_id')
                      ->orWhere('category_id', $transaction->category_id);
            })
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        foreach ($affectedBudgets as $budget) {
            $this->checkBudgetThresholds($budget, $delta);
        }
    }

    /**
     * Check if any alert threshold has been crossed for a single budget.
     */
    protected function checkBudgetThresholds(Budget $budget, float $delta): void
    {
        // Ensure current_spending is up-to-date (important in queued jobs or imports)
        $budget->refreshCurrentSpending();
        $total = (float) $budget->amount;

        if ($total <= 0) {
            return;
        }

        $previousSpending = $budget->current_spending - $delta;
        $currentSpending  = $budget->current_spending;

        $previousPercent = $previousSpending > 0 ? ($previousSpending / $total) * 100 : 0;
        $currentPercent  = ($currentSpending / $total) * 100;

        $thresholds = is_array($budget->alert_thresholds)
            ? $budget->alert_thresholds
            : json_decode($budget->alert_thresholds, true) ?? [];

        foreach ($thresholds as $threshold) {
            $threshold = (float) $threshold;

            if ($previousPercent < $threshold && $currentPercent >= $threshold) {
                $this->notifyBudgetThresholdExceeded($budget, $threshold, $currentPercent, $currentSpending, $total);
            }
        }
    }

    /**
     * Create a budget alert notification (with deduplication).
     */
    protected function notifyBudgetThresholdExceeded(
        Budget $budget,
        float $threshold,
        float $currentPercent,
        float $currentSpending,
        float $total
    ): void {
        // Prevent spam: same budget + threshold within last hour
        $recent = AppNotification::where('user_id', $this->id)
            ->where('type', 'budget_alert')
            ->whereJsonContains('data->budget_id', $budget->id)
            ->whereJsonContains('data->threshold', $threshold)
            ->where('created_at', '>', now()->subHour())
            ->exists();

        if ($recent) {
            return;
        }

        AppNotification::create([
            'user_id' => $this->id,
            'title'   => "Budget Alert: {$budget->name}",
            'message' => sprintf(
                "You've reached %d%% of your budget '%s' (%s spent of %s).",
                round($currentPercent),
                $budget->name,
                number_format($currentSpending, 2),
                number_format($total, 2)
            ),
            'type' => 'budget_alert',
            'data' => [
                'budget_id'        => $budget->id,
                'threshold'        => $threshold,
                'current_percent'  => round($currentPercent, 2),
                'current_spending' => $currentSpending,
                'budget_total'     => $total,
            ],
            'is_read' => false,
        ]);
    }

    // =====================================================================
    // Scopes & Accessors
    // =====================================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}