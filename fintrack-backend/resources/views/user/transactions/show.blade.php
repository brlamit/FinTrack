@extends('layouts.user')

@section('title', 'Transaction')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Transaction Details</h3>
            <p class="text-muted small mb-0">{{ $transaction->description ?? '—' }}</p>
        </div>
        <div>
            @if($transaction->group)
                <a href="{{ route('user.group.transactions', $transaction->group) }}" class="btn btn-outline-secondary">Back to Transactions</a>
            @else
                <a href="{{ route('user.transactions') }}" class="btn btn-outline-secondary">Back to Transactions</a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <h4 class="mb-2">{{ $transaction->description ?? 'No description' }}</h4>
                    <p class="mb-1 text-muted">Category: {{ $transaction->category->name ?? '—' }}</p>
                    <p class="mb-1 text-muted">Recorded by: {{ $transaction->user->name ?? '—' }}</p>
                    <p class="mb-1 text-muted">Type: <strong class="text-uppercase">{{ $transaction->type }}</strong></p>
                    <p class="mb-1 text-muted">Date: {{ ($transaction->transaction_date ?? $transaction->created_at)?->format('M d, Y') }}</p>
                    <p class="mb-1">Amount: <strong class="{{ $transaction->type === 'income' ? 'text-success' : 'text-danger' }}">${{ number_format($transaction->amount, 2) }}</strong></p>
                    @if($transaction->tags)
                        <p class="mb-1 text-muted">Tags: {{ implode(', ', (array) $transaction->tags) }}</p>
                    @endif
                </div>
                <div class="col-md-4">
                    @if($transaction->receipt)
                        @php
                            $img = $transaction->receipt->path ?? null;
                        @endphp
                        @if($img)
                            <img src="{{ $img }}" alt="Receipt" class="img-fluid rounded" style="max-height:260px; object-fit:cover;">
                        @else
                            <div class="text-muted small">Receipt not available.</div>
                        @endif
                    @else
                        <div class="text-muted small">No receipt attached.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
