<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB as DBFacade;

class AdminGroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index()
    {
        $groups = Group::query()
            ->with('owner:id,name,email')
            ->withCount('members')
            ->withCount('sharedTransactions')
            ->withSum(['sharedTransactions as income_total' => function ($query) {
                $query->where('type', 'income');
            }], 'amount')
            ->withSum(['sharedTransactions as expense_total' => function ($query) {
                $query->where('type', 'expense');
            }], 'amount')
            ->orderByDesc('created_at')
            ->get();

        $groupMetrics = [
            'groups' => $groups->count(),
            'members' => (int) $groups->sum('members_count'),
            'transactions' => (int) $groups->sum('shared_transactions_count'),
            'income' => (float) $groups->sum('income_total'),
            'expense' => (float) $groups->sum('expense_total'),
            'budget_cap' => (float) $groups->pluck('budget_limit')->filter()->sum(),
        ];

        $groupMetrics['net'] = $groupMetrics['income'] - $groupMetrics['expense'];

        return view('admin.groups.index', [
            'groups' => $groups,
            'groupMetrics' => $groupMetrics,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        return view('admin.groups.create');
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:family,friends',
            'budget_limit' => 'nullable|numeric|min:0',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'budget_limit' => $request->budget_limit,
            'owner_id' => auth()->id(),
            'invite_code' => Str::random(8),
        ]);

        // Add admin as admin member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => auth()->id(),
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        return redirect()->route('admin.groups.index')->with('success', 'Group created successfully');
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        $group->load([
            'owner',
            'members.user',
            'sharedTransactions' => function ($query) {
                $query->with('user', 'category')->orderByDesc(DBFacade::raw('COALESCE(transaction_date, created_at)'));
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
            ->orderByDesc(DBFacade::raw('COALESCE(transaction_date, created_at)'))
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
                DBFacade::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total"),
                DBFacade::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total"),
                DBFacade::raw('COUNT(*) as transactions_count')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->orderByDesc('transactions_count')
            ->get()
            ->keyBy('user_id');

        return view('admin.groups.show', [
            'group' => $group,
            'groupTotals' => $groupTotals,
            'transactionMetrics' => $transactionMetrics,
            'memberStats' => $memberStats,
        ]);
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group)
    {
        DBFacade::transaction(function () use ($group) {
            $group->members()->delete();
            $group->sharedTransactions()->delete();
            $group->delete();
        });

        return redirect()->route('admin.groups.index')->with('success', 'Group deleted successfully');
    }
}
