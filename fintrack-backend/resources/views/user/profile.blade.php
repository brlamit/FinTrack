@extends('layouts.user')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Left: Profile Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0 fw-bold">Profile Information</h5>
                </div>
                <div class="card-body text-center pt-4">
                    <!-- Avatar Upload -->
                    <div class="position-relative d-inline-block mb-4">
                        <form id="avatar-form" action="{{ route('user.avatar.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            {{-- <input type="file" name="avatar" id="avatar-input" accept="image/*" class="d-none"> --}}
                            <label for="avatar-input" class="cursor-pointer">
                                <img src="{{ auth()->user()->avatar }}?v={{ auth()->user()->updated_at->timestamp }}"
                                     alt="Profile Picture"
                                     class="rounded-circle shadow-lg border border-5 border-white"
                                     width="140" height="140"
                                     style="object-fit: cover; transition: all 0.3s;"
                                     id="profile-avatar-img">
                                {{-- <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-3 shadow-lg hover-scale"
                                     style="transform: translate(20%, 20%);">
                                    <i class="fas fa-camera text-white"></i>
                                </div> --}}
                            </label>
                        </form>

                        <!-- Loading Spinner -->
                        <div id="avatar-spinner" class="position-absolute top-50 start-50 translate-middle d-none">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Uploading...</span>
                            </div>
                        </div>
                    </div>

                    <h4 class="mb-1">{{ auth()->user()->name }}</h4>
                    <p class="text-muted mb-3">@if(auth()->user()->username) {{ auth()->user()->username }} @else No username @endif</p>

                    <div class="row g-3 text-start">
                        <div class="col-12">
                            <small class="text-muted">Email</small>
                            <p class="mb-1">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Phone</small>
                            <p class="mb-1">{{ auth()->user()->phone ?? 'Not set' }}</p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Member Since</small>
                            <p class="mb-1">{{ auth()->user()->created_at->format('M d, Y') }}</p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Status</small>
                            <p class="mb-0">
                                <span class="badge bg-{{ auth()->user()->status === 'active' ? 'success' : 'warning' }} fs-6">
                                    {{ ucfirst(auth()->user()->status ?? 'active') }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-grid gap-2">
                        <a href="{{ route('user.edit') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                        <a href="{{ route('user.security') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-shield-alt me-2"></i>Security
                        </a>
                        <a href="{{ route('user.preferences') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Preferences
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Stats & Activity -->
        <div class="col-lg-8">
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-2">Total Expenses</h5>
                            <h3 class="mb-0">{{ $totalExpenses }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-2">This Month</h5>
                            <h3 class="mb-0">{{ $thisMonthExpenses }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title mb-2">Groups</h5>
                            <h3 class="mb-0">{{ $groupCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <a href="{{ route('user.transactions') }}" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <tbody>
                                @forelse($recentTransactions as $t)
                                <tr>
                                    <td class="ps-4">
                                        <div>
                                            <div class="fw-bold">{{ $t->description }}</div>
                                            <small class="text-muted">
                                                {{ optional($t->category)->name ?? 'Uncategorized' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="fw-bold text-danger">
                                            -${{ number_format($t->amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-muted pe-4" style="width: 120px;">
                                        {{ $t->transaction_date?->format('M d') ?? $t->created_at->format('M d') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fas fa-receipt fa-2x mb-3"></i><br>
                                        No transactions yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Groups -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Your Groups</h5>
                </div>
                <div class="card-body">
                    @if($userGroups->count())
                        <div class="row g-3">
                            @foreach($userGroups as $group)
                            <div class="col-md-6">
                                <div class="card h-100 border-start border-primary border-4">
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="text-primary fw-bold">{{ $group->name }}</h6>
                                        <p class="text-muted small flex-grow-1">
                                            {{ Str::limit($group->description ?? 'No description', 60) }}
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-users"></i> {{ $group->members_count ?? $group->members->count() }} members
                                            </small>
                                            <a href="{{ route('user.group', $group) }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                            <p>You haven't joined any groups yet.</p>
                            <a href="{{ route('groups.index') }}" class="btn btn-primary">Explore Groups</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: translate(20%, 20%) scale(1.2); }
    #profile-avatar-img:hover { opacity: 0.9; }
</style>
@endpush

@push('scripts')
{{-- <script>
document.getElementById('avatar-input')?.addEventListener('change', function(e) {
    const file = this.files[0];
    if (!file) return;

    if (file.size > 8 * 1024 * 1024) {
        alert('Image must be under 8MB');
        return;
    }

    const form = this.closest('form');
    const img = document.getElementById('profile-avatar-img');
    const navbarImg = document.getElementById('navbar-avatar-img');
    const spinner = document.getElementById('avatar-spinner');

    // Instant preview
    const reader = new FileReader();
    reader.onload = e => {
        img.src = e.target.result;
        if (navbarImg) navbarImg.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Show spinner
    spinner.classList.remove('d-none');

    const formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.avatar_url) {
            const url = data.avatar_url;
            img.src = url;
            if (navbarImg) navbarImg.src = url;

            // Success toast
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = 9999;
            toast.innerHTML = `
                <div class="toast show align-items-center text-white bg-success border-0">
                    <div class="d-flex">
                        <div class="toast-body">Profile picture updated!</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Upload failed. Please try again.');
    })
    .finally(() => {
        spinner.classList.add('d-none');
        this.value = '';
    });
});
</script> --}}
@endpush