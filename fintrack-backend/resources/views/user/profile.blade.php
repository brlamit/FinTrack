@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="rounded-circle" width="100" height="100">
                            @else
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; margin: 0 auto;">
                                    <span style="font-size: 48px;">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <h5>{{ auth()->user()->name }}</h5>
                        <p class="text-muted">@{{ auth()->user()->username }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <p class="fw-semibold">{{ auth()->user()->email }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Phone</label>
                        <p class="fw-semibold">{{ auth()->user()->phone ?? 'Not provided' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Status</label>
                        <p>
                            <span class="badge bg-{{ auth()->user()->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst(auth()->user()->status) }}
                            </span>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Member Since</label>
                        <p class="fw-semibold">{{ auth()->user()->created_at->format('M d, Y') }}</p>
                    </div>

                    <hr>

                    <a href="{{ route('user.edit') }}" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="{{ route('user.security') }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-lock"></i> Security Settings
                    </a>
                    <a href="{{ route('user.preferences') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-cog"></i> Preferences
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-2">Total Expenses</h6>
                                <h4 class="text-primary">{{ $totalExpenses ?? '$0.00' }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-2">This Month</h6>
                                <h4 class="text-success">{{ $thisMonthExpenses ?? '$0.00' }}</h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-2">Groups</h6>
                                <h4 class="text-info">{{ $groupCount ?? 0 }}</h4>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Recent Transactions</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions ?? [] as $transaction)
                                    <tr>
                                        <td>{{ $transaction->description }}</td>
                                        <td>
                                            @if($transaction->category)
                                                <span class="badge bg-secondary">{{ $transaction->category->name }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">${{ number_format($transaction->amount, 2) }}</td>
                                        <td>{{ $transaction->date->format('M d, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No recent transactions</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Connected Groups -->
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Your Groups</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($userGroups ?? [] as $group)
                            <div class="col-md-6 mb-3">
                                <div class="card border-left-primary">
                                    <div class="card-body">
                                        <h6 class="font-weight-bold text-primary">{{ $group->name }}</h6>
                                        <p class="text-sm text-muted mb-2">
                                            <i class="fas fa-users"></i> 
                                            {{ $group->members->count() }} members
                                        </p>
                                        @if($group->description)
                                            <p class="text-sm">{{ Str::limit($group->description, 50) }}</p>
                                        @endif
                                        <a href="{{ route('user.group', $group) }}" class="btn btn-sm btn-outline-primary">
                                            View Group
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted text-center">You haven't joined any groups yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
