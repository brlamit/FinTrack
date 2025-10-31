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
                        <span class="badge bg-primary rounded-pill">{{ $user->created_at->diffForHumans() }}</span>
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