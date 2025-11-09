@extends('layouts.admin')

@section('title', 'Group Details')

@section('content')
@php
    $currency = fn (float $value) => '$' . number_format($value, 2);
    $lastActivity = $transactionMetrics['last_activity'] ?? null;
    if ($lastActivity && ! $lastActivity instanceof \Carbon\CarbonInterface) {
        $lastActivity = \Illuminate\Support\Carbon::parse($lastActivity);
    }
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <h2 class="mb-0">{{ $group->name }}</h2>
            <span class="badge bg-primary-subtle text-primary text-uppercase">{{ ucfirst($group->type) }}</span>
        </div>
        <p class="text-muted mb-0">
            Created {{ $group->created_at?->format('M d, Y') ?? '—' }} ·
            Owner {{ $group->owner->name }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">Back to Groups</a>
        <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Deleting this group will remove all shared transactions and members. Continue?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">Delete Group</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Total Income</p>
                <h4 class="text-success fw-semibold mb-0">{{ $currency($groupTotals['income']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Total Expense</p>
                <h4 class="text-danger fw-semibold mb-0">{{ $currency($groupTotals['expense']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Net Flow</p>
                @php $net = $groupTotals['net']; @endphp
                <h4 class="fw-semibold mb-0 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ $currency($net) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Total Transactions</p>
                <h4 class="fw-semibold mb-0">{{ number_format($transactionMetrics['count']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Average Amount</p>
                <h4 class="fw-semibold mb-0">{{ $currency($transactionMetrics['average']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Last Activity</p>
                <h4 class="fw-semibold mb-1">{{ $lastActivity ? $lastActivity->format('M d, Y') : 'No activity yet' }}</h4>
                @if($lastActivity)
                    <span class="text-muted small">{{ $lastActivity->diffForHumans() }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Member Contributions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Member</th>
                                <th scope="col" class="text-center">Role</th>
                                <th scope="col" class="text-center">Joined</th>
                                <th scope="col" class="text-end">Income</th>
                                <th scope="col" class="text-end">Expense</th>
                                <th scope="col" class="text-center">Transactions</th>
                                <th scope="col" class="text-end">Balance</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($group->members as $member)
                                @php
                                    $stats = $memberStats->get($member->user_id);
                                    $income = $stats->income_total ?? 0;
                                    $expense = $stats->expense_total ?? 0;
                                    $balance = $income - $expense;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $member->user->name }}</div>
                                        <div class="text-muted small">{{ $member->user->email }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ ucfirst($member->role) }}</span>
                                    </td>
                                    <td class="text-center text-muted small">
                                        {{ optional($member->joined_at)->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-end text-success">{{ $currency($income) }}</td>
                                    <td class="text-end text-danger">{{ $currency($expense) }}</td>
                                    <td class="text-center">{{ number_format($stats->transactions_count ?? 0) }}</td>
                                    <td class="text-end {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $currency($balance) }}</td>
                                    <td class="text-end">
                                        @if($member->user_id !== $group->owner_id)
                                            <form action="{{ route('admin.groups.member.remove', ['group' => $group->id, 'member' => $member->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove {{ $member->user->name }} from this group?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                            </form>
                                        @else
                                            <span class="badge bg-light text-dark">Owner</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No members have joined this group yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Description</th>
                                <th scope="col">Type</th>
                                <th scope="col">Recorded By</th>
                                <th scope="col">Category</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($group->sharedTransactions as $transaction)
                                @php
                                    $isIncome = $transaction->type === 'income';
                                    $date = $transaction->transaction_date ?? $transaction->created_at;
                                @endphp
                                <tr>
                                    <td>{{ $transaction->description ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $isIncome ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                            {{ $isIncome ? 'Income' : 'Expense' }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->user->name ?? '—' }}</td>
                                    <td>{{ $transaction->category->name ?? '—' }}</td>
                                    <td class="text-end {{ $isIncome ? 'text-success' : 'text-danger' }}">{{ $currency($transaction->amount) }}</td>
                                    <td class="text-end text-muted small">{{ $date?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No transactions recorded for this group.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Group Overview</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6 text-muted">Owner</dt>
                    <dd class="col-6 text-end">{{ $group->owner->name }}</dd>

                    <dt class="col-6 text-muted">Owner Email</dt>
                    <dd class="col-6 text-end">{{ $group->owner->email }}</dd>

                    <dt class="col-6 text-muted">Budget Limit</dt>
                    <dd class="col-6 text-end">{{ $group->budget_limit ? $currency($group->budget_limit) : '—' }}</dd>

                    <dt class="col-6 text-muted">Invite Code</dt>
                    <dd class="col-6 text-end text-uppercase">{{ $group->invite_code ?? '—' }}</dd>

                    <dt class="col-6 text-muted">Members</dt>
                    <dd class="col-6 text-end">{{ number_format($group->members->count()) }}</dd>

                    <dt class="col-6 text-muted">Transactions</dt>
                    <dd class="col-6 text-end">{{ number_format($transactionMetrics['count']) }}</dd>
                </dl>

                @if($group->description)
                    <hr>
                    <p class="text-muted small mb-1">Description</p>
                    <p class="mb-0">{{ $group->description }}</p>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0 text-danger">Danger Zone</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Removing a group permanently deletes all shared transactions and membership records associated with it.</p>
                <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('This action cannot be undone. Delete the group permanently?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100">Delete Group</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection