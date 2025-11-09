@extends('layouts.user')

@section('title', 'My Groups')

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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Groups</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
            <i class="fas fa-plus-circle"></i> Create Group
        </button>
    </div>

    <div class="row">
        @forelse($groups as $group)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">{{ $group->name }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-sm">
                            {{ Str::limit($group->description, 60) ?: 'No description' }}
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-info">
                                <i class="fas fa-users"></i> {{ $group->members->count() }} members
                            </span>
                            <span class="badge bg-secondary">
                                {{ ucfirst($group->type) }}
                            </span>
                        </div>
                        <a href="{{ route('user.group', $group) }}" class="btn btn-sm btn-primary w-100">
                            View Group
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <p>You haven't joined any groups yet. <a href="#" data-bs-toggle="modal" data-bs-target="#createGroupModal">Create a group</a></p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($groups->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $groups->links() }}
        </div>
    @endif
</div>

<!-- Create Group Modal -->
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createGroupModalLabel">Create a New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('user.groups.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="group-name" class="form-label">Group Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="group-name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="group-type" class="form-label">Group Type <span class="text-danger">*</span></label>
                            <select name="type" id="group-type" class="form-select" required>
                                <option value="" disabled {{ old('type') ? '' : 'selected' }}>Select type</option>
                                <option value="family" {{ old('type') === 'family' ? 'selected' : '' }}>Family</option>
                                <option value="friends" {{ old('type') === 'friends' ? 'selected' : '' }}>Friends</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="group-description" class="form-label">Description</label>
                            <textarea name="description" id="group-description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="group-budget" class="form-label">Budget Limit (optional)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="budget_limit" id="group-budget" class="form-control" min="0" step="0.01" value="{{ old('budget_limit') }}">
                            </div>
                            <small class="text-muted">Leave blank if you don't want a budget cap.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($errors->any())
<script>
    const groupModal = new bootstrap.Modal(document.getElementById('createGroupModal'));
    groupModal.show();
</script>
@endif
@endpush
