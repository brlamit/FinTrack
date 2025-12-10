@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #1e3a8a, #1d4ed8 55%, #22d3ee);
        color: #ffffff;
        border: none;
        overflow: hidden;
        position: relative;
    }
    .dashboard-hero .badge {
        letter-spacing: 0.08em;
    }
    .dashboard-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 55%),
                    radial-gradient(circle at bottom left, rgba(56, 189, 248, 0.2), transparent 60%);
        opacity: 0.9;
        pointer-events: none;
    }
    .dashboard-hero .card-body {
        position: relative;
        z-index: 1;
    }
    .stat-card {
        border: none;
        border-radius: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 38px -22px rgba(15, 23, 42, 0.45);
    }
    .stat-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(148, 163, 184, 0.07), transparent 55%);
        pointer-events: none;
    }
    .stat-card .card-body {
        position: relative;
        z-index: 1;
    }
    .icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .icon-wrapper.accent-primary {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(37, 99, 235, 0.32));
        color: #1d4ed8;
    }
    .icon-wrapper.accent-success {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.18), rgba(22, 163, 74, 0.32));
        color: #15803d;
    }
    .icon-wrapper.accent-info {
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(2, 132, 199, 0.32));
        color: #0369a1;
    }
    .icon-wrapper.accent-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.18), rgba(217, 119, 6, 0.32));
        color: #b45309;
    }
    .badge-trend {
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 999px;
        padding: 0.25rem 0.75rem;
    }
    .badge-trend.up {
        background-color: rgba(34, 197, 94, 0.18);
        color: #15803d;
    }
    .badge-trend.down {
        background-color: rgba(220, 38, 38, 0.18);
        color: #991b1b;
    }
    .badge-trend.flat {
        background-color: rgba(148, 163, 184, 0.22);
        color: #475569;
    }
    .trend-progress {
        height: 6px;
        border-radius: 999px;
        background-color: rgba(15, 23, 42, 0.08);
    }
    .trend-progress .progress-bar {
        border-radius: 999px;
    }
    .chart-toolbar .control-pill {
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.4);
        color: #475569;
        background-color: #ffffff;
        padding: 0.4rem 1.1rem;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }
    .chart-toolbar .control-pill:hover {
        background: rgba(29, 78, 216, 0.08);
        color: #1d4ed8;
        border-color: rgba(29, 78, 216, 0.45);
    }
    .chart-toolbar .control-pill.active {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
        box-shadow: 0 12px 18px -12px rgba(37, 99, 235, 0.6);
    }
    .chart-toolbar .control-pill.disabled,
    .chart-toolbar .control-pill:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .chart-wrapper {
        position: relative;
        height: 320px;
        border-radius: 18px;
        background: rgba(15, 23, 42, 0.03);
        padding: 1rem;
    }
    .chart-wrapper canvas {
        width: 100% !important;
        height: 100% !important;
    }
    .category-legend .legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 0.85rem;
        border-radius: 0.85rem;
        background-color: rgba(148, 163, 184, 0.12);
        margin-bottom: 0.75rem;
    }
    .category-legend .legend-item:last-child {
        margin-bottom: 0;
    }
    .category-legend .color-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 0.6rem;
    }
    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(37, 99, 235, 0.08));
        color: #1d4ed8;
    }
    .timeline {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }
    .timeline-item {
        position: relative;
        padding-left: 2.25rem;
        padding-bottom: 1.35rem;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 1.05rem;
        top: 0.75rem;
        width: 2px;
        height: 100%;
        background: rgba(148, 163, 184, 0.35);
    }
    .timeline-item:last-child::before {
        display: none;
    }
    .timeline-dot {
        position: absolute;
        left: 0.55rem;
        top: 0.4rem;
        width: 1.1rem;
        height: 1.1rem;
        border-radius: 50%;
    }
    .timeline-dot.income {
        background: #16a34a;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.2);
    }
    .timeline-dot.expense {
        background: #dc2626;
        box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.2);
    }
    .progress-bar.bg-gradient {
        background: linear-gradient(135deg, #4338ca, #22d3ee);
    }
    .callout-banner {
        border-radius: 1.5rem;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.14), rgba(16, 185, 129, 0.14));
        border: 1px solid rgba(59, 130, 246, 0.2);
        padding: 1.75rem;
    }
    .callout-banner h6 {
        letter-spacing: 0.08em;
    }
    @media (max-width: 575.98px) {
        .dashboard-hero {
            text-align: center;
        }
        .dashboard-hero .d-flex {
            flex-direction: column !important;
            align-items: flex-start;
        }
        .chart-toolbar {
            width: 100%;
            justify-content: space-between;
            gap: 0.75rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $recentUsers = $stats['recent_users'];
    $recentTransactions = $stats['recent_transactions'];
    $topGroups = $stats['top_groups'];
    $platformVolumeCard = collect($stats['insight_cards'])->firstWhere('title', 'Platform Volume');
    $netCashflow = $platformVolumeCard['net']['current'] ?? null;
    $activeMembersCard = collect($stats['insight_cards'])->firstWhere('title', 'Active Members');
    $categoryLabels = $stats['chartData']['category']['labels'] ?? [];
    $categoryValues = $stats['chartData']['category']['values'] ?? [];
    $categoryColors = $stats['chartData']['category']['colors'] ?? [];
    $categoryTotal = array_sum($categoryValues);
    $monthlyIncomeSeries = $stats['chartData']['monthly']['income'] ?? [];
    $latestMonthlyIncome = !empty($monthlyIncomeSeries) ? $monthlyIncomeSeries[array_key_last($monthlyIncomeSeries)] : 0;
@endphp
<div class="card dashboard-hero shadow-sm mb-4">
    <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <span class="badge bg-white text-dark fw-semibold mb-3">ADMIN OVERVIEW</span>
                <h1 class="h2 fw-semibold mb-2">Welcome back, {{ auth()->user()->name }}.</h1>
                <p class="mb-0 text-white-50">Monitor FinTrack performance, member engagement, and cash flow trends at a glance.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.reports') }}" class="btn btn-light btn-lg px-4 shadow-sm">
                    <i class="fas fa-chart-line me-2"></i>View Reports
                </a>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-light btn-lg px-4 shadow-sm">
                    <i class="fas fa-users-cog me-2"></i>Manage Groups
                </a>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3 mt-4 text-white-50 small">
            <span class="d-inline-flex align-items-center"><i class="far fa-clock me-2"></i>Last updated {{ now()->format('M j, Y g:i A') }}</span>
            <span class="vr opacity-50 d-none d-sm-block"></span>
            <span class="d-inline-flex align-items-center"><i class="fas fa-arrow-trend-up me-2"></i>{{ number_format($stats['monthly_context']['current_transaction_count']) }} transactions this month</span>
            @if(!is_null($activeMembersCard))
                <span class="vr opacity-50 d-none d-sm-block"></span>
                <span class="d-inline-flex align-items-center"><i class="fas fa-user-check me-2"></i>{{ number_format($activeMembersCard['value'] ?? 0) }} active members</span>
            @endif
            @if(!is_null($netCashflow))
                <span class="vr opacity-50 d-none d-sm-block"></span>
                <span class="d-inline-flex align-items-center"><i class="fas fa-coins me-2"></i>Net cashflow {{ ($netCashflow >= 0 ? '+' : '-') . '$' . number_format(abs($netCashflow), 2) }}</span>
            @endif
            @if(!empty($monthlyIncomeSeries))
                <span class="vr opacity-50 d-none d-sm-block"></span>
                <span class="d-inline-flex align-items-center"><i class="fas fa-wallet me-2"></i>Income last month ${{ number_format((float) $latestMonthlyIncome, 2) }}</span>
            @endif
        </div>
        <span class="badge bg-info">All transactions (group + personal)</span>
    </div>
</div>

<div class="row g-4 mb-4">
    @foreach($stats['insight_cards'] as $card)
        @php
            $valueFormatted = $card['format'] === 'currency'
                ? '$' . number_format($card['value'], 2)
                : number_format($card['value']);
            $trend = $card['trend'];
            $direction = $trend['direction'];
            $percentValue = $trend['percent'];
            $percentFormatted = $percentValue > 0
                ? '+' . number_format($percentValue, 1) . '%'
                : ($percentValue < 0 ? number_format($percentValue, 1) . '%' : '0.0%');
            $badgeClass = 'flat';
            if ($direction === 'up') {
                $badgeClass = 'up';
            } elseif ($direction === 'down') {
                $badgeClass = 'down';
            }
            $normalizedPercent = min(max(abs($percentValue), 0), 180);
        @endphp
        <div class="col-xxl-3 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase small fw-semibold">{{ $card['title'] }}</span>
                            <h2 class="mt-2 mb-3 fw-bold">{{ $valueFormatted }}</h2>
                            <div class="badge-trend {{ $badgeClass }} mb-2">{{ $percentFormatted }}</div>
                            <div class="text-muted small">{{ $card['detail_text'] }}</div>
                            @if(isset($card['trend']['comparison_label']))
                                <div class="text-muted small">{{ $card['trend']['comparison_label'] }}</div>
                            @endif
                            @if(isset($card['net']))
                                @php
                                    $netCurrent = $card['net']['current'];
                                    $netLabelClass = $netCurrent >= 0 ? 'text-success' : 'text-danger';
                                @endphp
                                <div class="small mt-3">
                                    <span class="text-muted">Net cashflow:</span>
                                    <span class="fw-semibold {{ $netLabelClass }}">{{ ($netCurrent >= 0 ? '+' : '-') . '$' . number_format(abs($netCurrent), 2) }}</span>
                                </div>
                            @endif
                            <div class="trend-progress progress mt-3">
                                <div class="progress-bar {{ $direction === 'down' ? 'bg-danger' : 'bg-success' }}" role="progressbar" style="width: {{ min($normalizedPercent, 100) }}%"></div>
                            </div>
                        </div>
                        <div class="icon-wrapper accent-{{ $card['accent'] }} ms-3">
                            <i class="fas {{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xxl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h5 class="mb-1">Platform Income vs Expense</h5>
                        <span class="text-muted small" data-range-label>Loading timeline...</span>
                    </div>
                    <div class="chart-toolbar d-flex flex-wrap gap-2">
                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Select range">
                            <button type="button" class="control-pill" data-range="3">3M</button>
                            <button type="button" class="control-pill" data-range="6">6M</button>
                            <button type="button" class="control-pill" data-range="12">12M</button>
                            <button type="button" class="control-pill" data-range="all">All</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2" role="group" aria-label="Chart type">
                            <button type="button" class="control-pill active" data-chart-type="line"><i class="fas fa-chart-line"></i></button>
                            <button type="button" class="control-pill" data-chart-type="bar"><i class="fas fa-chart-column"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="admin-income-expense-chart"></canvas>
                </div>
                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div>
                        <span class="text-muted text-uppercase small fw-semibold">Income (this month)</span>
                        <p class="mb-0 fw-semibold fs-5 text-success">${{ number_format($stats['monthly_context']['current_income'], 2) }}</p>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase small fw-semibold">Expense (this month)</span>
                        <p class="mb-0 fw-semibold fs-5 text-danger">${{ number_format($stats['monthly_context']['current_expense'], 2) }}</p>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase small fw-semibold">Transactions (this month)</span>
                        <p class="mb-0 fw-semibold fs-5">{{ number_format($stats['monthly_context']['current_transaction_count']) }}</p>
                    </div>
                </div>
                @if(!is_null($netCashflow))
                    <div class="d-inline-flex align-items-center px-3 py-2 rounded-pill bg-light text-muted small mt-4">
                        <i class="fas fa-coins me-2 text-warning"></i>
                        <span>Net cashflow this month:</span>
                        <span class="ms-2 fw-semibold {{ $netCashflow >= 0 ? 'text-success' : 'text-danger' }}">{{ ($netCashflow >= 0 ? '+' : '-') . '$' . number_format(abs($netCashflow), 2) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xxl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Expense Breakdown</h5>
                    <span class="text-muted small">Top categories</span>
                </div>
            </div>
            <div class="card-body">
                @if(!empty($categoryLabels))
                    <div style="height: 260px;">
                        <canvas id="admin-category-chart"></canvas>
                    </div>
                    <div class="category-legend mt-4">
                        @foreach($categoryLabels as $index => $label)
                            @php
                                $value = $categoryValues[$index] ?? 0;
                                $color = $categoryColors[$index] ?? '#6366f1';
                                $percentage = $categoryTotal > 0 ? ($value / $categoryTotal) * 100 : 0;
                            @endphp
                            <div class="legend-item">
                                <div class="d-flex align-items-center">
                                    <span class="color-dot" style="background-color: {{ $color }};"></span>
                                    <span class="fw-semibold">{{ $label }}</span>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">${{ number_format($value, 2) }}</div>
                                    <small class="text-muted">{{ number_format($percentage, 1) }}%</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center mb-0 py-4">Add expense transactions to see category distribution.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Users</h5>
                <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($recentUsers->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach($recentUsers as $user)
                            @php
                                $initial = strtoupper(mb_substr($user->name ?? 'U', 0, 1));
                            @endphp
                            <li class="list-group-item px-0">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">{{ $initial }}</div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    </div>
                                    <span class="badge bg-light text-muted border small"><i class="far fa-clock me-1"></i>{{ optional($user->created_at)->diffForHumans() }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted text-center mb-0">No users to display yet.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activity</h5>
                <a href="{{ route('admin.transactions') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                @if($recentTransactions->isNotEmpty())
                    <ul class="timeline">
                        @foreach($recentTransactions as $transaction)
                            @php
                                $isIncome = $transaction->type === 'income';
                                $amountDisplay = ($isIncome ? '+' : '-') . '$' . number_format((float) $transaction->amount, 2);
                                $userName = optional($transaction->user)->name ?? 'Unknown user';
                            @endphp
                            <li class="timeline-item">
                                <span class="timeline-dot {{ $isIncome ? 'income' : 'expense' }}"></span>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <div class="fw-semibold">{{ ucfirst($transaction->type) }} • {{ $userName }}</div>
                                        <div class="text-muted small">{{ optional($transaction->created_at)->diffForHumans() }}</div>
                                    </div>
                                    <div class="fw-semibold {{ $isIncome ? 'text-success' : 'text-danger' }}">{{ $amountDisplay }}</div>
                                </div>
                                @if($transaction->description)
                                    <div class="text-muted small mt-2">{{ \Illuminate\Support\Str::limit($transaction->description, 80) }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted text-center mb-0">Recent transactions will appear here.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Top Performing Groups</h5>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-sm btn-outline-primary">Manage</a>
            </div>
            <div class="card-body">
                @if($topGroups->isNotEmpty())
                    @php
                        $maxGroupTotal = $topGroups->max('total_shared_amount') ?: 1;
                    @endphp
                    <div class="list-group list-group-flush">
                        @foreach($topGroups as $group)
                            @php
                                $share = (float) ($group->total_shared_amount ?? 0);
                                $percent = $maxGroupTotal > 0 ? ($share / $maxGroupTotal) * 100 : 0;
                            @endphp
                            <div class="list-group-item border-0 px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-semibold">{{ $group->name }}</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-user me-1"></i>{{ $group->members_count }} members
                                            @if($group->owner)
                                                <span class="ms-2"><i class="fas fa-crown me-1"></i>{{ $group->owner->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="fw-semibold">${{ number_format($share, 2) }}</span>
                                </div>
                                <div class="progress rounded-pill mt-3" style="height: 8px;">
                                    <div class="progress-bar bg-gradient" role="progressbar" style="width: {{ number_format($percent, 2) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted text-center mb-0">Create group activity to populate this leaderboard.</p>
                @endif
            </div>
            <div class="callout-banner mt-4">
                <h6 class="text-uppercase text-primary fw-semibold mb-2">Optimize Engagement</h6>
                <p class="text-muted mb-3">Encourage underperforming groups and nudge individuals who haven’t logged a transaction recently.</p>
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.groups.send-engagement-reminders') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">
                            <i class="fas fa-people-group me-2"></i>Group reminders
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.engagement.send-personal-reminders') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                            <i class="fas fa-user-clock me-2"></i>Personal nudges
                        </button>
                    </form>
                </div>
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
        const monthlyData = chartData.monthly || { labels: [], income: [], expense: [] };
        const categoryData = chartData.category || { labels: [], values: [], colors: [] };
        const incomeExpenseCanvas = document.getElementById('admin-income-expense-chart');
        const categoryCanvas = document.getElementById('admin-category-chart');
        const rangeButtons = document.querySelectorAll('[data-range]');
        const chartTypeButtons = document.querySelectorAll('[data-chart-type]');
        const chartRangeLabel = document.querySelector('[data-range-label]');
        const totalMonths = Array.isArray(monthlyData.labels) ? monthlyData.labels.length : 0;
        let currentRange = totalMonths >= 6 ? 6 : totalMonths;
        let currentChartType = 'line';
        let incomeExpenseChart;

        const hexToRgba = (hex, alpha) => {
            const sanitized = hex.replace('#', '');
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
                if (button.disabled) {
                    return;
                }
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
                if (type === currentChartType) {
                    return;
                }
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
    });
</script>
@endpush
