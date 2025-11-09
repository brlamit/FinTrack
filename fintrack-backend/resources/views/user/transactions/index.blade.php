@extends('layouts.user')

@section('title', 'My Transactions')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Transactions</h2>
        <a href="{{ route('user.transactions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Transaction
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Income</p>
                    <h4 class="text-success mb-0">${{ number_format($totals['income'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total Expense</p>
                    <h4 class="text-danger mb-0">${{ number_format($totals['expense'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Net Balance</p>
                    @php $netClass = $totals['net'] >= 0 ? 'text-success' : 'text-danger'; @endphp
                    <h4 class="{{ $netClass }} mb-0">${{ number_format($totals['net'], 2) }}</h4>
                    <small class="text-muted">{{ $periodLabel }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('user.transactions') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="period" class="form-label">Period</label>
                        <select id="period" name="period" class="form-select">
                            <option value="this_month" {{ $filters['period'] === 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $filters['period'] === 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="this_week" {{ $filters['period'] === 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="last_week" {{ $filters['period'] === 'last_week' ? 'selected' : '' }}>Last Week</option>
                            <option value="last_30_days" {{ $filters['period'] === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="this_year" {{ $filters['period'] === 'this_year' ? 'selected' : '' }}>This Year</option>
                            <option value="all_time" {{ $filters['period'] === 'all_time' ? 'selected' : '' }}>All Time</option>
                            <option value="custom" {{ $filters['period'] === 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="from" class="form-label">From</label>
                        <input id="from" type="date" name="from" class="form-control" value="{{ $filters['from'] }}" {{ $filters['period'] === 'custom' ? '' : 'disabled' }}>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="to" class="form-label">To</label>
                        <input id="to" type="date" name="to" class="form-control" value="{{ $filters['to'] }}" {{ $filters['period'] === 'custom' ? '' : 'disabled' }}>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="" {{ $filters['type'] === null ? 'selected' : '' }}>All</option>
                            <option value="income" {{ $filters['type'] === 'income' ? 'selected' : '' }}>Income</option>
                            <option value="expense" {{ $filters['type'] === 'expense' ? 'selected' : '' }}>Expense</option>
                        </select>
                    </div>
                    @php $perPageOptions = [10, 15, 25, 50, 100]; @endphp
                    <div class="col-6 col-md-2">
                        <label for="per_page" class="form-label">Per Page</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach($perPageOptions as $size)
                                <option value="{{ $size }}" {{ (int) $filters['per_page'] === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-xl-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                        <a href="{{ route('user.transactions') }}" class="btn btn-outline-secondary" title="Reset filters">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        @php
                            $dateToShow = $transaction->transaction_date ?? $transaction->created_at;
                            $isIncome = $transaction->type === 'income';
                        @endphp
                        <tr>
                            <td>
                                <span class="badge {{ $isIncome ? 'bg-success' : 'bg-danger' }} text-uppercase">{{ $transaction->type ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $transaction->description }}</td>
                            <td>
                                @if($transaction->category)
                                    <span class="badge bg-secondary">{{ $transaction->category->name }}</span>
                                @endif
                            </td>
                            <td>
                                <strong class="{{ $isIncome ? 'text-success' : 'text-danger' }}">${{ number_format($transaction->amount, 2) }}</strong>
                            </td>
                            <td>{{ $dateToShow?->format('M d, Y') }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="#" class="btn btn-sm btn-outline-danger">Delete</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No transactions yet. <a href="{{ route('user.transactions.create') }}">Add your first transaction</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($transactions->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const periodSelect = document.getElementById('period');
            const fromInput = document.getElementById('from');
            const toInput = document.getElementById('to');

            const toggleDateInputs = () => {
                const isCustom = periodSelect.value === 'custom';
                fromInput.disabled = !isCustom;
                toInput.disabled = !isCustom;
            };

            periodSelect.addEventListener('change', toggleDateInputs);
            toggleDateInputs();
        });
    </script>
@endpush
