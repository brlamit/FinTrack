@extends('layouts.admin')

@section('title', 'My Transactions')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Transactions</h2>
        <a href="{{ route('user.transactions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add Transaction
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->description }}</td>
                            <td>
                                @if($transaction->category)
                                    <span class="badge bg-secondary">{{ $transaction->category->name }}</span>
                                @endif
                            </td>
                            <td><strong>${{ number_format($transaction->amount, 2) }}</strong></td>
                            <td>{{ $transaction->date->format('M d, Y') }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="#" class="btn btn-sm btn-outline-danger">Delete</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No transactions yet. <a href="{{ route('user.transactions.create') }}">Add your first transaction</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($transactions->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
