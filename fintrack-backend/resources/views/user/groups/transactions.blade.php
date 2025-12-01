@extends('layouts.user')

@section('title', 'Group Transactions - ' . $group->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Transactions — {{ $group->name }}</h3>
            <p class="text-muted small mb-0">All transactions shared within this group.</p>
        </div>
        <div>
            <a href="{{ route('user.group', $group) }}" class="btn btn-outline-secondary">Back to Group</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Description</th>
                            <th scope="col">Type</th>
                            <th scope="col">Receipt</th>
                            <th scope="col">Recorded By</th>
                            <th scope="col">Category</th>
                            <th scope="col" class="text-end">Amount</th>
                            <th scope="col" class="text-end">Date</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            @php $isIncome = $transaction->type === 'income'; $date = $transaction->transaction_date ?? $transaction->created_at; @endphp
                            <tr>
                                <td>{{ $transaction->description ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $isIncome ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                        {{ $isIncome ? 'Income' : 'Expense' }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->receipt)
                                        @php
                                            $img = $transaction->receipt->path ?? null;
                                        @endphp
                                        @if($img)
                                            <a href="{{ $img }}" target="_blank" class="text-decoration-none">View Receipt</a>
                                        @else
                                            <span class="text-muted small">N/A</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">No receipt</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->user->name ?? '—' }}</td>
                                <td>{{ $transaction->category->name ?? '—' }}</td>
                                <td class="text-end {{ $isIncome ? 'text-success' : 'text-danger' }}">${{ number_format($transaction->amount, 2) }}</td>
                                <td class="text-end text-muted small">{{ $date?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('user.transaction.show', $transaction) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No transactions found for this group.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $transactions->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
