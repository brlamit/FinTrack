@extends('layouts.admin')

@section('title', 'Groups Management')

@section('content')
@php
    $currency = fn (float $value) => '$' . number_format($value, 2);
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h2 class="mb-0">Groups Management</h2>
        <p class="text-muted mb-0">Monitor activity across every shared group and drill into detailed records.</p>
    </div>
    <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">Create New Group</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-3 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Total Groups</p>
                <h4 class="fw-semibold mb-0">{{ number_format($groupMetrics['groups']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Members</p>
                <h4 class="fw-semibold mb-0">{{ number_format($groupMetrics['members']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Transactions</p>
                <h4 class="fw-semibold mb-0">{{ number_format($groupMetrics['transactions']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Income</p>
                <h4 class="fw-semibold mb-0">{{ $currency($groupMetrics['income']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Expense</p>
                <h4 class="fw-semibold mb-0">{{ $currency($groupMetrics['expense']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-3 col-sm-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Net Flow</p>
                <h4 class="fw-semibold mb-0 {{ $groupMetrics['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $currency($groupMetrics['net']) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Group</th>
                        <th scope="col">Owner</th>
                        <th scope="col">Created</th>
                        <th scope="col" class="text-center">Members</th>
                        <th scope="col" class="text-center">Transactions</th>
                        <th scope="col" class="text-end">Income</th>
                        <th scope="col" class="text-end">Expense</th>
                        <th scope="col" class="text-end">Net</th>
                        <th scope="col" class="text-end">Budget Limit</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $group->name }}</div>
                                <div class="text-muted small">{{ ucfirst($group->type) }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $group->owner->name }}</div>
                                <div class="text-muted small">{{ $group->owner->email }}</div>
                            </td>
                            <td class="text-muted small">{{ $group->created_at?->format('M d, Y') }}</td>
                            <td class="text-center fw-semibold">{{ number_format($group->members_count) }}</td>
                            <td class="text-center">{{ number_format($group->shared_transactions_count) }}</td>
                            <td class="text-end text-success">{{ $currency($group->income_total ?? 0) }}</td>
                            <td class="text-end text-danger">{{ $currency($group->expense_total ?? 0) }}</td>
                            <td class="text-end {{ ($group->income_total - $group->expense_total) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $currency(($group->income_total ?? 0) - ($group->expense_total ?? 0)) }}
                            </td>
                            <td class="text-end">{{ $group->budget_limit ? $currency($group->budget_limit) : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-sm btn-outline-primary">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No groups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection