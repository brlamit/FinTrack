@php
    $spent = data_get($budget, 'spent_formatted', '$0.00');
    $limit = data_get($budget, 'limit_formatted', '$0.00');
    $progress = (float) data_get($budget, 'progress', 0);
    $remaining = data_get($budget, 'remaining_formatted', '$0.00');
    $statusLabel = data_get($budget, 'status_label', 'On track');
    $statusClass = data_get($budget, 'status_class', 'text-success');
    $label = data_get($budget, 'label', 'General');
    $budgetId = data_get($budget, 'id');
@endphp

<div class="card h-100 border-0 shadow-sm">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div class="small text-muted">{{ $label }}</div>
                <div class="h6 fw-bold mt-1">{{ $spent }} / {{ $limit }}</div>
            </div>
            <div class="text-end">
                <div class="small {{ $statusClass }} fw-semibold">{{ $statusLabel }}</div>
                @if(!empty($budgetId))
                    <a href="{{ route('user.budgets') }}#budget-{{ $budgetId }}" class="small text-decoration-none">Manage</a>
                @endif
            </div>
        </div>

        <div class="progress mb-2" style="height:8px;">
            <div class="progress-bar bg-gradient" role="progressbar" style="width: {{ min(max($progress,0),100) }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-auto small text-muted">
            <div>Remaining {{ $remaining }}</div>
            <div>{{ round($progress,1) }}% used</div>
        </div>
    </div>
</div>
