@extends('layouts.admin')

@section('title', $group->name)

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <a href="{{ route('user.groups') }}" class="btn btn-outline-secondary mb-3">
        <i class="fas fa-arrow-left"></i> Back to Groups
    </a>

    <div class="row">
        <!-- Group Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ $group->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Type</h6>
                            <p>{{ ucfirst($group->type) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Owner</h6>
                            <p>{{ $group->owner->name }}</p>
                        </div>
                    </div>
                    @if($group->description)
                        <div class="mb-3">
                            <h6 class="text-muted">Description</h6>
                            <p>{{ $group->description }}</p>
                        </div>
                    @endif
                    @if($group->budget_limit)
                        <div class="mb-3">
                            <h6 class="text-muted">Budget Limit</h6>
                            <p>${{ number_format($group->budget_limit, 2) }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Group Transactions -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Group Transactions</h5>
                </div>
                <!-- Add Expense Form -->
                <div class="card-body border-bottom">
                    <form action="{{ route('groups.split', $group) }}" method="POST" enctype="multipart/form-data" id="add-expense-form">
                        @csrf
                        <input type="hidden" name="split_type" id="split-type" value="custom">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Total amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" id="total-amount" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control">
                            </div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Receipt (optional)</label>
                                <input type="file" name="receipt" accept="image/*" class="form-control">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="split-equally">Split equally</button>
                                <button type="submit" class="btn btn-primary">Add & Split</button>
                            </div>
                        </div>

                        <hr>
                        <h6>Per-member split</h6>
                        @foreach($group->members as $idx => $member)
                            <div class="row align-items-center mb-1">
                                <div class="col-md-7">
                                    <strong>{{ $member->user->name }}</strong> <small class="text-muted">({{ ucfirst($member->role) }})</small>
                                </div>
                                <div class="col-md-5 text-end">
                                    <input type="hidden" name="splits[{{ $idx }}][user_id]" value="{{ $member->user->id }}">
                                    <input type="number" step="0.01" name="splits[{{ $idx }}][amount]" class="form-control d-inline-block w-50" placeholder="0.00">
                                </div>
                            </div>
                        @endforeach
                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Add &amp; Split</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($group->sharedTransactions && $group->sharedTransactions->count())
                                @foreach($group->sharedTransactions as $tx)
                                    <tr>
                                        <td>{{ $tx->description }}</td>
                                        <td>{{ $tx->user->name ?? '—' }}</td>
                                        <td>${{ number_format($tx->amount, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($tx->transaction_date)->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No transactions yet</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Members Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-users"></i> Members ({{ $group->members->count() }})</h6>
                </div>
                <div class="list-group list-group-flush">
                    @php
                        $currentMember = $group->members->firstWhere('user_id', auth()->id());
                    @endphp
                    @foreach($group->members as $member)
                        <div class="list-group-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 font-weight-bold">{{ $member->user->name }}</h6>
                                    <small class="text-muted">{{ ucfirst($member->role) }}</small>
                                </div>
                                <div>
                                    <span class="badge bg-secondary me-2">{{ ucfirst($member->role) }}</span>
                                    @if($currentMember && $currentMember->role === 'admin' && $member->user_id !== $group->owner_id && $member->user_id !== auth()->id())
                                        <button class="btn btn-sm btn-danger remove-member-btn" 
                                                data-group-id="{{ $group->id }}" 
                                                data-member-id="{{ $member->id }}"
                                                onclick="removeMember(event)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if($currentMember && $currentMember->role === 'admin')
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Invite Member</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('groups.invite', $group) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-user-plus"></i> Send Invite
                            </button>
                        </form>
                    </div>
                </div>

                @if($group->owner_id === auth()->id())
                    <form action="{{ route('user.groups.destroy', $group) }}" method="POST" class="mt-4" onsubmit="return confirm('Delete this group? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash-alt"></i> Delete Group
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
function removeMember(event) {
    event.preventDefault();
    
    const button = event.target.closest('button');
    const groupId = button.getAttribute('data-group-id');
    const memberId = button.getAttribute('data-member-id');
    const memberName = button.closest('.list-group-item').querySelector('h6').textContent.trim();
    
    // Confirm before removing
    if (!confirm(`Are you sure you want to remove ${memberName} from this group?`)) {
        return;
    }
    
    // Show loading state
    button.disabled = true;
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
    
    // Use the web route (session-based auth)
    fetch(`/groups/${groupId}/members/${memberId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || `HTTP Error: ${response.status}`);
            }).catch(err => {
                throw new Error(`HTTP Error: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Success:', data);
        if (data.success) {
            // Show success message
            alert('Member removed successfully');
            // Remove the list item from DOM
            button.closest('.list-group-item').remove();
        } else {
            alert(data.message || 'Failed to remove member');
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Error:', error);
        
        if (error.name === 'AbortError') {
            alert('Request timeout. Please try again.');
        } else {
            alert(error.message || 'An error occurred while removing the member');
        }
        button.disabled = false;
        button.innerHTML = originalContent;
    });
}
</script>
<script>
document.getElementById('split-equally')?.addEventListener('click', function() {
    const total = parseFloat(document.getElementById('total-amount')?.value || '0');
    const inputs = document.querySelectorAll('#add-expense-form input[name$="[amount]"]');
    if (!inputs.length) return;
    const per = (total / inputs.length) || 0;
    inputs.forEach(i => i.value = per.toFixed(2));
    // mark split type as equal for server validation
    const st = document.getElementById('split-type');
    if (st) st.value = 'equal';
});
</script>
@endsection