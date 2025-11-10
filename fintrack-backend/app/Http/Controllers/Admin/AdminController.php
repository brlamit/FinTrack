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
        $monthsBack = 6;
        $periodLabels = [];
        $incomeSeries = [];
        $expenseSeries = [];

        $dateExpression = DB::raw('COALESCE(transaction_date, created_at)');
        $now = Carbon::now()->startOfMonth();

        for ($offset = $monthsBack - 1; $offset >= 0; $offset--) {
            $month = $now->copy()->subMonths($offset);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $periodLabels[] = $month->format('M Y');

            $incomeSeries[] = (float) Transaction::query()
                ->where('type', 'income')
                ->whereBetween($dateExpression, [$start, $end])
                ->sum('amount');

            $expenseSeries[] = (float) Transaction::query()
                ->where('type', 'expense')
                ->whereBetween($dateExpression, [$start, $end])
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

        $chartData = [
            'monthly' => [
                'labels' => $periodLabels,
                'income' => $incomeSeries,
                'expense' => $expenseSeries,
            ],
            'category' => $categoryChart,
        ];

        $stats = [
            'total_users' => User::count(),
            'total_groups' => Group::count(),
            'total_transactions' => Transaction::count(),
            'total_transaction_amount' => Transaction::sum('amount'),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_transactions' => Transaction::with('user')->latest()->take(5)->get(),
            'chartData' => $chartData,
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
}
