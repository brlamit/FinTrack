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
                        <div class="card metric-card border-0 shadow-lg rounded-4 h-100">
                            <div class="card-body position-relative overflow-hidden">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="text-muted mb-1">{{ $metric['label'] }}</h6>
                                        @if($metric['label'] === 'Total Balance')
                                            <div class="d-flex align-items-center gap-2">
                                                <h4 id="total-balance-amount" class="text-{{ $metric['class'] }} fw-bold mb-0">{{ $metric['value'] }}</h4>
                                            </div>
                                        @else
                                            <h4 class="text-{{ $metric['class'] }} fw-bold">{{ $metric['value'] }}</h4>
                                        @endif
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
                        <div class="card shadow-sm quick-actions-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('user.transactions.create') }}" class="btn btn-primary w-100 rounded-pill">
                        <i class="fas fa-plus-circle me-1"></i> Add Transaction
                    </a>
                    <a href="{{ route('user.budgets') }}" class="btn btn-outline-primary w-100 rounded-pill">
                        <i class="fas fa-chart-pie me-1"></i> Manage Budgets
                    </a>
                    <a href="{{ route('user.groups') }}" class="btn btn-outline-primary w-100 rounded-pill">
                        <i class="fas fa-users me-1"></i> My Groups
                    </a>
                    <a href="{{ route('user.reports') }}" class="btn btn-outline-primary w-100 rounded-pill">
                        <i class="fas fa-file-alt me-1"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
        
    </div>
<!-- FINANCIAL HEALTH SCORE & WARNINGS -->
<div class="row g-4 mb-5">
    <!-- Financial Health Score Card -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Financial Health</h5>
                <small class="text-muted">Overall assessment</small>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                <div class="position-relative mb-3" style="width: 120px; height: 120px;">
                    <svg viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#{{ data_get($financialHealth, 'color') === 'success' ? '16a34a' : (data_get($financialHealth, 'color') === 'danger' ? 'dc2626' : (data_get($financialHealth, 'color') === 'warning' ? 'ea580c' : '0ea5e9')) }}" 
                                stroke-width="8" stroke-dasharray="{{ (data_get($financialHealth, 'score', 0) / 100) * 339.29 }} 339.29" stroke-linecap="round"/>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle text-center">
                        <div class="display-4 fw-bold text-{{ data_get($financialHealth, 'color') }}">{{ data_get($financialHealth, 'grade', '—') }}</div>
                        <small class="text-muted">{{ data_get($financialHealth, 'score', 0) }}/100</small>
                    </div>
                </div>
                <p class="text-muted small mb-3">Based on savings rate, budgets, income stability, and goals</p>
                <div class="w-100">
                    @foreach(data_get($financialHealth, 'breakdown', []) as $factor)
                        <div class="d-flex justify-content-between align-items-center small mb-2">
                            <span class="text-muted">{{ data_get($factor, 'label') }}</span>
                            <div class="progress flex-grow-1 mx-2" style="height: 6px; max-width: 80px;">
                                <div class="progress-bar" style="width: {{ (data_get($factor, 'points', 0) / data_get($factor, 'max', 1)) * 100 }}%"></div>
                            </div>
                            <small class="fw-semibold" style="min-width: 50px; text-align: right;">{{ round(data_get($factor, 'points', 0), 1) }}/{{ data_get($factor, 'max', 0) }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Warnings & Goals Summary -->
    <div class="col-lg-8">
        <!-- Budget Warnings -->
        @if(!empty($budgetWarnings) && count($budgetWarnings) > 0)
            <div class="card shadow-sm border-0 border-start border-warning mb-4">
                <div class="card-header bg-warning bg-opacity-10 border-0">
                    <h6 class="mb-0 text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Budget Warnings</h6>
                </div>
                <div class="card-body">
                    @foreach($budgetWarnings as $warning)
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <div>
                                <h6 class="mb-0">{{ data_get($warning, 'label', 'Budget') }}</h6>
                                <small class="text-muted">{{ data_get($warning, 'progress', 0) }}% used</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-semibold text-warning">{{ data_get($warning, 'spent_formatted', '$0.00') }} / {{ data_get($warning, 'limit_formatted', '$0.00') }}</div>
                                <small class="text-{{ data_get($warning, 'progress', 0) >= 100 ? 'danger' : 'warning' }}">{{ data_get($warning, 'status_label', 'Near limit') }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Goals Summary -->
        @if($goalCount > 0 && !empty($activeGoals))
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Financial Goals</h5>
                        <small class="text-muted">{{ $goalCount }} active goal{{ $goalCount !== 1 ? 's' : '' }}</small>
                    </div>
                    <a href="{{ route('user.goals') ?? '#' }}" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="card-body">
                    @forelse($activeGoals as $goal)
                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ data_get($goal, 'name', 'Goal') }}</h6>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" style="width: {{ min(max(data_get($goal, 'progress', 0), 0), 100) }}%"></div>
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <div class="small {{ data_get($goal, 'status_class', 'text-warning') }} fw-semibold">{{ data_get($goal, 'progress', 0) }}%</div>
                                <small class="text-muted">{{ data_get($goal, 'current_amount_formatted', '$0') }} / {{ data_get($goal, 'target_amount_formatted', '$0') }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center mb-0">No active goals. <a href="{{ route('user.goals') ?? '#' }}" class="text-decoration-none">Create one</a></p>
                    @endforelse
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 border-dashed">
                <div class="card-body text-center py-4">
                    <i class="fas fa-bullseye text-muted mb-3" style="font-size: 32px;"></i>
                    <p class="text-muted mb-2">No financial goals set yet</p>
                    <p class="text-muted small">Set goals to track progress toward your financial objectives</p>
                    @if(Route::has('user.goals'))
                        <a href="{{ route('user.goals') }}" class="btn btn-sm btn-primary mt-2">Create Goal</a>
                    @endif
                </div>
            </div>
        @endif
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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Budget Status</h5>
                    <a href="{{ route('user.budgets') }}" class="btn btn-sm btn-light">Manage Budgets</a>
                </div>
                <div class="card-body">
                    @if(!empty($activeBudgets) && count($activeBudgets) > 0)
                        <div class="row g-3">
                            @foreach($activeBudgets as $budget)
                                <div class="col-12 col-md-6 col-lg-4">
                                    @include('user.partials._budget_card', ['budget' => $budget])
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 text-end">
                            <a href="{{ route('user.budgets') }}" class="btn btn-outline-primary">View all budgets</a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">You don't have any active budgets yet.</p>
                            <a href="{{ route('user.budgets') }}" class="btn btn-primary">Create your first budget</a>
                        </div>
                    @endif
                </div>
            </div>
    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h5 class="mb-1">Income vs Expense</h5>
                            <span class="text-muted small" data-range-label>{{ $chartWindowDescription ?? 'Loading timeline...' }}</span>
                        </div>
                       <div class="chart-toolbar d-flex flex-wrap gap-2">
                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Select range">
                            <button type="button" class="control-pill" data-range="3" role="button" tabindex="0" aria-pressed="false">3M</button>
                            <button type="button" class="control-pill" data-range="6" role="button" tabindex="0" aria-pressed="false">6M</button>
                            <button type="button" class="control-pill" data-range="12" role="button" tabindex="0" aria-pressed="false">12M</button>
                            <button type="button" class="control-pill" data-range="all" role="button" tabindex="0" aria-pressed="false">All</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Chart type">
                            <button type="button" class="control-pill active" data-chart-type="line" role="button" tabindex="0" aria-pressed="true" aria-label="Line chart"><i class="fas fa-chart-line"></i></button>
                            <button type="button" class="control-pill" data-chart-type="bar" role="button" tabindex="0" aria-pressed="false" aria-label="Bar chart"><i class="fas fa-chart-column"></i></button>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper" style="height:320px;">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-4">
                        <div>
                            <span class="text-muted text-uppercase small fw-semibold">Income (this month)</span>
                            <p class="mb-0 fw-semibold fs-5 text-success">${{ number_format(data_get($totalsDisplay['monthly'] ?? [], 'income', 0), 2) }}</p>
                        </div>
                        <div>
                            <span class="text-muted text-uppercase small fw-semibold">Expense (this month)</span>
                            <p class="mb-0 fw-semibold fs-5 text-danger">${{ number_format(data_get($totalsDisplay['monthly'] ?? [], 'expense', 0), 2) }}</p>
                        </div>
                        <div>
                            <span class="text-muted text-uppercase small fw-semibold">Transactions (this month)</span>
                            <p class="mb-0 fw-semibold fs-5">{{ number_format(data_get($totalsDisplay['monthly'] ?? [], 'transactions', 0)) }}</p>
                        </div>
                    </div>
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
                <div class="p-3 d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group input-group-sm me-2" style="max-width:360px;">
                        <span class="input-group-text" id="search-addon"><i class="fas fa-search"></i></span>
                        <input id="tx-search" type="search" class="form-control" placeholder="Search transactions (desc, category, type)" aria-label="Search transactions" aria-describedby="search-addon">
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <label for="tx-per-page" class="small text-muted mb-0">Show</label>
                        <select id="tx-per-page" class="form-select form-select-sm" style="width:80px;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                        </select>
                    </div>
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
                                <tr class="transaction-row">
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
@php
    $userChartData = $chartData ?? [
        'monthly' => ['labels' => [], 'income' => [], 'expense' => []],
        'category' => ['labels' => [], 'values' => [], 'colors' => []],
    ];
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = @json($userChartData);
        const monthlyData = chartData.monthly || { labels: [], income: [], expense: [] };
        const categoryData = chartData.category || { labels: [], values: [], colors: [] };

        const incomeExpenseCanvas = document.getElementById('incomeExpenseChart');
        const categoryCanvas = document.getElementById('expenseCategoryChart');
        const rangeButtons = document.querySelectorAll('[data-range]');
        const chartTypeButtons = document.querySelectorAll('[data-chart-type]');
        const chartRangeLabel = document.querySelector('[data-range-label]');
        const parseNumber = (v) => {
            if (v === null || v === undefined) return 0;
            if (typeof v === 'number') return v;
            const s = String(v).replace(/[^0-9.\-]+/g, '');
            return s === '' ? 0 : Number(s);
        };

        monthlyData.income = Array.isArray(monthlyData.income) ? monthlyData.income.map(parseNumber) : [];
        monthlyData.expense = Array.isArray(monthlyData.expense) ? monthlyData.expense.map(parseNumber) : [];

        if (Array.isArray(monthlyData.labels)) {
            if (monthlyData.income.length !== monthlyData.labels.length) {
                console.warn('Income dataset length does not match labels', monthlyData);
            }
            if (monthlyData.expense.length !== monthlyData.labels.length) {
                console.warn('Expense dataset length does not match labels', monthlyData);
            }
        }

        const totalMonths = Array.isArray(monthlyData.labels) ? monthlyData.labels.length : 0;
        let currentRange = totalMonths >= 6 ? 6 : totalMonths;
        let currentChartType = 'line';
        let incomeExpenseChart;

        const hexToRgba = (hex, alpha) => {
            const sanitized = (hex || '').replace('#', '');
            if (!sanitized) return `rgba(0,0,0,${alpha})`;
            const bigint = parseInt(sanitized, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        };

        const setActiveRangeButton = (rangeValue, useAll = false) => {
            rangeButtons.forEach(button => {
                const key = button.dataset.range;
                if (key === 'all') {
                    button.classList.toggle('active', useAll);
                } else {
                    const numeric = Number(key);
                    button.classList.toggle('active', !useAll && numeric === rangeValue);
                }
            });

            if (chartRangeLabel) {
                if (totalMonths === 0) {
                    chartRangeLabel.textContent = 'No data available yet';
                } else {
                    const applied = useAll ? totalMonths : Math.min(rangeValue, totalMonths);
                    chartRangeLabel.textContent = `Last ${applied} month${applied === 1 ? '' : 's'}`;
                }
            }
        };

        const setActiveChartTypeButton = (type) => {
            chartTypeButtons.forEach(button => {
                button.classList.toggle('active', button.dataset.chartType === type);
            });
        };

        const disableUnavailableRangeButtons = () => {
            rangeButtons.forEach(button => {
                const key = button.dataset.range;
                if (key === 'all') {
                    button.disabled = totalMonths === 0;
                    button.classList.toggle('disabled', totalMonths === 0);
                } else {
                    const numeric = Number(key);
                    const shouldDisable = !totalMonths || numeric > totalMonths;
                    button.disabled = shouldDisable;
                    button.classList.toggle('disabled', shouldDisable);
                }
            });
        };

        const sliceMonthlyData = (range) => {
            if (totalMonths === 0) {
                return { labels: [], income: [], expense: [] };
            }
            const appliedRange = Math.min(Math.max(range, 1), totalMonths);
            const startIndex = totalMonths - appliedRange;
            return {
                labels: monthlyData.labels.slice(startIndex),
                income: monthlyData.income.slice(startIndex),
                expense: monthlyData.expense.slice(startIndex),
            };
        };

        const createGradient = (ctx, color) => {
            const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
            gradient.addColorStop(0, hexToRgba(color, 0.55));
            gradient.addColorStop(1, hexToRgba(color, 0.05));
            return gradient;
        };

        const renderIncomeExpenseChart = (range, type) => {
            if (!incomeExpenseCanvas || totalMonths === 0 || typeof Chart === 'undefined') {
                return;
            }

            const ctx = incomeExpenseCanvas.getContext('2d');
            const subset = sliceMonthlyData(range);
            currentRange = subset.labels.length;

            if (incomeExpenseChart) {
                incomeExpenseChart.destroy();
            }

            const isLine = type === 'line';
            const incomeColor = '#16a34a';
            const expenseColor = '#dc2626';

            incomeExpenseChart = new Chart(ctx, {
                type: type,
                data: {
                    labels: subset.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: subset.income,
                            borderColor: incomeColor,
                            backgroundColor: isLine ? createGradient(ctx, incomeColor) : hexToRgba(incomeColor, 0.75),
                            fill: isLine,
                            tension: isLine ? 0.35 : 0,
                            borderWidth: isLine ? 3 : 0,
                            pointRadius: isLine ? 4 : 0,
                            pointHoverRadius: isLine ? 6 : 0,
                            maxBarThickness: isLine ? undefined : 42,
                            borderRadius: isLine ? undefined : 10,
                        },
                        {
                            label: 'Expense',
                            data: subset.expense,
                            borderColor: expenseColor,
                            backgroundColor: isLine ? createGradient(ctx, expenseColor) : hexToRgba(expenseColor, 0.75),
                            fill: isLine,
                            tension: isLine ? 0.35 : 0,
                            borderWidth: isLine ? 3 : 0,
                            pointRadius: isLine ? 4 : 0,
                            pointHoverRadius: isLine ? 6 : 0,
                            maxBarThickness: isLine ? undefined : 42,
                            borderRadius: isLine ? undefined : 10,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'start',
                            labels: { usePointStyle: true, padding: 18 },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            callbacks: {
                                label: context => {
                                    const label = context.dataset.label || '';
                                    const value = Number(context.parsed.y ?? context.parsed ?? 0);
                                    return `${label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148, 163, 184, 0.25)',
                                borderDash: [6, 6],
                                drawBorder: false,
                            },
                            ticks: {
                                callback: value => `$${Number(value).toLocaleString()}`,
                            },
                        },
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, padding: 8 },
                        },
                    },
                },
            });

            if (chartRangeLabel && subset.labels.length) {
                chartRangeLabel.textContent = `Last ${subset.labels.length} month${subset.labels.length === 1 ? '' : 's'}`;
            }
        };

        disableUnavailableRangeButtons();

        if (totalMonths > 0) {
            if (totalMonths >= 6) {
                currentRange = 6;
                setActiveRangeButton(6, false);
            } else {
                currentRange = totalMonths;
                setActiveRangeButton(totalMonths, true);
            }
            setActiveChartTypeButton(currentChartType);
            renderIncomeExpenseChart(currentRange, currentChartType);
        } else if (chartRangeLabel) {
            chartRangeLabel.textContent = 'No data available yet';
        }

        rangeButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (button.disabled) return;
                const key = button.dataset.range;
                let sanitizedRange = totalMonths || 1;
                let useAll = false;

                if (key !== 'all') {
                    sanitizedRange = Math.min(Math.max(Number(key), 1), totalMonths || 1);
                } else {
                    useAll = true;
                    sanitizedRange = totalMonths || 1;
                }

                currentRange = sanitizedRange;
                renderIncomeExpenseChart(currentRange, currentChartType);
                setActiveRangeButton(sanitizedRange, useAll);
            });
        });

        chartTypeButtons.forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.chartType;
                if (type === currentChartType) return;
                currentChartType = type;
                setActiveChartTypeButton(currentChartType);
                renderIncomeExpenseChart(currentRange || totalMonths || 1, currentChartType);
            });
        });

        if (categoryCanvas && Array.isArray(categoryData.labels) && categoryData.labels.length) {
            const ctx = categoryCanvas.getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.labels,
                    datasets: [{
                        data: categoryData.values,
                        backgroundColor: categoryData.colors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            callbacks: {
                                label: context => {
                                    const value = Number(context.parsed || 0);
                                    const total = context.dataset.data.reduce((sum, val) => sum + Number(val || 0), 0);
                                    const percentage = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${context.label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${percentage}%)`;
                                },
                            },
                        },
                    },
                },
            });
        }

        // Initialize Bootstrap tooltips (if Bootstrap JS is present)
        try {
            if (typeof bootstrap !== 'undefined') {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });
            }
        } catch (e) {
            console.warn('Tooltip init failed', e);
        }

        // Keyboard activation (Enter / Space) for toolbar buttons
        const addKeyboardActivation = (btn) => {
            btn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
            });
        };
        rangeButtons.forEach(addKeyboardActivation);
        chartTypeButtons.forEach(addKeyboardActivation);

        // Server-side Recent Transactions search + pagination
        const txSearch = document.getElementById('tx-search');
        const txPerPage = document.getElementById('tx-per-page');
        const txTbody = document.querySelector('.table-responsive table tbody');
        const transactionsEndpoint = new URL("{{ route('user.transactions') }}", window.location.origin);

        const escapeHtml = (s) => String(s || '').replace(/[&<>"'`=\/]/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'\/','`':'&#96;','=':'&#61;'})[c]; });

        const renderTransactions = (items) => {
            if (!txTbody) return;
            if (!items || items.length === 0) {
                txTbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No transactions yet. Start tracking your expenses!</td></tr>`;
                return;
            }
            txTbody.innerHTML = items.map(t => `
                <tr class="transaction-row">
                    <td><span class="badge ${t.is_income ? 'bg-success' : 'bg-danger'} text-uppercase">${t.type}</span></td>
                    <td>${escapeHtml(t.description || '—')}</td>
                    <td>${t.category_name ? `<span class="badge bg-secondary">${escapeHtml(t.category_name)}</span>` : ''}</td>
                    <td><strong class="${t.is_income ? 'text-success' : 'text-danger'}">${escapeHtml(t.display_amount)}</strong></td>
                    <td>${escapeHtml(t.display_date || '—')}</td>
                </tr>
            `).join('');
        };

        // small debounce
        const debounce = (fn, wait = 250) => {
            let t;
            return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
        };

        const fetchAndRender = async () => {
            const q = txSearch?.value?.trim() || '';
            const per = Number(txPerPage?.value || 10);
            const url = new URL(transactionsEndpoint.href);
            url.searchParams.set('per_page', per);
            if (q) url.searchParams.set('q', q);
            try {
                const resp = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) return;
                const json = await resp.json();
                renderTransactions(json.data || []);
            } catch (e) {
                console.warn('Failed to fetch transactions', e);
            }
        };

        if (txSearch) txSearch.addEventListener('input', debounce(fetchAndRender, 300));
        if (txPerPage) txPerPage.addEventListener('change', fetchAndRender);
        // initial load
        fetchAndRender();
    });
</script>
@endpush

@push('styles')
<style>
    .control-pill:focus { outline: 3px solid rgba(59,130,246,0.25); outline-offset: 2px; }
    .control-pill.disabled { opacity: .5; pointer-events: none; }
</style>
@endpush
