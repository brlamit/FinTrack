@extends('layouts.user')

@section('title', 'My Budgets')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Budgets</h2>
        <a href="#" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Create Budget
        </a>
    </div>

    <div class="row">
        @forelse($budgets as $budget)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">{{ $budget->category->name ?? 'General' }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Spent</span>
                            <strong>${{ number_format($budget->spent ?? 0, 2) }} / ${{ number_format($budget->limit_amount, 2) }}</strong>
                        </div>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: {{ min((($budget->spent ?? 0) / $budget->limit_amount * 100), 100) }}%"
                                 aria-valuenow="{{ min((($budget->spent ?? 0) / $budget->limit_amount * 100), 100) }}" 
                                 aria-valuemin="0" aria-valuemax="100">
                                {{ round(min((($budget->spent ?? 0) / $budget->limit_amount * 100), 100)) }}%
                            </div>
                        </div>
                        <p class="text-muted text-sm mb-3">
                            {{ $budget->period === 'monthly' ? 'Monthly' : 'Weekly' }} budget
                        </p>
                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="#" class="btn btn-sm btn-outline-danger">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <p>No budgets created yet. <a href="#">Create your first budget</a></p>
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
