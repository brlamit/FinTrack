@php
    $goalId = data_get($goal, 'id');
    $name = data_get($goal, 'name', 'Unnamed Goal');
    $progress = (float) data_get($goal, 'progress', 0);
    $currentFormatted = data_get($goal, 'current_amount_formatted', '$0.00');
    $targetFormatted = data_get($goal, 'target_amount_formatted', '$0.00');
    $remainingFormatted = data_get($goal, 'remaining_formatted', '$0.00');
    $targetDate = data_get($goal, 'target_date', '—');
    $daysRemaining = data_get($goal, 'days_remaining');
    $isOverdue = data_get($goal, 'is_overdue', false);
    $statusClass = data_get($goal, 'status_class', 'text-warning');
    $statusBadge = data_get($goal, 'status_badge', 'In Progress');
@endphp

<div class="card h-100 border-0 shadow-sm">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-1 fw-semibold">{{ $name }}</h6>
                <small class="text-muted">Target: {{ $targetDate }}</small>
            </div>
            <span class="badge bg-{{ $isOverdue ? 'danger' : ($progress >= 100 ? 'success' : 'warning') }}">{{ $statusBadge }}</span>
        </div>

        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar" role="progressbar" style="width: {{ min(max($progress, 0), 100) }}%;" 
                 aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="mb-2 small">
            <div class="d-flex justify-content-between text-muted">
                <span>Progress</span>
                <strong>{{ round($progress, 1) }}%</strong>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <span class="text-muted">{{ $currentFormatted }} / {{ $targetFormatted }}</span>
            </div>
        </div>

        <div class="mt-auto pt-2 border-top">
            <div class="d-flex justify-content-between text-muted small">
                <span>Remaining: {{ $remainingFormatted }}</span>
                @if($daysRemaining !== null)
                    <span class="{{ $daysRemaining < 0 ? 'text-danger' : 'text-success' }}">
                        {{ abs($daysRemaining) }} days {{ $daysRemaining < 0 ? 'overdue' : 'left' }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>