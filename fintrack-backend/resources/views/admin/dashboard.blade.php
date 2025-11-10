@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2>{{ number_format($stats['total_users']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Groups</h5>
                <h2>{{ number_format($stats['total_groups']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Transactions</h5>
                <h2>{{ number_format($stats['total_transactions']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Amount</h5>
                <h2>${{ number_format($stats['total_transaction_amount'], 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Platform Income vs Expense</h5>
                <span class="text-muted small">Last 6 months</span>
            </div>
            <div class="card-body">
                <canvas id="admin-income-expense-chart" height="260"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expense Breakdown</h5>
                <span class="text-muted small">Top categories</span>
            </div>
            <div class="card-body">
                @if(!empty($stats['chartData']['category']['labels']))
                    <canvas id="admin-category-chart" height="260"></canvas>
                @else
                    <p class="text-muted text-center mb-0">Add expense transactions to populate this chart.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Users</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($stats['recent_users'] as $user)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $user->name }}
                        {{-- <span class="badge bg-primary rounded-pill">{{ $user->created_at->diffForHumans() }}</span> --}}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Transactions</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($stats['recent_transactions'] as $transaction)
                    <li class="list-group-item">
                        <strong>{{ $transaction->user->name }}</strong> - {{ ucfirst($transaction->type) }} ${{ number_format($transaction->amount, 2) }}
                        <br><small class="text-muted">{{ $transaction->created_at->diffForHumans() }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $adminChartData = $stats['chartData'] ?? [
        'monthly' => ['labels' => [], 'income' => [], 'expense' => []],
        'category' => ['labels' => [], 'values' => [], 'colors' => []],
    ];
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = @json($adminChartData);

        const incomeExpenseCanvas = document.getElementById('admin-income-expense-chart');
        if (incomeExpenseCanvas && chartData.monthly) {
            const context = incomeExpenseCanvas.getContext('2d');
            new Chart(context, {
                type: 'line',
                data: {
                    labels: chartData.monthly.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: chartData.monthly.income,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.12)',
                            pointBackgroundColor: '#16a34a',
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'Expense',
                            data: chartData.monthly.expense,
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.12)',
                            pointBackgroundColor: '#dc2626',
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '$' + Number(value).toLocaleString(),
                            },
                            grid: { drawBorder: false },
                        },
                        x: {
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: context => {
                                    const label = context.dataset.label || '';
                                    const value = Number(context.parsed.y || 0);
                                    return `${label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                },
                            },
                        },
                    },
                },
            });
        }

        const categoryCanvas = document.getElementById('admin-category-chart');
        if (categoryCanvas && chartData.category && chartData.category.labels && chartData.category.labels.length) {
            const context = categoryCanvas.getContext('2d');
            new Chart(context, {
                type: 'doughnut',
                data: {
                    labels: chartData.category.labels,
                    datasets: [
                        {
                            data: chartData.category.values,
                            backgroundColor: chartData.category.colors,
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: context => {
                                    const value = Number(context.parsed || 0);
                                    return `${context.label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                },
                            },
                        },
                    },
                },
            });
        }
    });
</script>
@endpush