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

    <!-- Modern Header with Gradient -->
    <div class="position-relative overflow-hidden rounded-4 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 120px;">
        <div class="position-absolute top-0 end-0 opacity-25">
            <svg width="200" height="120" viewBox="0 0 200 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="160" cy="40" r="80" fill="white"/>
            </svg>
        </div>
        <div class="position-relative p-4 text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 fw-bold">My Groups</h2>
                    <p class="mb-0 opacity-75">Manage your expense sharing groups</p>
                </div>
                <button type="button" class="btn btn-light btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="fas fa-plus-circle me-2"></i>Create Group
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($groups as $group)
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow-lg transition-all" style="border-radius: 16px; overflow: hidden;">
                    <!-- Card Header with Gradient -->
                    <div class="position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 80px;">
                        <div class="position-absolute top-0 end-0 opacity-25">
                            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="45" cy="15" r="40" fill="white"/>
                            </svg>
                        </div>
                        <div class="position-absolute bottom-0 start-0 p-3 text-white">
                            <div class="d-flex align-items-center">
                                <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                                    <i class="fas {{ $group->type === 'family' ? 'fa-users' : 'fa-user-friends' }} text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ Str::limit($group->name, 25) }}</h6>
                                    <small class="opacity-75">{{ ucfirst($group->type) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body p-4">
                        <p class="text-muted mb-3" style="min-height: 40px;">
                            {{ Str::limit($group->description, 80) ?: 'No description available' }}
                        </p>

                        <!-- Member Count with Modern Badge -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-pill px-3 py-1 me-2">
                                    <i class="fas fa-users text-primary me-1"></i>
                                    <span class="fw-medium text-primary">{{ $group->members->count() }}</span>
                                </div>
                                <small class="text-muted">members</small>
                            </div>
                        </div>

                        <!-- View Button -->
                        <a href="{{ route('user.group', $group) }}" class="btn btn-primary w-100 rounded-pill fw-medium">
                            <i class="fas fa-eye me-2"></i>View Group
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-users text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted mb-2">No Groups Yet</h5>
                        <p class="text-muted mb-4">You haven't joined any groups yet. Create your first group to start sharing expenses!</p>
                        <button type="button" class="btn btn-primary btn-lg rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                            <i class="fas fa-plus-circle me-2"></i>Create Your First Group
                        </button>
                    </div>
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
<div class="modal fade top-10" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header with Gradient -->
            <div class="modal-header position-relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 24px;">
                <div class="position-absolute top-0 end-0 opacity-25">
                    <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="60" cy="20" r="60" fill="white"/>
                    </svg>
                </div>
                <div class="position-relative text-white">
                    <h5 class="modal-title fw-bold mb-1" id="createGroupModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Create a New Group
                    </h5>
                    <p class="mb-0 opacity-75 small">Start sharing expenses with your group</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('user.groups.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="group-name" class="form-label fw-semibold text-dark">
                                Group Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-tag text-muted"></i>
                                </span>
                                <input type="text" name="name" id="group-name" class="form-control border-start-0 ps-0"
                                       style="border-radius: 0 8px 8px 0;" value="{{ old('name') }}" required
                                       placeholder="Enter group name">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="group-type" class="form-label fw-semibold text-dark">
                                Group Type <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-users text-muted"></i>
                                </span>
                                <select name="type" id="group-type" class="form-select border-start-0"
                                        style="border-radius: 0 8px 8px 0;" required>
                                    <option value="" disabled {{ old('type') ? '' : 'selected' }}>Select type</option>
                                    <option value="family" {{ old('type') === 'family' ? 'selected' : '' }}>👨‍👩‍👧‍👦 Family</option>
                                    <option value="friends" {{ old('type') === 'friends' ? 'selected' : '' }}>👥 Friends</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="group-description" class="form-label fw-semibold text-dark">Description</label>
                            <textarea name="description" id="group-description" class="form-control"
                                      rows="3" style="border-radius: 8px; resize: vertical;"
                                      placeholder="Describe your group (optional)">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="group-budget" class="form-label fw-semibold text-dark">Budget Limit (optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-dollar-sign text-muted"></i>
                                </span>
                                <input type="number" name="budget_limit" id="group-budget" class="form-control border-start-0"
                                       style="border-radius: 0 8px 8px 0;" min="0" step="0.01" value="{{ old('budget_limit') }}"
                                       placeholder="0.00">
                            </div>
                            <small class="text-muted mt-1 d-block">Leave blank if you don't want a budget cap</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="fas fa-plus-circle me-2"></i>Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.hover-shadow-lg {
    transition: all 0.3s ease;
}

.hover-shadow-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.transition-all {
    transition: all 0.3s ease;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.input-group-text {
    border-radius: 8px 0 0 8px !important;
}

.form-control, .form-select {
    border-radius: 0 8px 8px 0 !important;
}

.modal-content {
    border: none !important;
}
</style>
@endpush

@push('scripts')
@if($errors->any())
<script>
    const groupModal = new bootstrap.Modal(document.getElementById('createGroupModal'));
    groupModal.show();
</script>
@endif
@endpush
