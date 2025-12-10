<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
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

            // Admin dashboard aggregates: include both group and personal transactions
            $incomeSeries[] = (float) Transaction::query()
                ->where('type', 'income')
                ->whereBetween(DB::raw($dateExpressionRaw), [$start, $end])
                ->sum('amount');

            $expenseSeries[] = (float) Transaction::query()
                ->where('type', 'expense')
                ->whereBetween(DB::raw($dateExpressionRaw), [$start, $end])
                ->sum('amount');
        }

        // Category breakdown from all expenses (group + personal)
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
        // Totals reflect all transactions (group + personal)
        $totalTransactions = Transaction::count();
        $totalTransactionAmount = Transaction::sum('amount');

        $stats = [
            'total_users' => $totalUsers,
            'total_groups' => $totalGroups,
            'total_transactions' => $totalTransactions,
            'total_transaction_amount' => $totalTransactionAmount,
            'recent_users' => User::latest()->take(5)->get(),
            // Show only group-shared transactions in recent list for admin visibility
            'recent_transactions' => Transaction::with('user')
                ->whereNotNull('group_id')
                ->latest()
                ->take(5)
                ->get(),
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
        // Default: admin sees only group-shared transactions (privacy-first)
        $query = Transaction::with(['user', 'category', 'group'])
            ->whereNotNull('group_id');

        // Optional gated access to personal transactions with justification; audit on use
        $includePersonal = $request->boolean('include_personal', false);
        $justification = trim((string) $request->input('reason', ''));
        if ($includePersonal) {
            if (! Gate::allows('view_personal_transactions')) {
                return redirect()->back()->withErrors(['include_personal' => 'You are not authorized to include personal transactions.']);
            }
            // Require a non-empty justification
            if ($justification === '') {
                return redirect()->back()->withErrors(['reason' => 'Please provide a brief justification to include personal transactions.']);
            }

            // Expand scope and log the access intent; masking sensitive fields is still expected in views/resources
            $query = Transaction::with(['user', 'category', 'group']);

            try {
                \App\Models\AdminAccessLog::create([
                    'admin_user_id' => Auth::id(),
                    'action' => 'include_personal_transactions',
                    'reason' => $justification,
                    'context' => json_encode([
                        'filters' => [
                            'user_id' => $request->input('user_id'),
                            'type' => $request->input('type'),
                            'group_id' => $request->input('group_id'),
                            'date_from' => $request->input('date_from'),
                            'date_to' => $request->input('date_to'),
                        ],
                    ]),
                ]);
            } catch (\Throwable $e) {
                // Non-blocking: fail open but capture in log
                \Log::warning('AdminAccessLog failed', ['error' => $e->getMessage()]);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('date_from')) {
            try {
                $from = \Illuminate\Support\Carbon::parse($request->date_from)->startOfDay();
                $query->whereDate('created_at', '>=', $from);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        if ($request->filled('date_to')) {
            try {
                $to = \Illuminate\Support\Carbon::parse($request->date_to)->endOfDay();
                $query->whereDate('created_at', '<=', $to);
            } catch (\Throwable $e) {
                // ignore invalid date
            }
        }

        $transactions = $query->paginate(20);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.transactions.index', compact('transactions', 'groups'));
    }

    public function reports(Request $request)
    {
        $dateExpressionRaw = 'COALESCE(transaction_date, created_at)';

        // Base scope: group-only by default
        $query = Transaction::with(['user', 'category', 'group'])
            ->whereNotNull('group_id');

        // Optional: include personal (gated + justification + audit)
        $includePersonal = $request->boolean('include_personal', false);
        $justification = trim((string) $request->input('reason', ''));
        if ($includePersonal) {
            if (! \Illuminate\Support\Facades\Gate::allows('view_personal_transactions')) {
                return redirect()->back()->withErrors(['include_personal' => 'You are not authorized to include personal transactions.']);
            }
            if ($justification === '') {
                return redirect()->back()->withErrors(['reason' => 'Please provide a brief justification to include personal transactions.']);
            }
            $query = Transaction::with(['user', 'category', 'group']);

            try {
                \App\Models\AdminAccessLog::create([
                    'admin_user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => 'report_include_personal_transactions',
                    'reason' => $justification,
                    'context' => json_encode([
                        'filters' => [
                            'user_id' => $request->input('user_id'),
                            'type' => $request->input('type'),
                            'group_id' => $request->input('group_id'),
                            'date_from' => $request->input('date_from'),
                            'date_to' => $request->input('date_to'),
                        ],
                    ]),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('AdminAccessLog(report) failed', ['error' => $e->getMessage()]);
            }
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }
        if ($request->filled('date_from')) {
            try {
                $from = \Illuminate\Support\Carbon::parse($request->date_from)->startOfDay();
                $query->whereBetween(\Illuminate\Support\Facades\DB::raw($dateExpressionRaw), [$from, now()]);
            } catch (\Throwable $e) {
                // ignore invalid
            }
        }
        if ($request->filled('date_to')) {
            try {
                $to = \Illuminate\Support\Carbon::parse($request->date_to)->endOfDay();
                $query->where(\Illuminate\Support\Facades\DB::raw($dateExpressionRaw), '<=', $to);
            } catch (\Throwable $e) {
                // ignore invalid
            }
        }

        // Clone for aggregates to avoid pagination side effects
        $base = (clone $query);

        $totalCount = (clone $base)->count();
        $totalAmount = (float) (clone $base)->sum('amount');
        $incomeSum = (float) (clone $base)->where('type', 'income')->sum('amount');
        $expenseSum = (float) (clone $base)->where('type', 'expense')->sum('amount');

        $byCategory = (clone $base)
            ->select('category_id', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byUser = (clone $base)
            ->select('user_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'), \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byGroup = (clone $base)
            ->select('group_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'), \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->whereNotNull('group_id')
            ->groupBy('group_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Paginated detail table
        $transactions = $query->latest()->paginate(25)->appends($request->query());
        $groups = Group::orderBy('name')->get(['id', 'name']);

        $report = [
            'totals' => [
                'count' => $totalCount,
                'amount' => $totalAmount,
                'income' => $incomeSum,
                'expense' => $expenseSum,
                'net' => $incomeSum - $expenseSum,
            ],
            'by_category' => $byCategory,
            'by_user' => $byUser,
            'by_group' => $byGroup,
        ];

        return view('admin.reports.index', compact('report', 'transactions', 'groups'));
    }

    public function reportsExportCsv(Request $request)
    {
        $dateExpressionRaw = 'COALESCE(transaction_date, created_at)';
        $query = Transaction::with(['user', 'category', 'group'])->whereNotNull('group_id');

        $includePersonal = $request->boolean('include_personal', false);
        $justification = trim((string) $request->input('reason', ''));
        if ($includePersonal) {
            if (! \Illuminate\Support\Facades\Gate::allows('view_personal_transactions')) {
                return redirect()->route('admin.reports', $request->query())->withErrors(['include_personal' => 'You are not authorized to include personal transactions.']);
            }
            if ($justification === '') {
                return redirect()->route('admin.reports', $request->query())->withErrors(['reason' => 'Please provide a brief justification to include personal transactions.']);
            }
            $query = Transaction::with(['user', 'category', 'group']);
            try {
                \App\Models\AdminAccessLog::create([
                    'admin_user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => 'report_export_csv_include_personal',
                    'reason' => $justification,
                    'context' => json_encode(['filters' => $request->query()]),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('AdminAccessLog(csv) failed', ['error' => $e->getMessage()]);
            }
        }

        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('group_id')) $query->where('group_id', $request->group_id);
        if ($request->filled('date_from')) {
            try { $from = \Illuminate\Support\Carbon::parse($request->date_from)->startOfDay(); $query->where(\Illuminate\Support\Facades\DB::raw($dateExpressionRaw), '>=', $from); } catch (\Throwable $e) {}
        }
        if ($request->filled('date_to')) {
            try { $to = \Illuminate\Support\Carbon::parse($request->date_to)->endOfDay(); $query->where(\Illuminate\Support\Facades\DB::raw($dateExpressionRaw), '<=', $to); } catch (\Throwable $e) {}
        }

        $filename = 'admin-report-' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['ID', 'User', 'Type', 'Group', 'Amount', 'Category', 'Description', 'Date'];

        $callback = function () use ($query, $columns, $dateExpressionRaw) {
            $handle = fopen('php://output', 'w');
            // Optional: UTF-8 BOM for Excel compatibility
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);

            $query->orderBy('id', 'desc')->chunk(1000, function ($rows) use ($handle, $dateExpressionRaw) {
                foreach ($rows as $t) {
                    fputcsv($handle, [
                        $t->id,
                        optional($t->user)->name,
                        $t->type,
                        optional($t->group)->name,
                        number_format((float) $t->amount, 2, '.', ''),
                        optional($t->category)->name,
                        $t->description,
                        optional($t->transaction_date ?? $t->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function reportsExportPdf(Request $request)
    {
        $dateExpressionRaw = 'COALESCE(transaction_date, created_at)';
        $query = Transaction::with(['user', 'category', 'group'])->whereNotNull('group_id');

        $includePersonal = $request->boolean('include_personal', false);
        $justification = trim((string) $request->input('reason', ''));
        if ($includePersonal) {
            if (! \Illuminate\Support\Facades\Gate::allows('view_personal_transactions')) {
                return redirect()->route('admin.reports', $request->query())->withErrors(['include_personal' => 'You are not authorized to include personal transactions.']);
            }
            if ($justification === '') {
                return redirect()->route('admin.reports', $request->query())->withErrors(['reason' => 'Please provide a brief justification to include personal transactions.']);
            }
            $query = Transaction::with(['user', 'category', 'group']);
            try {
                \App\Models\AdminAccessLog::create([
                    'admin_user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'action' => 'report_export_pdf_include_personal',
                    'reason' => $justification,
                    'context' => json_encode(['filters' => $request->query()]),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('AdminAccessLog(pdf) failed', ['error' => $e->getMessage()]);
            }
        }

        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('group_id')) $query->where('group_id', $request->group_id);
        if ($request->filled('date_from')) {
            try { $from = \Illuminate\Support\Carbon::parse($request->date_from)->startOfDay(); $query->where(\Illuminate\Support\Facades\DB::raw($dateExpressionRaw), '>=', $from); } catch (\Throwable $e) {}
        }
        if ($request->filled('date_to')) {
            try { $to = \Illuminate\Support\Carbon::parse($request->date_to)->endOfDay(); $query->where(\Illuminate\Support\Facades\DB::raw($dateExpressionRaw), '<=', $to); } catch (\Throwable $e) {}
        }

        $transactions = $query->orderBy('id', 'desc')->limit(5000)->get();

        $totals = [
            'count' => $transactions->count(),
            'amount' => (float) $transactions->sum('amount'),
            'income' => (float) $transactions->where('type', 'income')->sum('amount'),
            'expense' => (float) $transactions->where('type', 'expense')->sum('amount'),
        ];

        $data = [
            'generated_at' => now(),
            'filters' => $request->query(),
            'totals' => $totals,
            'transactions' => $transactions,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.export_pdf', $data)->setPaper('a4', 'portrait');
        $filename = 'admin-report-' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
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
