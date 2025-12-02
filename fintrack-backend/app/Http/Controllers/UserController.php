<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Group;
use App\Models\Budget;
use App\Models\Notification;
use App\Models\Category;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(private readonly OtpService $otpService)
    {
    }
    /**
     * Show user dashboard
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $chartPeriodSelection = (string) $request->input('chart_period', '3');
        $allowedPeriods = ['1', '3', '6', 'custom'];
        if (!in_array($chartPeriodSelection, $allowedPeriods, true)) {
            $chartPeriodSelection = '3';
        }

        $chartEndParam = $request->input('chart_end');
        $chartEndMonth = Carbon::now()->startOfMonth();
        if ($chartEndParam) {
            try {
                $candidateEnd = Carbon::createFromFormat('Y-m', $chartEndParam)->startOfMonth();
                if ($candidateEnd->lessThanOrEqualTo(Carbon::now()->startOfMonth())) {
                    $chartEndMonth = $candidateEnd;
                }
            } catch (\Exception $e) {
                // ignore invalid format and keep default
            }
        }

        $chartStartMonth = null;
        if ($chartPeriodSelection === 'custom') {
            $chartStartParam = $request->input('chart_start');
            if ($chartStartParam) {
                try {
                    $chartStartMonth = Carbon::createFromFormat('Y-m', $chartStartParam)->startOfMonth();
                } catch (\Exception $e) {
                    // fall back to default range
                }
            }

            if (!$chartStartMonth) {
                $chartStartMonth = $chartEndMonth->copy()->subMonths(2)->startOfMonth();
            }

            if ($chartStartMonth->greaterThan($chartEndMonth)) {
                $chartStartMonth = $chartEndMonth->copy();
            }
        } else {
            $monthsBack = (int) $chartPeriodSelection;
            $chartStartMonth = $chartEndMonth->copy()->subMonths(max($monthsBack - 1, 0))->startOfMonth();
        }

        $chartMonthsCount = max($chartStartMonth->diffInMonths($chartEndMonth) + 1, 1);

        $categoryChartType = $request->input('category_type', 'expense');
        if (!in_array($categoryChartType, ['income', 'expense'], true)) {
            $categoryChartType = 'expense';
        }

        $overallIncome = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->where('type', 'income')
            ->sum('amount');

        $overallExpense = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->where('type', 'expense')
            ->sum('amount');
        $totalNet = $overallIncome - $overallExpense;
        $overallExpenseRatio = $overallIncome > 0
            ? min(max(($overallExpense / max($overallIncome, 1)) * 100, 0), 100)
            : null;

        $startOfMonth = $chartEndMonth->copy()->startOfMonth();
        $endOfMonth = $chartEndMonth->copy()->endOfMonth();

        $monthlyIncome = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->where('type', 'income')
            ->whereBetween(DB::raw('COALESCE(transaction_date, created_at)'), [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->where('type', 'expense')
            ->whereBetween(DB::raw('COALESCE(transaction_date, created_at)'), [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthNet = $monthlyIncome - $monthlyExpense;

        $now = Carbon::now();
        $daysInSelectedMonth = $chartEndMonth->daysInMonth;
        $daysElapsed = $chartEndMonth->isSameMonth($now)
            ? min($now->day, $daysInSelectedMonth)
            : $daysInSelectedMonth;
        $averageDailyExpense = $daysElapsed > 0 ? $monthlyExpense / $daysElapsed : 0.0;
        $projectedExpense = $averageDailyExpense * $daysInSelectedMonth;
        $savingRate = $monthlyIncome > 0
            ? (($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100
            : null;

        $formatCurrency = static function (float $amount): string {
            $sign = $amount < 0 ? '-$' : '$';
            return $sign . number_format(abs($amount), 2);
        };

        $dateExpression = DB::raw('COALESCE(transaction_date, created_at)');

        $topExpenseCategoryRow = Transaction::query()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->where('type', 'expense')
            ->whereBetween($dateExpression, [$chartStartMonth->copy(), $chartEndMonth->copy()->endOfMonth()])
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        $topExpenseCategory = [
            'label' => 'No expenses recorded',
            'amount' => '$0.00',
            'share' => null,
        ];

        if ($topExpenseCategoryRow) {
            $category = Category::find($topExpenseCategoryRow->category_id);
            $topExpenseAmount = (float) ($topExpenseCategoryRow->total ?? 0);

            $topExpenseCategory = [
                'label' => $category?->name ?? 'Uncategorized',
                'amount' => $formatCurrency($topExpenseAmount),
                'share' => $monthlyExpense > 0
                    ? round(($topExpenseAmount / $monthlyExpense) * 100, 1)
                    : null,
            ];
        }

        $recentTransactions = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->with('category')
            ->orderByRaw('COALESCE(transaction_date, created_at) DESC')
            ->limit(5)
            ->get()
            ->map(function (Transaction $transaction) use ($formatCurrency) {
                $transactionDate = $transaction->transaction_date ?? $transaction->created_at;

                return [
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'category_name' => optional($transaction->category)->name,
                    'display_amount' => $formatCurrency((float) ($transaction->amount ?? 0)),
                    'display_date' => $transactionDate?->format('M d, Y'),
                    'is_income' => $transaction->type === 'income',
                ];
            });

        $categoryCount = Category::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->count();

        $totalBudgets = Budget::where('user_id', $user->id)->count();

        $activeBudgets = Budget::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('category')
            ->limit(3)
            ->get()
            ->map(function (Budget $budget) use ($formatCurrency) {
                $spent = $budget->current_spending;
                $limit = $budget->amount;
                $progress = $limit > 0
                    ? min(($spent / $limit) * 100, 100)
                    : 0;
                $remaining = max($limit - $spent, 0);

                $statusLabel = 'On track';
                $statusClass = 'text-success';

                if ($progress >= 100) {
                    $statusLabel = 'Over limit';
                    $statusClass = 'text-danger';
                } elseif ($progress >= 85) {
                    $statusLabel = 'Near limit';
                    $statusClass = 'text-warning';
                } elseif ($progress <= 40) {
                    $statusLabel = 'Plenty left';
                    $statusClass = 'text-info';
                }

                return [
                    'label' => $budget->category->name ?? 'General',
                    'spent_formatted' => $formatCurrency((float) $spent),
                    'limit_formatted' => $formatCurrency((float) $limit),
                    'progress' => round($progress, 1),
                    'remaining_formatted' => $formatCurrency((float) $remaining),
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                ];
            });

        $monthlyChart = [
            'labels' => [],
            'income' => [],
            'expense' => [],
        ];

        for ($offset = $chartMonthsCount - 1; $offset >= 0; $offset--) {
            $month = $chartEndMonth->copy()->subMonths($offset);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $monthlyChart['labels'][] = $month->format('M Y');
            $monthlyChart['income'][] = (float) Transaction::query()
                ->where('user_id', $user->id)
                ->whereNull('group_id')
                ->where('type', 'income')
                ->whereBetween($dateExpression, [$start, $end])
                ->sum('amount');

            $monthlyChart['expense'][] = (float) Transaction::query()
                ->where('user_id', $user->id)
                ->whereNull('group_id')
                ->where('type', 'expense')
                ->whereBetween($dateExpression, [$start, $end])
                ->sum('amount');
        }

        $categoryBreakdown = Transaction::query()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', $user->id)
            ->whereNull('group_id')
            ->where('type', $categoryChartType)
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $categoryMap = Category::query()
            ->whereIn('id', $categoryBreakdown->pluck('category_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $categoryChart = [
            'labels' => [],
            'values' => [],
            'colors' => [],
        ];

        $fallbackColors = ['#4C51BF', '#F56565', '#48BB78', '#ED8936', '#4299E1', '#38B2AC', '#D53F8C', '#718096'];
        $fallbackIndex = 0;

        foreach ($categoryBreakdown as $row) {
            $category = $categoryMap->get($row->category_id);
            $label = $category?->name ?? 'Uncategorized';
            $color = $category?->color;
            if (!$color) {
                $color = $fallbackColors[$fallbackIndex % count($fallbackColors)];
                $fallbackIndex++;
            }

            $categoryChart['labels'][] = $label;
            $categoryChart['values'][] = (float) $row->total;
            $categoryChart['colors'][] = $color;
        }

        $chartData = [
            'monthly' => $monthlyChart,
            'category' => $categoryChart,
        ];

        // Recent notifications and unread count for dashboard badge
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $recentNotifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $filters = [
            'chart_period' => $chartPeriodSelection,
            'category_type' => $categoryChartType,
            'chart_end' => $chartEndMonth->format('Y-m'),
            'chart_start' => $chartStartMonth->format('Y-m'),
        ];

        $chartWindowDescription = $chartStartMonth->equalTo($chartEndMonth)
            ? $chartEndMonth->format('M Y')
            : sprintf('%s – %s', $chartStartMonth->format('M Y'), $chartEndMonth->format('M Y'));

        $insights = [
            [
                'label' => 'Net this month',
                'value' => $formatCurrency((float) $monthNet),
                'description' => $monthNet >= 0
                    ? 'You are spending within your means this period.'
                    : 'Spending currently exceeds income for this period.',
                'class' => $monthNet >= 0 ? 'text-success' : 'text-danger',
            ],
            [
                'label' => 'Avg daily spend',
                'value' => $formatCurrency((float) $averageDailyExpense),
                'description' => $chartEndMonth->isSameMonth($now)
                    ? 'Based on spending recorded so far this month.'
                    : 'Average daily spending across the selected range.',
                'class' => 'text-primary',
            ],
            [
                'label' => 'Projected expense',
                'value' => $formatCurrency((float) $projectedExpense),
                'description' => 'Estimated total if your current pace continues.',
                'class' => 'text-warning',
            ],
        ];

        if ($savingRate !== null) {
            $insights[] = [
                'label' => 'Savings rate',
                'value' => number_format($savingRate, 1) . '%',
                'description' => 'Portion of income kept after expenses.',
                'class' => $savingRate >= 0 ? 'text-success' : 'text-danger',
            ];
        }

        $totalsDisplay = [
            'overall' => [
                'income' => $formatCurrency((float) $overallIncome),
                'expense' => $formatCurrency((float) $overallExpense),
                'net' => $formatCurrency((float) $totalNet),
                'net_class' => $totalNet >= 0 ? 'text-success' : 'text-danger',
            ],
            'month' => [
                'income' => $formatCurrency((float) $monthlyIncome),
                'expense' => $formatCurrency((float) $monthlyExpense),
                'net' => $formatCurrency((float) $monthNet),
                'net_class' => $monthNet >= 0 ? 'text-success' : 'text-danger',
            ],
        ];

        return view('user.dashboard', [
            'totals' => [
                'overall' => [
                    'income' => $overallIncome,
                    'expense' => $overallExpense,
                    'net' => $totalNet,
                ],
                'month' => [
                    'income' => $monthlyIncome,
                    'expense' => $monthlyExpense,
                    'net' => $monthNet,
                ],
            ],
            'monthRangeLabel' => [
                $startOfMonth->format('M d'),
                $endOfMonth->format('M d'),
            ],
            'totalsDisplay' => $totalsDisplay,
            'categoryCount' => $categoryCount,
            'budgetCount' => $totalBudgets,
            'recentTransactions' => $recentTransactions,
            'activeBudgets' => $activeBudgets,
            'chartData' => $chartData,
            'filters' => $filters,
            'chartWindowDescription' => $chartWindowDescription,
            'chartEndMonthLabel' => $chartEndMonth->format('F Y'),
            'insights' => $insights,
            'topExpenseCategory' => $topExpenseCategory,
            'overallExpenseRatio' => $overallExpenseRatio,
            'notifications' => $recentNotifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Show user profile
     */
    public function profile()
{
    $user = auth()->user();

    // Fix: Use proper query instead of non-existent scope
    $totalExpenses = Transaction::where('user_id', $user->id)
        ->whereNull('group_id')
        ->where('type', 'expense')
        ->sum('amount');

    $thisMonthExpenses = Transaction::where('user_id', $user->id)
        ->whereNull('group_id')
        ->where('type', 'expense')
        ->whereMonth('transaction_date', now()->month)
        ->whereYear('transaction_date', now()->year)
        ->sum('amount');

    $recentTransactions = Transaction::where('user_id', $user->id)
        ->whereNull('group_id')
        ->with('category')
        ->orderByDesc('transaction_date')
        ->orderByDesc('created_at')
        ->limit(6)
        ->get();

    return view('user.profile', [
        'totalExpenses'     => '$' . number_format((float) $totalExpenses, 2),
        'thisMonthExpenses' => '$' . number_format((float) $thisMonthExpenses, 2),
        'groupCount'        => $user->groups()->count(),
        'recentTransactions' => $recentTransactions,
        'userGroups'        => $user->groups()->withCount('members')->latest()->limit(6)->get(),
    ]);
}

    /**
     * Show edit profile form
     */
    public function edit()
    {
        return view('user.edit');
    }

    /**
     * Update user profile
     */
  public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'    => ['nullable', 'string', 'max:20'],
            'avatar'   => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB
        ]);

        // Handle avatar if uploaded
        if ($request->hasFile('avatar')) {
            $this->uploadAvatar($request->file('avatar'), $user);
            // The validated array contains the UploadedFile under 'avatar' — remove it so
            // we don't overwrite the saved avatar URL/path in the DB with the tmp path.
            if (array_key_exists('avatar', $validated)) {
                unset($validated['avatar']);
            }
        }

        $user->update($validated);

        return redirect()->route('user.profile')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * AJAX-only: Update avatar from profile page (click-to-upload)
     */
    public function updateAvatar(Request $request)
{
    $request->validate([
        'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:8192',
    ]);

    $user = $request->user();
    $file = $request->file('avatar');

    // Delete old avatar (handle stored URL or storage key)
    $oldPath = $user->getRawOriginal('avatar');
    $oldDisk = $user->getRawOriginal('avatar_disk') ?? 'public';

    if ($oldPath && $oldPath !== 'default.png') {
        try {
            if (strpos($oldPath, 'http://') === 0 || strpos($oldPath, 'https://') === 0) {
                $diskUrl = config("filesystems.disks.{$oldDisk}.url");
                if (!empty($diskUrl) && strpos($oldPath, $diskUrl) === 0) {
                    $maybeKey = ltrim(substr($oldPath, strlen($diskUrl)), '/');
                    Storage::disk($oldDisk)->delete($maybeKey);
                }
                // otherwise skip deletion since we can't derive a key
            } else {
                Storage::disk($oldDisk)->delete($oldPath);
            }
        } catch (\Throwable $e) {
            // Ignore if already deleted or deletion failed
        }
    }

    // Determine which disk to use for avatars (allow overriding via AVATAR_DISK env)
    $disk = env('AVATAR_DISK', config('filesystems.default'));
    $available = array_keys(config('filesystems.disks', []));
    if (!in_array($disk, $available, true)) {
        // fallback to configured default disk, then 'public'
        $disk = config('filesystems.default');
        if (!in_array($disk, $available, true)) {
            $disk = 'public';
        }
    }

    // Upload new avatar to configured disk
    $filename = $user->id . '_' . Str::random(20) . '.' . $file->extension();
    try {
        $imagePath = $file->storeAs("avatars/{$user->id}", $filename, $disk);
    } catch (\Throwable $e) {
        Log::error('Avatar upload failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Avatar upload failed. Check logs.'
        ], 500);
    }

    if ($imagePath === false || $imagePath === null) {
        Log::error('Avatar upload returned false/null for disk: ' . $disk);
        return response()->json([
            'success' => false,
            'message' => 'Avatar upload failed. Check logs.'
        ], 500);
    }

    // Make sure it's public if the disk supports visibility
    try {
        Storage::disk($disk)->setVisibility($imagePath, 'public');
    } catch (\Throwable $e) {
        // Ignore - some disks or buckets are already public or don't support visibility
    }

    // save object key and the public URL (include bucket in the public URL when available)
    $avatarToSave = $imagePath;
    $diskConfig = config("filesystems.disks.{$disk}", []);
    $generated = null;
    try {
        $generated = Storage::disk($disk)->url($imagePath);
    } catch (\Throwable $e) {
        $generated = null;
    }

    $bucket = $diskConfig['bucket'] ?? null;
    // If generated URL exists but is missing the bucket, discard it so we can build one that includes the bucket
    if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
        $generated = null;
    }

    if (empty($generated) && !empty($diskConfig['url'])) {
        $diskUrl = rtrim($diskConfig['url'], '/');
        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $imagePath)));
        if (!empty($bucket)) {
            $generated = $diskUrl . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
        } else {
            $generated = $diskUrl . '/' . ltrim($encodedKey, '/');
        }
    }

    if (!empty($generated)) {
        $avatarToSave = $generated;
    }

    // Save avatar (URL or key) + disk
    $user->update([
        'avatar'      => $avatarToSave,
        'avatar_disk' => $disk,
    ]);

    return response()->json([
        'success'    => true,
        'avatar_url' => $user->fresh()->avatar . '?v=' . now()->timestamp,
        'message'    => 'Avatar updated successfully!',
    ]);
}

    /**
     * Shared avatar upload logic (DRY)
     */
    private function uploadAvatar($file, $user)
    {
        $disk = env('AVATAR_DISK', config('filesystems.default'));
        $available = array_keys(config('filesystems.disks', []));
        if (!in_array($disk, $available, true)) {
            $disk = config('filesystems.default');
            if (!in_array($disk, $available, true)) {
                $disk = 'public';
            }
        }

        // Delete old avatar (if present) - handle stored URL or storage key
        $oldPath = $user->getRawOriginal('avatar');
        $oldDisk = $user->getRawOriginal('avatar_disk') ?? $disk;
        if (!in_array($oldDisk, $available, true)) {
            $oldDisk = $disk;
        }

        try {
            if ($oldPath) {
                if (strpos($oldPath, 'http://') === 0 || strpos($oldPath, 'https://') === 0) {
                    $diskUrl = config("filesystems.disks.{$oldDisk}.url");
                    if (!empty($diskUrl) && strpos($oldPath, $diskUrl) === 0) {
                        $maybeKey = ltrim(substr($oldPath, strlen($diskUrl)), '/');
                        if (Storage::disk($oldDisk)->exists($maybeKey)) {
                            Storage::disk($oldDisk)->delete($maybeKey);
                        }
                    }
                } else {
                    if (Storage::disk($oldDisk)->exists($oldPath)) {
                        Storage::disk($oldDisk)->delete($oldPath);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore deletion errors
        }

        // Generate safe filename
        $filename = $user->id . '_' . Str::random(20) . '.' . $file->getClientOriginalExtension();

        // Store in: avatars/{user_id}/filename.jpg
        try {
            $storedPath = $file->storeAs("avatars/{$user->id}", $filename, $disk);
        } catch (\Throwable $e) {
            Log::error('Avatar upload failed: ' . $e->getMessage());
            return;
        }

        if ($storedPath === false || $storedPath === null) {
            Log::error('Avatar upload returned false/null for disk: ' . $disk);
            return;
        }

        // Attempt to generate a public URL and store that; otherwise build from disk config url and keep storage key
        $avatarToSave = $storedPath;
        $generated = null;
        try {
            $generated = null;
            try {
                $generated = Storage::disk($disk)->url($storedPath);
            } catch (\Throwable $e) {
                $generated = null;
            }

            $diskConfig = config("filesystems.disks.{$disk}", []);
            $bucket = $diskConfig['bucket'] ?? null;
            if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
                $generated = null;
            }

            if (empty($generated) && !empty($diskConfig['url'])) {
                $diskUrl = rtrim($diskConfig['url'], '/');
                $encodedKey = implode('/', array_map('rawurlencode', explode('/', $storedPath)));
                if (!empty($bucket)) {
                    $generated = $diskUrl . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
                } else {
                    $generated = $diskUrl . '/' . ltrim($encodedKey, '/');
                }
            }

            if (!empty($generated)) {
                $avatarToSave = $generated;
            }

        $user->update([
            'avatar'       => $avatarToSave,
            'avatar_disk'  => $disk,
        ]);
        return $generated;
        } catch (\Throwable $e) {
            Log::error('Avatar URL generation failed: ' . $e->getMessage());
            return;
        }
        
    }

    /**
     * Show security settings
     */
    public function security()
    {
        return view('user.security');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ]);

        $otpDigits = [
            trim((string) $request->input('otp_1')),
            trim((string) $request->input('otp_2')),
            trim((string) $request->input('otp_3')),
            trim((string) $request->input('otp_4')),
        ];

        $otpCode = implode('', $otpDigits);

        if (strlen($otpCode) !== 4 || !ctype_digit($otpCode)) {
            return back()->withErrors(['otp' => 'Enter the 4-digit verification code.']);
        }

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The provided password does not match our records.']);
        }

        if (!$this->otpService->validate($user, 'password_change', $otpCode)) {
            return back()->withErrors(['otp' => 'The verification code is invalid or has expired.']);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['new_password']),
            'password_changed_at' => now(),
            'first_login_done' => true,
        ]);

        return back()->with('password_updated', 'Password updated successfully!');
    }

    /**
     * Show preferences
     */
    public function preferences()
    {
        return view('user.preferences');
    }

    /**
     * Update preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = auth()->user();

        $preferences = [
            'notify_email' => $request->has('notify_email'),
            'notify_budget' => $request->has('notify_budget'),
            'notify_group' => $request->has('notify_group'),
            'notify_weekly' => $request->has('notify_weekly'),
            'theme' => $request->input('theme', 'light'),
            'currency' => $request->input('currency', 'USD'),
            'language' => $request->input('language', 'en'),
            'show_profile' => $request->has('show_profile'),
            'share_stats' => $request->has('share_stats'),
        ];

        $user->update([
            'preferences' => $preferences,
        ]);

        return back()->with('preferences_updated', 'Preferences updated successfully!');
    }

    /**
     * Sign out all sessions
     */
    public function logoutAll()
    {
        // This would require a tokens table to track sessions
        // For now, just log out the current session
        auth('sanctum')->user()->tokens()->delete();
        auth()->guard('sanctum')->logout();

        return redirect('/')->with('success', 'You have been signed out from all sessions.');
    }

    /**
     * Disable 2FA
     */
    public function disable2FA()
    {
        $user = auth()->user();
        $user->update(['two_factor_enabled' => false]);

        return back()->with('success', '2FA has been disabled.');
    }

    /**
     * Show enable 2FA
     */
    public function enable2FA()
    {
        return view('user.enable-2fa');
    }

    /**
     * List user transactions
     */
    public function transactions(Request $request)
    {
        $user = auth()->user();

        $formatCurrency = static function (float $amount): string {
            $sign = $amount < 0 ? '-$' : '$';
            return $sign . number_format(abs($amount), 2);
        };

        $period = $request->input('period', 'this_month');
        $typeFilter = $request->input('type');

        $fromDate = null;
        $toDate = null;

        switch ($period) {
            case 'this_week':
                $fromDate = Carbon::now()->startOfWeek();
                $toDate = Carbon::now()->endOfWeek();
                break;
            case 'last_week':
                $fromDate = Carbon::now()->subWeek()->startOfWeek();
                $toDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'last_30_days':
                $fromDate = Carbon::now()->subDays(29)->startOfDay();
                $toDate = Carbon::now()->endOfDay();
                break;
            case 'last_month':
                $fromDate = Carbon::now()->subMonth()->startOfMonth();
                $toDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $fromDate = Carbon::now()->startOfYear();
                $toDate = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $fromDate = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
                $toDate = $request->filled('to') ? Carbon::parse($request->input('to')) : null;
                break;
            case 'all_time':
                // leave dates null
                break;
            case 'this_month':
            default:
                $fromDate = Carbon::now()->startOfMonth();
                $toDate = Carbon::now()->endOfMonth();
                $period = 'this_month';
                break;
        }

        $coalescedDateColumn = DB::raw('COALESCE(transaction_date, created_at)');

        $applyDateFilters = function ($query) use ($fromDate, $toDate, $coalescedDateColumn) {
            if ($fromDate) {
                $query->where($coalescedDateColumn, '>=', $fromDate->copy()->startOfDay());
            }

            if ($toDate) {
                $query->where($coalescedDateColumn, '<=', $toDate->copy()->endOfDay());
            }

            return $query;
        };

        $transactionsQuery = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id');

        $transactionsQuery = $applyDateFilters($transactionsQuery);

        if (in_array($typeFilter, ['income', 'expense'], true)) {
            $transactionsQuery->where('type', $typeFilter);
        } else {
            $typeFilter = null;
        }

        // Server-side search (q) - search description, category name, and type
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $transactionsQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($cq) use ($search) {
                        $cq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $perPage = (int) max(5, min($request->input('per_page', 15), 100));

        $transactions = $transactionsQuery
            ->with('category')
            ->orderByRaw('COALESCE(transaction_date, created_at) DESC')
            ->paginate($perPage)
            ->withQueryString();

        // If request expects JSON (AJAX/dashboard calls), return a compact JSON payload
        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('accept') ?? '', '/json')) {
            $items = $transactions->map(function (Transaction $transaction) use ($formatCurrency) {
                $transactionDate = $transaction->transaction_date ?? $transaction->created_at;

                return [
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'category_name' => optional($transaction->category)->name,
                    'display_amount' => $formatCurrency((float) ($transaction->amount ?? 0)),
                    'display_date' => $transactionDate?->format('M d, Y'),
                    'is_income' => $transaction->type === 'income',
                ];
            });

            return response()->json([
                'data' => $items,
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ]);
        }

        $totalsBaseQuery = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('group_id');

        $totalsBaseQuery = $applyDateFilters($totalsBaseQuery);

        $incomeTotal = (clone $totalsBaseQuery)->where('type', 'income')->sum('amount');
        $expenseTotal = (clone $totalsBaseQuery)->where('type', 'expense')->sum('amount');

        $totals = [
            'income' => $incomeTotal,
            'expense' => $expenseTotal,
            'net' => $incomeTotal - $expenseTotal,
        ];

        $filters = [
            'type' => $typeFilter,
            'period' => $period,
            'from' => $fromDate ? $fromDate->toDateString() : null,
            'to' => $toDate ? $toDate->toDateString() : null,
            'per_page' => $perPage,
        ];

        switch ($period) {
            case 'this_week':
                $periodLabel = 'This Week';
                break;
            case 'last_week':
                $periodLabel = 'Last Week';
                break;
            case 'last_30_days':
                $periodLabel = 'Last 30 Days';
                break;
            case 'last_month':
                $periodLabel = 'Last Month';
                break;
            case 'this_year':
                $periodLabel = 'This Year';
                break;
            case 'all_time':
                $periodLabel = 'All Time';
                break;
            case 'custom':
                $startLabel = $filters['from'] ? Carbon::parse($filters['from'])->format('M d, Y') : 'Beginning';
                $endLabel = $filters['to'] ? Carbon::parse($filters['to'])->format('M d, Y') : 'Today';
                $periodLabel = $startLabel . ' - ' . $endLabel;
                break;
            default:
                $periodLabel = 'This Month';
                break;
        }

        return view('user.transactions.index', [
            'transactions' => $transactions,
            'totals' => $totals,
            'filters' => $filters,
            'periodLabel' => $periodLabel,
        ]);
    }

    /**
     * Create transaction
     */
    public function createTransaction()
    {
        $this->ensureDefaultCategories();

        $categories = Category::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
            ->orderByRaw("COALESCE(type, '') ASC")
            ->orderBy('name')
            ->get();

        return view('user.transactions.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store transaction
     */
    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => ['required', 'in:income,expense'],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->whereNull('user_id')
                    ->orWhere('user_id', auth()->id())
                ),
            ],
            'date' => 'required|date',
        ]);

        $category = Category::find($validated['category_id']);

        if ($category && $category->type && $category->type !== $validated['type']) {
            return back()
                ->withErrors(['category_id' => 'Selected category does not match the transaction type.'])
                ->withInput();
        }

        Transaction::create([
            'user_id' => auth()->id(),
            'group_id' => null,
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'category_id' => $validated['category_id'],
            'transaction_date' => Carbon::parse($validated['date'])
                ->startOfDay(),
            'type' => $validated['type'],
        ]);

        return redirect()->route('user.dashboard')
            ->with('success', 'Transaction added successfully!');
    }

    /**
     * Ensure baseline categories exist for personal tracking.
     */
    private function ensureDefaultCategories(): void
    {
        if (Category::whereNull('user_id')->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Salary', 'icon' => '💼', 'color' => '#4CAF50', 'type' => 'income'],
            ['name' => 'Freelance', 'icon' => '💻', 'color' => '#2196F3', 'type' => 'income'],
            ['name' => 'Business', 'icon' => '🏢', 'color' => '#FF9800', 'type' => 'income'],
            ['name' => 'Investment', 'icon' => '📈', 'color' => '#9C27B0', 'type' => 'income'],
            ['name' => 'Gift', 'icon' => '🎁', 'color' => '#E91E63', 'type' => 'income'],
            ['name' => 'Other Income', 'icon' => '💰', 'color' => '#00BCD4', 'type' => 'income'],
            ['name' => 'Food & Dining', 'icon' => '🍽️', 'color' => '#FF5722', 'type' => 'expense'],
            ['name' => 'Transportation', 'icon' => '🚗', 'color' => '#795548', 'type' => 'expense'],
            ['name' => 'Shopping', 'icon' => '🛍️', 'color' => '#9C27B0', 'type' => 'expense'],
            ['name' => 'Entertainment', 'icon' => '🎬', 'color' => '#673AB7', 'type' => 'expense'],
            ['name' => 'Bills & Utilities', 'icon' => '💡', 'color' => '#FF9800', 'type' => 'expense'],
            ['name' => 'Healthcare', 'icon' => '🏥', 'color' => '#F44336', 'type' => 'expense'],
            ['name' => 'Education', 'icon' => '📚', 'color' => '#2196F3', 'type' => 'expense'],
            ['name' => 'Travel', 'icon' => '✈️', 'color' => '#00BCD4', 'type' => 'expense'],
            ['name' => 'Insurance', 'icon' => '🛡️', 'color' => '#607D8B', 'type' => 'expense'],
            ['name' => 'Personal Care', 'icon' => '💅', 'color' => '#E91E63', 'type' => 'expense'],
            ['name' => 'Home & Garden', 'icon' => '🏠', 'color' => '#4CAF50', 'type' => 'expense'],
            ['name' => 'Pets', 'icon' => '🐾', 'color' => '#FFEB3B', 'type' => 'expense'],
            ['name' => 'Other Expense', 'icon' => '📦', 'color' => '#9E9E9E', 'type' => 'expense'],
        ];

        foreach ($defaults as $category) {
            Category::firstOrCreate(
                [
                    'user_id' => null,
                    'name' => $category['name'],
                    'type' => $category['type'],
                ],
                $category
            );
        }
    }

    /**
     * List user budgets
     */
    public function budgets()
    {
        $budgets = Budget::where('user_id', auth()->id())
            ->with('category')
            ->paginate(10);

        return view('user.budgets.index', compact('budgets'));
    }

    /**
     * List user groups
     */
    public function groups()
    {
        $groups = auth()->user()
            ->groups()
            ->with('members')
            ->paginate(10);

        return view('user.groups.index', compact('groups'));
    }

    /**
     * Show single group
     */
    public function group(Group $group)
    {
        $userId = auth()->id();

        if (! $group->members()->where('user_id', $userId)->exists()) {
            abort(403, 'Unauthorized');
        }

        $group->load([
            'owner',
            'members.user',
            'sharedTransactions' => function ($query) {
                $query->with('user', 'category')
                    ->orderByDesc(DB::raw('COALESCE(transaction_date, created_at)'));
            },
        ]);

        $incomeTotal = (float) $group->sharedTransactions()
            ->where('type', 'income')
            ->sum('amount');

        $expenseTotal = (float) $group->sharedTransactions()
            ->where('type', 'expense')
            ->sum('amount');

        $transactionCount = $group->sharedTransactions()->count();
        $lastActivity = $group->sharedTransactions()
            ->orderByDesc(DB::raw('COALESCE(transaction_date, created_at)'))
            ->first();

        $totalFlow = $incomeTotal + $expenseTotal;

        $groupTotals = [
            'income' => $incomeTotal,
            'expense' => $expenseTotal,
            'net' => $incomeTotal - $expenseTotal,
        ];

        $transactionMetrics = [
            'count' => $transactionCount,
            'average' => $transactionCount > 0 ? $totalFlow / $transactionCount : 0,
            'last_activity' => $lastActivity ? ($lastActivity->transaction_date ?? $lastActivity->created_at) : null,
        ];

        $memberStats = $group->sharedTransactions()
            ->select(
                'user_id',
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total"),
                DB::raw('COUNT(*) as transactions_count')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->orderByDesc('transactions_count')
            ->get()
            ->keyBy('user_id');

        // Recent transactions (limit to latest 7 for display in group show page)
        $recentTransactions = $group->sharedTransactions()
            ->with('user', 'category')
            ->orderByDesc(DB::raw('COALESCE(transaction_date, created_at)'))
            ->limit(7)
            ->get();

        $hasMoreTransactions = $transactionCount > $recentTransactions->count();

        return view('user.groups.show', [
            'group' => $group,
            'groupTotals' => $groupTotals,
            'transactionMetrics' => $transactionMetrics,
            'memberStats' => $memberStats,
            'recentTransactions' => $recentTransactions,
            'hasMoreTransactions' => $hasMoreTransactions,
        ]);
    }

    /**
     * Show all transactions for a specific group (web view)
     */
    public function groupTransactions(Group $group)
    {
        $userId = auth()->id();

        if (! $group->members()->where('user_id', $userId)->exists()) {
            abort(403, 'Unauthorized');
        }

        $transactions = $group->sharedTransactions()
            ->with('user', 'category')
            ->orderByDesc(DB::raw('COALESCE(transaction_date, created_at)'))
            ->paginate(20);

        return view('user.groups.transactions', [
            'group' => $group,
            'transactions' => $transactions,
        ]);
    }

    /**
     * List user reports
     */
    public function reports()
    {
        return view('user.reports.index');
    }

    /**
     * Web view: list notifications for the user
     */
    public function notifications(Request $request)
    {
        $user = auth()->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('user.notifications.index', [
            'notifications' => $notifications,
        ]);
    }
}
