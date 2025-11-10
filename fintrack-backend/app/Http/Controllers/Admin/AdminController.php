<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $monthsBack = 12;
        $periodLabels = [];
        $incomeSeries = [];
        $expenseSeries = [];

    $dateExpressionRaw = 'COALESCE(transaction_date, created_at)';
        $currentMoment = Carbon::now();
        $startOfCurrentMonth = $currentMoment->copy()->startOfMonth();

        for ($offset = $monthsBack - 1; $offset >= 0; $offset--) {
            $month = $startOfCurrentMonth->copy()->subMonths($offset);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $periodLabels[] = $month->format('M Y');

            $incomeSeries[] = (float) Transaction::query()
                ->where('type', 'income')
                ->whereBetween(DB::raw($dateExpressionRaw), [$start, $end])
                ->sum('amount');

            $expenseSeries[] = (float) Transaction::query()
                ->where('type', 'expense')
                ->whereBetween(DB::raw($dateExpressionRaw), [$start, $end])
                ->sum('amount');
        }

        $categoryBreakdown = Transaction::query()
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->where('type', 'expense')
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

        $fallbackColors = ['#2563eb', '#9333ea', '#f59e0b', '#10b981', '#ef4444', '#14b8a6', '#f97316', '#0ea5e9'];
        $colorIndex = 0;

        foreach ($categoryBreakdown as $row) {
            $category = $categoryMap->get($row->category_id);
            $categoryChart['labels'][] = $category?->name ?? 'Uncategorized';
            $categoryChart['values'][] = (float) ($row->total ?? 0);
            $categoryChart['colors'][] = $category?->color ?: $fallbackColors[$colorIndex++ % count($fallbackColors)];
        }

        $startOfPreviousMonth = $startOfCurrentMonth->copy()->subMonth();
        $endOfPreviousMonth = $startOfCurrentMonth->copy()->subSecond();

        $newUsersCurrent = User::where('created_at', '>=', $startOfCurrentMonth)->count();
        $newUsersPrevious = User::whereBetween('created_at', [$startOfPreviousMonth, $endOfPreviousMonth])->count();

        $newGroupsCurrent = Group::where('created_at', '>=', $startOfCurrentMonth)->count();
        $newGroupsPrevious = Group::whereBetween('created_at', [$startOfPreviousMonth, $endOfPreviousMonth])->count();

        $currentMonthTransactionCount = Transaction::query()
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfCurrentMonth, $currentMoment])
            ->count();

        $previousMonthTransactionCount = Transaction::query()
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfPreviousMonth, $endOfPreviousMonth])
            ->count();

        $currentMonthVolume = (float) Transaction::query()
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfCurrentMonth, $currentMoment])
            ->sum('amount');

        $previousMonthVolume = (float) Transaction::query()
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfPreviousMonth, $endOfPreviousMonth])
            ->sum('amount');

        $currentIncome = (float) Transaction::query()
            ->where('type', 'income')
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfCurrentMonth, $currentMoment])
            ->sum('amount');

        $currentExpense = (float) Transaction::query()
            ->where('type', 'expense')
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfCurrentMonth, $currentMoment])
            ->sum('amount');

        $previousIncome = (float) Transaction::query()
            ->where('type', 'income')
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfPreviousMonth, $endOfPreviousMonth])
            ->sum('amount');

        $previousExpense = (float) Transaction::query()
            ->where('type', 'expense')
            ->whereBetween(DB::raw($dateExpressionRaw), [$startOfPreviousMonth, $endOfPreviousMonth])
            ->sum('amount');

        $thirtyDaysAgo = $currentMoment->copy()->subDays(30);
        $sixtyDaysAgo = $currentMoment->copy()->subDays(60);

        $activeUsersCurrent = User::whereHas('transactions', function ($query) use ($dateExpressionRaw, $thirtyDaysAgo, $currentMoment) {
            $query->whereBetween(DB::raw($dateExpressionRaw), [$thirtyDaysAgo, $currentMoment]);
        })->count();

        $activeUsersPrevious = User::whereHas('transactions', function ($query) use ($dateExpressionRaw, $sixtyDaysAgo, $thirtyDaysAgo) {
            $query->whereBetween(DB::raw($dateExpressionRaw), [$sixtyDaysAgo, $thirtyDaysAgo]);
        })->count();

        $topGroups = Group::query()
            ->with(['owner:id,name'])
            ->withCount('members')
            ->withSum('sharedTransactions as total_shared_amount', 'amount')
            ->orderByDesc('total_shared_amount')
            ->limit(5)
            ->get();

        $chartData = [
            'monthly' => [
                'labels' => $periodLabels,
                'income' => $incomeSeries,
                'expense' => $expenseSeries,
            ],
            'category' => $categoryChart,
        ];

        $totalUsers = User::count();
        $totalGroups = Group::count();
        $totalTransactions = Transaction::count();
        $totalTransactionAmount = Transaction::sum('amount');

        $stats = [
            'total_users' => $totalUsers,
            'total_groups' => $totalGroups,
            'total_transactions' => $totalTransactions,
            'total_transaction_amount' => $totalTransactionAmount,
            'recent_users' => User::latest()->take(5)->get(),
            'recent_transactions' => Transaction::with('user')->latest()->take(5)->get(),
            'chartData' => $chartData,
            'insight_cards' => [
                [
                    'title' => 'Total Users',
                    'icon' => 'fa-users',
                    'accent' => 'primary',
                    'value' => $totalUsers,
                    'format' => 'number',
                    'detail_text' => number_format($newUsersCurrent) . ' new this month',
                    'trend' => $this->buildTrend($newUsersCurrent, $newUsersPrevious),
                ],
                [
                    'title' => 'Active Members',
                    'icon' => 'fa-user-check',
                    'accent' => 'success',
                    'value' => $activeUsersCurrent,
                    'format' => 'number',
                    'detail_text' => 'Rolling 30-day activity snapshot',
                    'trend' => $this->buildTrend($activeUsersCurrent, $activeUsersPrevious, 'vs prior 30 days'),
                ],
                [
                    'title' => 'Total Groups',
                    'icon' => 'fa-people-group',
                    'accent' => 'info',
                    'value' => $totalGroups,
                    'format' => 'number',
                    'detail_text' => number_format($newGroupsCurrent) . ' new this month',
                    'trend' => $this->buildTrend($newGroupsCurrent, $newGroupsPrevious),
                ],
                [
                    'title' => 'Platform Volume',
                    'icon' => 'fa-sack-dollar',
                    'accent' => 'warning',
                    'value' => $totalTransactionAmount,
                    'format' => 'currency',
                    'detail_text' => '$' . number_format($currentMonthVolume, 2) . ' processed this month',
                    'trend' => $this->buildTrend($currentMonthVolume, $previousMonthVolume),
                    'net' => [
                        'current' => $currentIncome - $currentExpense,
                        'previous' => $previousIncome - $previousExpense,
                    ],
                ],
            ],
            'top_groups' => $topGroups,
            'monthly_context' => [
                'current_transaction_count' => $currentMonthTransactionCount,
                'previous_transaction_count' => $previousMonthTransactionCount,
                'current_income' => $currentIncome,
                'current_expense' => $currentExpense,
            ],
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
        }

        $users = $query->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function transactions(Request $request)
    {
    $query = Transaction::with(['user', 'category', 'group']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.transactions.index', compact('transactions', 'groups'));
    }

    public function impersonate(User $user)
    {
        Auth::login($user);
        return redirect('/')->with('success', 'Now impersonating ' . $user->name);
    }

    protected function buildTrend(float|int $current, float|int $previous, string $comparisonLabel = 'vs last month'): array
    {
        $delta = $current - $previous;

        if ($previous == 0.0) {
            if ($current == 0.0) {
                return [
                    'direction' => 'flat',
                    'percent' => 0.0,
                    'delta' => $delta,
                    'comparison_label' => $comparisonLabel,
                ];
            }

            return [
                'direction' => $current > 0 ? 'up' : 'down',
                'percent' => $current > 0 ? 100.0 : -100.0,
                'delta' => $delta,
                'comparison_label' => $comparisonLabel,
            ];
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = 'flat';

        if ($change > 0) {
            $direction = 'up';
        } elseif ($change < 0) {
            $direction = 'down';
        }

        return [
            'direction' => $direction,
            'percent' => round($change, 1),
            'delta' => $delta,
            'comparison_label' => $comparisonLabel,
        ];
    }
}
