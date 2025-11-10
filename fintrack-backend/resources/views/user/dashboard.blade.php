@extends('layouts.user')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">Welcome, {{ auth()->user()->name }}! 👋</h2>
            <p class="text-muted">Here's your financial overview</p>
        </div>
    </div>
    <!-- Key Metrics + Quick Actions -->
     <div class="row g-4 mb-5 align-items-stretch">
        <!-- Key Metrics (Left Side) -->
        <div class="col-lg-8">
            <div class="row g-4">
                @php
                    $metrics = [
                        ['label' => 'Total Balance', 'icon' => 'fa-wallet', 'class' => 'primary', 'value' => $totalsDisplay['overall']['net'] ?? '$0.00', 'desc' => 'Income: '.($totalsDisplay['overall']['income'] ?? '$0.00').' · Expense: '.($totalsDisplay['overall']['expense'] ?? '$0.00')],
                        ['label' => 'Total Income', 'icon' => 'fa-arrow-trend-up', 'class' => 'success', 'value' => $totalsDisplay['overall']['income'] ?? '$0.00', 'desc' => 'Across all transactions'],
                        ['label' => 'Total Expense', 'icon' => 'fa-arrow-trend-down', 'class' => 'danger', 'value' => $totalsDisplay['overall']['expense'] ?? '$0.00', 'desc' => 'Across all transactions'],
                    ];
                @endphp

                @foreach ($metrics as $metric)
                    <div class="col-md-4">
                        <div class="card border-0 shadow-lg rounded-4 h-100">
                            <div class="card-body position-relative overflow-hidden">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-1">{{ $metric['label'] }}</h6>
                                        <h4 class="text-{{ $metric['class'] }} fw-bold">{{ $metric['value'] }}</h4>
                                        <small class="text-muted">{{ $metric['desc'] }}</small>
                                    </div>
                                    <div class="p-3 bg-{{ $metric['class'] }} bg-opacity-10 rounded-circle">
                                        <i class="fas {{ $metric['icon'] }} text-{{ $metric['class'] }}" style="font-size: 28px;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('user.transactions.create') }}" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle me-1"></i> Add Transaction
                    </a>
                    <a href="{{ route('user.budgets') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-chart-pie me-1"></i> Manage Budgets
                    </a>
                    <a href="{{ route('user.groups') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-users me-1"></i> My Groups
                    </a>
                    <a href="{{ route('user.reports') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-file-alt me-1"></i> View Reports
                    </a>
                </div>
            </div>
{{-- 
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Budget Status</h5>
                </div>
                <div class="card-body">
                    @forelse($activeBudgets ?? [] as $budget)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">{{ data_get($budget, 'label', 'General') }}</span>
                                <span class="small fw-semibold {{ data_get($budget, 'status_class') }}">{{ data_get($budget, 'status_label') }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Spent {{ data_get($budget, 'spent_formatted', '$0.00') }}</span>
                                <span>Limit {{ data_get($budget, 'limit_formatted', '$0.00') }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ data_get($budget, 'progress', 0) }}%"
                                     aria-valuenow="{{ data_get($budget, 'progress', 0) }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mt-2">
                                <span>Remaining {{ data_get($budget, 'remaining_formatted', '$0.00') }}</span>
                                <span>{{ data_get($budget, 'progress', 0) }}% used</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-3">No active budgets yet.</p>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('user.budgets') }}" class="btn btn-outline-primary btn-sm">Create a budget</a>
                        </div>
                    @endforelse
                </div>
            </div> --}}
        </div>
    </div>

    @if(!empty($insights ?? []) || !empty($topExpenseCategory ?? []))
        <div class="row g-4 mb-4 align-items-stretch">
            <div class="col-lg-8">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex flex-column flex-md-row justify-content-between">
                            <div>
                                <h5 class="mb-0">Spending Insights</h5>
                                <small class="text-muted">Snapshot for {{ $chartWindowDescription }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
                            @foreach(($insights ?? []) as $insight)
                                <div class="col">
                                    <div class="p-3 rounded-4 border bg-light h-100">
                                        <p class="text-muted text-uppercase small mb-1">{{ data_get($insight, 'label') }}</p>
                                        <h4 class="fw-bold mb-1 {{ data_get($insight, 'class') }}">{{ data_get($insight, 'value') }}</h4>
                                        <p class="small text-muted mb-0">{{ data_get($insight, 'description') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Top Spending Focus</h5>
                        <small class="text-muted">Based on {{ $chartWindowDescription }}</small>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center text-center">
                        <h5 class="fw-semibold">{{ data_get($topExpenseCategory ?? [], 'label', 'No expenses recorded') }}</h5>
                        <p class="display-6 fw-bold text-danger mb-2">{{ data_get($topExpenseCategory ?? [], 'amount', '$0.00') }}</p>
                        @if(data_get($topExpenseCategory ?? [], 'share'))
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">{{ data_get($topExpenseCategory ?? [], 'share') }}% of expenses</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold">No spending yet</span>
                        @endif
                        <small class="text-muted mt-3">Use this insight to plan your next budget move.</small>
                    </div>
                </div>
            </div>
        </div>
    @endif

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Budget Status</h5>
                </div>
                <div class="card-body">
                    @forelse($activeBudgets ?? [] as $budget)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold">{{ data_get($budget, 'label', 'General') }}</span>
                                <span class="small fw-semibold {{ data_get($budget, 'status_class') }}">{{ data_get($budget, 'status_label') }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>Spent {{ data_get($budget, 'spent_formatted', '$0.00') }}</span>
                                <span>Limit {{ data_get($budget, 'limit_formatted', '$0.00') }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ data_get($budget, 'progress', 0) }}%"
                                     aria-valuenow="{{ data_get($budget, 'progress', 0) }}"
                                     aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mt-2">
                                <span>Remaining {{ data_get($budget, 'remaining_formatted', '$0.00') }}</span>
                                <span>{{ data_get($budget, 'progress', 0) }}% used</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-3">No active budgets yet.</p>
                        <div class="d-flex justify-content-center">
                            <a href="{{ route('user.budgets') }}" class="btn btn-outline-primary btn-sm">Create a budget</a>
                        </div>
                    @endforelse
                </div>
            </div>
    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Income vs Expense</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" id="chartPeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="far fa-calendar"></i>
                            <span>{{ $chartWindowDescription ?? 'Select range' }}</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-3 shadow" aria-labelledby="chartPeriodToggle" style="min-width: 250px;">
                            <form id="chart-period-form" method="GET" action="{{ route('user.dashboard') }}" class="d-flex flex-column gap-3">
                                <div>
                                    <label for="chart_period" class="form-label small text-uppercase text-muted mb-1">Chart period</label>
                                    <select id="chart_period" name="chart_period" class="form-select form-select-sm">
                                        @foreach(['1' => 'Last month', '3' => 'Last 3 months', '6' => 'Last 6 months', 'custom' => 'Custom range'] as $value => $label)
                                            <option value="{{ $value }}" {{ (string) ($filters['chart_period'] ?? '3') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="chart_end" class="form-label small text-uppercase text-muted mb-1">End month</label>
                                    <input type="month" id="chart_end" name="chart_end" class="form-control form-control-sm" value="{{ $filters['chart_end'] ?? '' }}">
                                </div>
                                <div data-custom-range class="{{ (string) ($filters['chart_period'] ?? '3') === 'custom' ? '' : 'd-none' }}">
                                    <label for="chart_start" class="form-label small text-uppercase text-muted mb-1">Start month</label>
                                    <input type="month" id="chart_start" name="chart_start" class="form-control form-control-sm" value="{{ $filters['chart_start'] ?? '' }}">
                                </div>
                                @if(isset($filters['category_type']))
                                    <input type="hidden" name="category_type" value="{{ $filters['category_type'] }}">
                                @endif
                                <div class="text-end">
                                    <a href="{{ route('user.dashboard') }}" class="btn btn-link btn-sm text-decoration-none">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="incomeExpenseChart" height="260"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ ucfirst($filters['category_type'] ?? 'expense') }} Breakdown</h5>
                    <span class="text-muted small">Top categories</span>
                </div>
                <div class="card-body">
                    @if(!empty($chartData['category']['labels']))
                        <canvas id="expenseCategoryChart" height="260"></canvas>
                    @else
                        <p class="text-muted text-center mb-0">Add {{ ucfirst($filters['category_type'] ?? 'expense') }} transactions to see category insights.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
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
                                <th>Type</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions ?? [] as $transaction)
                                @php
                                    $isIncome = data_get($transaction, 'is_income', false);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $isIncome ? 'bg-success' : 'bg-danger' }} text-uppercase">{{ data_get($transaction, 'type', 'n/a') }}</span>
                                    </td>
                                    <td>{{ data_get($transaction, 'description', '—') }}</td>
                                    <td>
                                        @if(data_get($transaction, 'category_name'))
                                            <span class="badge bg-secondary">{{ data_get($transaction, 'category_name') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="{{ $isIncome ? 'text-success' : 'text-danger' }}">{{ data_get($transaction, 'display_amount', '$0.00') }}</strong>
                                    </td>
                                    <td>{{ data_get($transaction, 'display_date', '—') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No transactions yet. Start tracking your expenses!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyData = @json($chartData['monthly'] ?? ['labels' => [], 'income' => [], 'expense' => []]);
        const categoryData = @json($chartData['category'] ?? ['labels' => [], 'values' => [], 'colors' => []]);

        const chartPeriodForm = document.getElementById('chart-period-form');
        if (chartPeriodForm) {
            const periodSelect = chartPeriodForm.querySelector('#chart_period');
            const customRangeContainer = chartPeriodForm.querySelector('[data-custom-range]');
            const startInput = chartPeriodForm.querySelector('#chart_start');
            const endInput = chartPeriodForm.querySelector('#chart_end');

            const updateCustomVisibility = () => {
                if (periodSelect && customRangeContainer) {
                    if (periodSelect.value === 'custom') {
                        customRangeContainer.classList.remove('d-none');
                    } else {
                        customRangeContainer.classList.add('d-none');
                    }
                }
            };

            const submitIfReady = () => {
                if (!periodSelect) {
                    chartPeriodForm.submit();
                    return;
                }

                if (periodSelect.value === 'custom') {
                    if (startInput?.value && endInput?.value) {
                        chartPeriodForm.submit();
                    }
                } else {
                    chartPeriodForm.submit();
                }
            };

            updateCustomVisibility();

            periodSelect?.addEventListener('change', () => {
                updateCustomVisibility();
                if (periodSelect.value !== 'custom') {
                    submitIfReady();
                }
            });

            [startInput, endInput].forEach(input => {
                input?.addEventListener('change', submitIfReady);
            });

            chartPeriodForm.querySelectorAll('select').forEach(select => {
                if (select.id !== 'chart_period') {
                    select.addEventListener('change', submitIfReady);
                }
            });
        }

        const incomeExpenseCanvas = document.getElementById('incomeExpenseChart');
        if (incomeExpenseCanvas) {
            const ctx = incomeExpenseCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthlyData.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: monthlyData.income,
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.12)',
                            pointBackgroundColor: '#16a34a',
                            pointRadius: 4,
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'Expense',
                            data: monthlyData.expense,
                            borderColor: '#dc2626',
                            backgroundColor: 'rgba(220, 38, 38, 0.12)',
                            pointBackgroundColor: '#dc2626',
                            pointRadius: 4,
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
                                    const value = Number(context.formattedValue || context.raw || 0);
                                    return `${label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                },
                            },
                        },
                    },
                },
            });
        }

        const expenseCategoryCanvas = document.getElementById('expenseCategoryChart');
        if (expenseCategoryCanvas && categoryData.labels && categoryData.labels.length) {
            const ctx = expenseCategoryCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.labels,
                    datasets: [
                        {
                            data: categoryData.values,
                            backgroundColor: categoryData.colors,
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
