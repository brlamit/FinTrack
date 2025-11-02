@extends('layouts.admin')

@section('title', 'Group Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>{{ $group->name }}</h3>
            </div>
            <div class="card-body">
                <p><strong>Type:</strong> {{ ucfirst($group->type) }}</p>
                <p><strong>Budget Limit:</strong> {{ $group->budget_limit ? '$' . number_format($group->budget_limit, 2) : 'N/A' }}</p>
                <p><strong>Owner:</strong> {{ $group->owner->name }}</p>
                <p><strong>Description:</strong> {{ $group->description ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5>Members</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group->members as $member)
                        <tr>
                            <td>{{ $member->user->name }}</td>
                            <td>{{ $member->user->email }}</td>
                            <td>{{ ucfirst($member->role) }}</td>
                            <td>{{ $member->joined_at->format('M d, Y') }}</td>
                            <td>
                                @if($member->user_id !== $group->owner_id)
                                    <button class="btn btn-sm btn-danger remove-member-btn" 
                                            data-group-id="{{ $group->id }}" 
                                            data-member-id="{{ $member->id }}"
                                            onclick="removeMember(event)">
                                        Remove
                                    </button>
                                @else
                                    <span class="text-muted">Owner</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Invite New Member</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('groups.invite', $group) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone (Optional)</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                    </div>
                    <button type="submit" class="btn btn-success">Send Invite</button>
                </form>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('admin.groups.index') }}" class="btn btn-secondary mt-3">Back to Groups</a>

<script>
function removeMember(event) {
    event.preventDefault();
    
    const button = event.target;
    const groupId = button.getAttribute('data-group-id');
    const memberId = button.getAttribute('data-member-id');
    const memberName = button.closest('tr').querySelector('td:first-child').textContent.trim();
    
    // Confirm before removing
    if (!confirm(`Are you sure you want to remove ${memberName} from this group?`)) {
        return;
    }
    
    // Show loading state
    button.disabled = true;
    button.textContent = 'Removing...';
    
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
            // Remove the row from table
            button.closest('tr').remove();
        } else {
            alert(data.message || 'Failed to remove member');
            button.disabled = false;
            button.textContent = 'Remove';
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
        button.textContent = 'Remove';
    });
}
</script>
@endsection