@extends('layouts.user')

@section('title', 'My Budgets')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Budgets</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
            <i class="fas fa-plus-circle"></i> Create Budget
        </button>
    </div>

    <!-- Create Budget Modal -->
    <div class="modal fade" id="createBudgetModal" tabindex="-1" aria-labelledby="createBudgetLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('user.budgets.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createBudgetLabel">Create Budget</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input name="name" class="form-control" value="{{ old('name') }}" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Amount</label>
                                <input name="amount" type="number" step="0.01" class="form-control" value="{{ old('amount') }}" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Period</label>
                                <select name="period" class="form-select" required>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">General</option>
                                    @foreach($categories ?? [] as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input name="start_date" type="date" class="form-control" value="{{ old('start_date', now()->startOfMonth()->toDateString()) }}" required />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input name="end_date" type="date" class="form-control" value="{{ old('end_date', now()->endOfMonth()->toDateString()) }}" required />
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alert thresholds (comma-separated percentages)</label>
                                <input name="alert_thresholds" class="form-control" value="{{ old('alert_thresholds', '50,75,90') }}" placeholder="e.g. 50,75,90" />
                                <small class="text-muted">Values will be parsed as percentages.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Budget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($budgets as $budget)
            @php
                $spent = (float) ($budget->spent ?? 0);
                $limit = (float) ($budget->limit_amount ?? 0);
                $percent = $limit > 0 ? min(($spent / $limit) * 100, 100) : 0;
                $remaining = $limit - $spent;
                if ($remaining < 0) { $remaining = 0; }
                $barClass = 'bg-success';
                if ($percent >= 90) { $barClass = 'bg-danger'; }
                elseif ($percent >= 60) { $barClass = 'bg-warning'; }
            @endphp

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">{{ $budget->name }}</h6>
                                <div class="text-muted small">{{ $budget->category->name ?? 'General' }}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary">{{ ucfirst($budget->period) }}</span>
                                @if(!$budget->is_active)
                                    <span class="badge bg-secondary ms-1">Paused</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3 text-muted small">
                                <div>Limit</div>
                                <div class="fw-bold">${{ number_format($limit, 2) }}</div>
                            </div>
                            <div class="me-3 text-muted small">
                                <div>Spent</div>
                                <div class="fw-bold text-{{ $percent >= 90 ? 'danger' : ($percent >= 60 ? 'warning' : 'success') }}">${{ number_format($spent, 2) }}</div>
                            </div>
                            <div class="text-muted small ms-auto text-end">
                                <div>Remaining</div>
                                <div class="fw-bold">${{ number_format($remaining, 2) }}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="progress" style="height:18px;">
                                <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ round($percent, 2) }}%;" aria-valuenow="{{ round($percent, 2) }}" aria-valuemin="0" aria-valuemax="100">
                                    <small class="ps-2">{{ round($percent) }}%</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2 small text-muted">
                            <strong class="me-2">Period:</strong> {{ ucfirst($budget->period) }}
                            <span class="mx-2">•</span>
                            <strong class="me-2">From:</strong> {{ Illuminate\Support\Carbon::parse($budget->start_date)->format('M j, Y') }}
                            <span class="mx-2">•</span>
                            <strong class="me-2">To:</strong> {{ Illuminate\Support\Carbon::parse($budget->end_date)->format('M j, Y') }}
                        </div>

                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                <strong>Alerts:</strong>
                                @if(is_array($budget->alert_thresholds) && count($budget->alert_thresholds))
                                    @foreach($budget->alert_thresholds as $t)
                                        <span class="badge bg-light text-dark border me-1">{{ (int)$t }}%</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editBudgetModal-{{ $budget->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <form method="POST" action="{{ route('user.budgets.destroy', $budget) }}" onsubmit="return confirm('Delete this budget?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Budget Modal (unchanged form content) -->
            <div class="modal fade" id="editBudgetModal-{{ $budget->id }}" tabindex="-1" aria-labelledby="editBudgetLabel-{{ $budget->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('user.budgets.update', $budget) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editBudgetLabel-{{ $budget->id }}">Edit Budget</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Name</label>
                                        <input name="name" class="form-control" value="{{ old('name', $budget->name) }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Amount</label>
                                        <input name="amount" type="number" step="0.01" class="form-control" value="{{ old('amount', $budget->amount) }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Period</label>
                                        <select name="period" class="form-select" required>
                                            <option value="weekly" {{ $budget->period === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                            <option value="monthly" {{ $budget->period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ $budget->period === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="yearly" {{ $budget->period === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Category</label>
                                        <select name="category_id" class="form-select">
                                            <option value="">General</option>
                                            @foreach($categories ?? [] as $cat)
                                                <option value="{{ $cat->id }}" {{ $budget->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Start Date</label>
                                        <input name="start_date" type="date" class="form-control" value="{{ old('start_date', optional($budget->start_date)->toDateString() ?? $budget->start_date) }}" required />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">End Date</label>
                                        <input name="end_date" type="date" class="form-control" value="{{ old('end_date', optional($budget->end_date)->toDateString() ?? $budget->end_date) }}" required />
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Alert thresholds (comma-separated percentages)</label>
                                        <input name="alert_thresholds" class="form-control" value="{{ old('alert_thresholds', is_array($budget->alert_thresholds) ? implode(',', $budget->alert_thresholds) : $budget->alert_thresholds) }}" placeholder="e.g. 50,75,90" />
                                        <small class="text-muted">Values will be parsed as percentages.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card text-center border-0 shadow-sm p-5">
                    <h4 class="mb-3">No budgets yet</h4>
                    <p class="text-muted mb-4">Create budgets to track your spending and get alerts when you approach your limits.</p>
                    <button class="btn btn-lg btn-primary" data-bs-toggle="modal" data-bs-target="#createBudgetModal">
                        <i class="fas fa-plus-circle me-2"></i>Create your first budget
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($budgets->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $budgets->links() }}
        </div>
    @endif
</div>
@endsection
