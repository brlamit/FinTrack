@extends('layouts.user')

@section('title', $group->name)

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $currency = fn (float $value) => '$' . number_format($value, 2);
        $lastActivity = $transactionMetrics['last_activity'] ?? null;
        if ($lastActivity && ! $lastActivity instanceof \Carbon\CarbonInterface) {
            $lastActivity = \Illuminate\Support\Carbon::parse($lastActivity);
        }
        $currentMember = $group->members->firstWhere('user_id', auth()->id());
        $isAdmin = $currentMember && $currentMember->role === 'admin';
        $isOwner = $group->owner_id === auth()->id();
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-3 mb-2">
                <h2 class="mb-0">{{ $group->name }}</h2>
                <span class="badge bg-primary-subtle text-primary text-uppercase">{{ ucfirst($group->type) }}</span>
            </div>
            <p class="text-muted mb-0">
                You joined {{ optional(optional($currentMember)->joined_at)->format('M d, Y') ?? '—' }} · Owner {{ $group->owner->name }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('user.groups') }}" class="btn btn-outline-secondary">Back to Groups</a>
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
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Add Group Transaction</h5>
                        <span class="text-muted small">Track shared income or expenses and decide how to split them.</span>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('groups.split', $group) }}" method="POST" enctype="multipart/form-data" id="add-expense-form" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Total amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" id="total-amount">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Transaction type</label>
                            <div class="btn-group w-100" role="group" aria-label="Transaction type">
                                <input type="radio" class="btn-check" name="type" id="type-expense" value="expense" autocomplete="off" checked>
                                <label class="btn btn-outline-danger" for="type-expense">Expense</label>
                                <input type="radio" class="btn-check" name="type" id="type-income" value="income" autocomplete="off">
                                <label class="btn btn-outline-success" for="type-income">Income</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Split method</label>
                            <div class="btn-group w-100" role="group" aria-label="Split type">
                                <input type="radio" class="btn-check" name="split_type" id="split-type-equal" value="equal" autocomplete="off" checked>
                                <label class="btn btn-outline-secondary" for="split-type-equal">Equal</label>
                                <input type="radio" class="btn-check" name="split_type" id="split-type-custom" value="custom" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="split-type-custom">Custom</label>
                                <input type="radio" class="btn-check" name="split_type" id="split-type-percentage" value="percentage" autocomplete="off">
                                <label class="btn btn-outline-secondary" for="split-type-percentage">Percent</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" name="description" class="form-control" placeholder="E.g. Grocery run or Rent contribution">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt (optional)</label>
                            <input type="file" name="receipt" accept="image/*" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end justify-content-md-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="auto-distribute">Auto distribute</button>
                            <button type="submit" class="btn btn-primary">Save Transaction</button>
                        </div>
                        <div class="col-12">
                            <hr class="my-2">
                        </div>
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase small">Per-member split</h6>
                            <p class="text-muted small mb-3">Enter amounts for custom splits or percentages when using percent mode. Auto distribute helps balance amounts quickly.</p>
                        </div>
                        @foreach($group->members as $idx => $member)
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                    <div>
                                        <strong>{{ $member->user->name }}</strong>
                                        <span class="text-muted small ms-2">{{ ucfirst($member->role) }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <input type="hidden" name="splits[{{ $idx }}][user_id]" value="{{ $member->user->id }}">
                                        <div class="split-input-amount">
                                            <input type="number" step="0.01" name="splits[{{ $idx }}][amount]" class="form-control split-amount" style="width: 130px;" placeholder="0.00">
                                        </div>
                                        <div class="split-input-percent d-none">
                                            <div class="input-group" style="width: 130px;">
                                                <input type="number" step="0.01" name="splits[{{ $idx }}][percent]" class="form-control split-percent" placeholder="0.00">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </form>
                </div>
            </div>

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
                                        <td class="text-center text-muted small">{{ optional($member->joined_at)->format('M d, Y') ?? '—' }}</td>
                                        <td class="text-end text-success">{{ $currency($income) }}</td>
                                        <td class="text-end text-danger">{{ $currency($expense) }}</td>
                                        <td class="text-center">{{ number_format($stats->transactions_count ?? 0) }}</td>
                                        <td class="text-end {{ $balance >= 0 ? 'text-success' : 'text-danger' }}">{{ $currency($balance) }}</td>
                                        <td class="text-end">
                                            @if($isAdmin && $member->user_id !== $group->owner_id && $member->user_id !== auth()->id())
                                                <form action="{{ route('groups.member.remove', ['group' => $group->id, 'member' => $member->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove {{ $member->user->name }} from this group?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            @elseif($member->user_id === $group->owner_id)
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
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <div>
                        @if(!empty($hasMoreTransactions))
                            <a href="{{ route('user.group.transactions', $group) }}" class="btn btn-sm btn-outline-secondary">View All Transactions</a>
                        @else
                            <a href="{{ route('user.group.transactions', $group) }}" class="btn btn-sm btn-outline-secondary">View All</a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Description</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Receipt</th>
                                    <th scope="col">Recorded By</th>
                                    <th scope="col">Category</th>
                                    <th scope="col" class="text-end">Amount</th>
                                    <th scope="col" class="text-end">Date</th>
                                    <th scope="col"> Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
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
                                        <td>
                                            @php
                                                $imgUrl = null;
                                                $path = $transaction->receipt_path ?? null;

                                                if ($path) {
                                                    $disk = $transaction->receipt_disk ?? config('filesystems.default');

                                                    // If the path already looks like a URL, use it directly
                                                    if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']) || filter_var($path, FILTER_VALIDATE_URL)) {
                                                        $imgUrl = $path;
                                                    } else {
                                                        try {
                                                            // Prefer Storage->url when the file exists on the configured disk
                                                            if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
                                                                $imgUrl = \Illuminate\Support\Facades\Storage::disk($disk)->url($path);
                                                            } else {
                                                                $imgUrl = null;
                                                            }
                                                        } catch (\Throwable $e) {
                                                            $imgUrl = null;
                                                        }
                                                    }
                                                }
                                            @endphp

                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}" width="80" height="60" style="object-fit: cover; border-radius: 4px;" alt="{{ $transaction->description ?? 'Receipt Image' }}">
                                            @else
                                                <span class="text-muted small">No Image</span>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->user->name ?? '—' }}</td>
                                        <td>{{ $transaction->category->name ?? '—' }}</td>
                                        <td class="text-end {{ $isIncome ? 'text-success' : 'text-danger' }}">{{ $currency($transaction->amount) }}</td>
                                        <td class="text-end text-muted small">{{ $date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('user.transaction.show', $transaction) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No transactions recorded for this group yet.</td>
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

                        <dt class="col-6 text-muted">Your role</dt>
                        <dd class="col-6 text-end">{{ ucfirst(optional($currentMember)->role ?? 'member') }}</dd>

                        <dt class="col-6 text-muted">Members</dt>
                        <dd class="col-6 text-end">{{ number_format($group->members->count()) }}</dd>

                        <dt class="col-6 text-muted">Budget Limit</dt>
                        <dd class="col-6 text-end">{{ $group->budget_limit ? $currency($group->budget_limit) : '—' }}</dd>

                        <dt class="col-6 text-muted">Invite Code</dt>
                        <dd class="col-6 text-end text-uppercase">{{ $group->invite_code ?? '—' }}</dd>
                    </dl>

                    @if($group->description)
                        <hr>
                        <p class="text-muted small mb-1">Description</p>
                        <p class="mb-0">{{ $group->description }}</p>
                    @endif
                </div>
            </div>

            @if($isAdmin)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Invite Member</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('groups.invite', $group) }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">Send Invite</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if($isOwner)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 text-danger">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Deleting this group removes all shared transactions and memberships permanently.</p>
                        <form action="{{ route('user.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Delete this group and all related records? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">Delete Group</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('add-expense-form');
    if (!form) return;

    const totalInput = document.getElementById('total-amount');
    const splitRadios = form.querySelectorAll('input[name="split_type"]');
    const autoDistributeButton = document.getElementById('auto-distribute');
    const amountContainers = form.querySelectorAll('.split-input-amount');
    const percentContainers = form.querySelectorAll('.split-input-percent');
    const amountInputs = form.querySelectorAll('.split-amount');
    const percentInputs = form.querySelectorAll('.split-percent');

    const getMode = () => form.querySelector('input[name="split_type"]:checked')?.value ?? 'equal';

    const toggleSplitInputs = () => {
        const mode = getMode();
        const usePercent = mode === 'percentage';

        percentContainers.forEach(container => container.classList.toggle('d-none', !usePercent));
        amountContainers.forEach(container => container.classList.toggle('d-none', usePercent));

        if (!usePercent) {
            percentInputs.forEach(input => (input.value = ''));
        }
    };

    const distributeAmounts = () => {
        const mode = getMode();
        if (mode === 'percentage') {
            const count = percentInputs.length;
            if (!count) return;

            const slice = count ? Math.round((100 / count) * 100) / 100 : 0;
            let remaining = 100;

            percentInputs.forEach((input, index) => {
                const value = index === count - 1 ? remaining : slice;
                input.value = value.toFixed(2);
                remaining = Number((remaining - value).toFixed(2));
            });

            amountInputs.forEach(input => (input.value = '0.00'));
            return;
        }

        const total = parseFloat(totalInput?.value || '0');
        const count = amountInputs.length;
        if (!count) return;

        if (!total || total <= 0) {
            amountInputs.forEach(input => (input.value = '0.00'));
            return;
        }

        const base = Math.round((total / count) * 100) / 100;
        let assigned = 0;

        amountInputs.forEach((input, index) => {
            let value = base;
            if (index === count - 1) {
                value = Number((total - assigned).toFixed(2));
            }

            assigned = Number((assigned + value).toFixed(2));
            input.value = value.toFixed(2);
        });
    };

    autoDistributeButton?.addEventListener('click', () => {
        distributeAmounts();
    });

    totalInput?.addEventListener('input', () => {
        if (getMode() === 'equal') {
            distributeAmounts();
        }
    });

    splitRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            toggleSplitInputs();
            if (['equal', 'percentage'].includes(getMode())) {
                distributeAmounts();
            }
        });
    });

    toggleSplitInputs();
    distributeAmounts();
});
</script>
@endsection