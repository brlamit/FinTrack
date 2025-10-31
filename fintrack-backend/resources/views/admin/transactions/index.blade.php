@extends('layouts.admin')

@section('title', 'Transactions Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Transactions Management</h2>
</div>

<form method="GET" class="mb-4">
    <div class="row">
        <div class="col-md-3">
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-3">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Category</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->user->name }}</td>
                <td>
                    <span class="badge bg-{{ $transaction->type === 'income' ? 'success' : 'danger' }}">
                        {{ ucfirst($transaction->type) }}
                    </span>
                </td>
                <td>${{ number_format($transaction->amount, 2) }}</td>
                <td>{{ $transaction->category->name ?? 'N/A' }}</td>
                <td>{{ $transaction->description }}</td>
                <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{ $transactions->appends(request()->query())->links() }}
@endsection