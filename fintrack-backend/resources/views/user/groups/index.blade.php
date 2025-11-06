@extends('layouts.admin')

@section('title', 'My Groups')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Groups</h2>
        <a href="#" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Create Group
        </a>
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
                    <p>You haven't joined any groups yet. <a href="#">Create a group</a></p>
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
@endsection
