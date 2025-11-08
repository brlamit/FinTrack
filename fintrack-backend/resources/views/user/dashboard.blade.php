@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">Welcome, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-muted">Here's your financial overview</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Balance</h6>
                            <h4 class="text-primary">{{ $totalBalance ?? '$0.00' }}</h4>
                        </div>
                        <div style="font-size: 32px; opacity: 0.2;">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">This Month</h6>
                            <h4 class="text-success">{{ $thisMonth ?? '$0.00' }}</h4>
                        </div>
                        <div style="font-size: 32px; opacity: 0.2;">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Categories</h6>
                            <h4 class="text-info">{{ $categoryCount ?? 0 }}</h4>
                        </div>
                        <div style="font-size: 32px; opacity: 0.2;">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Budgets</h6>
                            <h4 class="text-warning">{{ $budgetCount ?? 0 }}</h4>
                        </div>
                        <div style="font-size: 32px; opacity: 0.2;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <a href="{{ route('user.transactions') }}" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions ?? [] as $transaction)
                                <tr>
                                    <td>{{ $transaction->description }}</td>
                                    <td>
                                        @if($transaction->category)
                                            <span class="badge bg-secondary">{{ $transaction->category->name }}</span>
                                        @endif
                                    </td>
                                    <td><strong>${{ number_format($transaction->amount, 2) }}</strong></td>
                                   <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No transactions yet. Start tracking your expenses!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('user.transactions.create') }}" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-plus-circle"></i> Add Expense
                    </a>
                    <a href="{{ route('user.budgets') }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-chart-pie"></i> Manage Budgets
                    </a>
                    <a href="{{ route('user.groups') }}" class="btn btn-outline-primary w-100 mb-2">
                        <i class="fas fa-users"></i> My Groups
                    </a>
                    <a href="{{ route('user.reports') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-file-alt"></i> View Reports
                    </a>
                </div>
            </div>

            <!-- Budget Status -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Budget Status</h5>
                </div>
                <div class="card-body">
                    @forelse($activeBudgets ?? [] as $budget)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <small>{{ $budget->category->name ?? 'General' }}</small>
                                <small class="font-weight-bold">
                                    ${{ number_format($budget->spent, 2) }} / ${{ number_format($budget->limit_amount, 2) }}
                                </small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ min(($budget->spent / $budget->limit_amount * 100), 100) }}%"
                                     aria-valuenow="{{ min(($budget->spent / $budget->limit_amount * 100), 100) }}" 
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No active budgets</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
