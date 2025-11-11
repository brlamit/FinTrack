<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Transaction;
use App\Models\User;
use App\Mail\GroupEngagementReminderMail;
use App\Mail\PersonalEngagementReminderMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB as DBFacade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

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

    /**
     * Send engagement reminder emails to underperforming groups.
     */
    public function sendEngagementReminders()
    {
        $leaderGroup = Group::query()
            ->with(['owner:id,name,email'])
            ->withSum('sharedTransactions as total_shared_amount', 'amount')
            ->orderByDesc('total_shared_amount')
            ->first();

        if (!$leaderGroup) {
            Log::info('Skipped engagement reminders: no groups found.');

            return redirect()->route('admin.dashboard')->with(
                'warning',
                'Create at least one group to start sending engagement reminders.'
            );
        }

        $leaderTotal = (float) ($leaderGroup->total_shared_amount ?? 0);
        $threshold = $leaderTotal * 0.5;

        if ($leaderTotal <= 0) {
            $threshold = 1.0; // ensure zero-activity groups are still targeted

            Log::info('Engagement reminders running with default threshold due to low activity.', [
                'leader_group_id' => $leaderGroup->id,
            ]);
        }

        $candidateGroups = Group::query()
            ->with(['owner:id,name,email'])
            ->withSum('sharedTransactions as total_shared_amount', 'amount')
            ->get()
            ->filter(function (Group $group) use ($threshold, $leaderGroup) {
                if ($group->id === $leaderGroup->id) {
                    return false;
                }

                return (float) ($group->total_shared_amount ?? 0) < $threshold;
            });

        if ($candidateGroups->isEmpty()) {
            Log::info('Skipped engagement reminders: no groups below threshold.', [
                'leader_group_id' => $leaderGroup->id,
                'threshold' => $threshold,
            ]);

            return redirect()->route('admin.dashboard')->with(
                'info',
                'All groups are operating at or above 50% of the current leader.'
            );
        }

        $leaderTotal = (float) $leaderGroup->total_shared_amount;
        $sentCount = 0;

        $failedCount = 0;

        foreach ($candidateGroups as $group) {
            if (!$group->owner || empty($group->owner->email)) {
                continue;
            }

            $groupTotal = (float) ($group->total_shared_amount ?? 0);
            $percentOfLeader = $leaderTotal > 0 ? round(($groupTotal / $leaderTotal) * 100, 1) : 0.0;

            try {
                Mail::to($group->owner->email)->send(
                    new GroupEngagementReminderMail($group, $leaderGroup, $percentOfLeader, $leaderTotal, $groupTotal)
                );

                Log::info('Sent engagement reminder email.', [
                    'group_id' => $group->id,
                    'group_owner_email' => $group->owner->email,
                    'percent_of_leader' => $percentOfLeader,
                ]);

                $sentCount++;
            } catch (\Throwable $exception) {
                Log::error('Failed to send engagement reminder email.', [
                    'group_id' => $group->id,
                    'group_owner_email' => $group->owner->email,
                    'error' => $exception->getMessage(),
                ]);

                $failedCount++;
                continue;
            }
        }

        if ($sentCount === 0) {
            $message = $failedCount > 0
                ? 'Tried to send engagement reminders, but the mail transport rejected every attempt. Check mail credentials and try again.'
                : 'No reminder emails were sent because eligible groups do not have owners with email addresses.';

            return redirect()->route('admin.dashboard')->with('warning', $message);
        }

        return redirect()->route('admin.dashboard')->with(
            'success',
            'Engagement reminders have been emailed to ' . $sentCount . ' group owner' . ($sentCount === 1 ? '' : 's') . '.'
        );
    }

    /**
     * Send engagement reminder emails to individual users with low or no activity.
     */
    public function sendPersonalEngagementReminders()
    {
        $inactiveSince = Carbon::now()->subDays(14);
        $activityCutoff = $inactiveSince->toDateTimeString();

        $eligibleUsers = User::query()
            ->where('role', '!=', 'admin')
            ->where(function ($query) use ($activityCutoff) {
                $query->whereDoesntHave('transactions')
                    ->orWhereRaw('(
                        SELECT MAX(COALESCE(transaction_date, created_at))
                        FROM transactions
                        WHERE transactions.user_id = users.id
                    ) < ?', [$activityCutoff]);
            })
            ->get();

        if ($eligibleUsers->isEmpty()) {
            Log::info('Skipped personal reminders: no inactive users found.', [
                'inactive_since' => $activityCutoff,
            ]);

            return redirect()->route('admin.dashboard')->with(
                'info',
                'All active users have logged a transaction within the last 14 days.'
            );
        }

        $sentCount = 0;
        $failedCount = 0;
        $missingEmailCount = 0;

        foreach ($eligibleUsers as $user) {
            if (empty($user->email)) {
                $missingEmailCount++;
                continue;
            }

            $latestActivityRaw = Transaction::query()
                ->where('user_id', $user->id)
                ->selectRaw('COALESCE(transaction_date, created_at) as activity_at')
                ->orderByDesc(DBFacade::raw('COALESCE(transaction_date, created_at)'))
                ->value('activity_at');

            $latestActivityAt = $latestActivityRaw ? Carbon::parse($latestActivityRaw) : null;

            try {
                Mail::to($user->email)->send(
                    new PersonalEngagementReminderMail($user, $latestActivityAt, $inactiveSince)
                );

                Log::info('Sent personal engagement reminder email.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'latest_activity_at' => optional($latestActivityAt)->toDateTimeString(),
                ]);

                $sentCount++;
            } catch (\Throwable $exception) {
                Log::error('Failed to send personal engagement reminder email.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);

                $failedCount++;
            }
        }

        if ($sentCount === 0) {
            if ($failedCount > 0) {
                return redirect()->route('admin.dashboard')->with(
                    'warning',
                    'Tried to send personal reminders, but the mail transport rejected every attempt. Check mail credentials and try again.'
                );
            }

            if ($missingEmailCount > 0) {
                return redirect()->route('admin.dashboard')->with(
                    'warning',
                    'Inactive users are missing email addresses, so no reminders could be delivered.'
                );
            }

            return redirect()->route('admin.dashboard')->with(
                'info',
                'Personal reminders were skipped because no eligible users remained after validation.'
            );
        }

        $successMessage = 'Personal reminders have been emailed to ' . $sentCount . ' user' . ($sentCount === 1 ? '' : 's') . '.';

        if ($missingEmailCount > 0) {
            $successMessage .= ' ' . $missingEmailCount . ' user' . ($missingEmailCount === 1 ? ' is' : 's are') . ' missing email addresses.';
        }

        if ($failedCount > 0) {
            $successMessage .= ' ' . $failedCount . ' send attempt' . ($failedCount === 1 ? ' was' : 's were') . ' rejected by the mail transport.';
        }

        return redirect()->route('admin.dashboard')->with('success', $successMessage);
    }
}
