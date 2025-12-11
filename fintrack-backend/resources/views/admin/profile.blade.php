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
                        <div class="card-body text-center pt-4">
                    <!-- Avatar Upload -->
                    <div class="position-relative d-inline-block mb-4">
                        <form id="avatar-form" action="{{ route('user.avatar.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="avatar" id="avatar-input" accept="image/*" class="d-none">
                            <label for="avatar-input" class="cursor-pointer">
                                <img src="{{ auth()->user()->avatar }}?v={{ auth()->user()->updated_at->timestamp }}"
                                     alt="Profile Picture"
                                     class="rounded-circle shadow-lg border border-5 border-white"
                                     width="140" height="140"
                                     style="object-fit: cover; transition: all 0.3s;"
                                     id="profile-avatar-img">
                                <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-3 shadow-lg hover-scale"
                                     style="transform: translate(20%, 20%);">
                                    <i class="fas fa-camera text-white"></i>
                                </div>
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
                        <h5>{{ $user->name ?? '' }}</h5>
                        <p class="text-muted">{{ $user->username ?? '' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <p class="fw-semibold">{{ $user->email ?? '—' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Phone</label>
                        <p class="fw-semibold">{{ $user->phone ?? 'Not provided' }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Status</label>
                        <p>
                            <span class="badge bg-{{ ($user->status ?? '') === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($user->status ?? '—') }}
                            </span>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Member Since</label>
                        <p class="fw-semibold">{{ $user && $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</p>
                    </div>

                    <hr>
                    @if($user && $user->getRawOriginal('avatar'))
                        <form method="POST" action="{{ route('admin.avatar.remove') }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                <i class="fas fa-trash me-1"></i>Remove Avatar
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="fas fa-edit me-1"></i> Edit Profile
                    </a>
                    <a href="{{ route('admin.security') }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-lock me-1"></i> Security Settings
                    </a>
                    <a href="{{ route('admin.preferences') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-cog me-1"></i> Preferences
                    </a>
                </div>
            </div>
        </div>

        <!-- Admin Overview -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-shield me-2"></i>Account Overview</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Account Role</h6>
                                    <span class="badge bg-danger fs-6">{{ ucfirst($user->role ?? 'Admin') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Account Status</h6>
                                    <span class="badge bg-{{ ($user->status ?? '') === 'active' ? 'success' : 'warning' }} fs-6">
                                        {{ ucfirst($user->status ?? 'Active') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Last Password Change</h6>
                                    <p class="mb-0">{{ $user->password_changed_at ? $user->password_changed_at->format('M d, Y') : 'Never' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-muted mb-1">Last Login</h6>
                                    <p class="mb-0">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y \a\t h:i A') : '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Quick Actions</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>Edit Profile
                        </a>
                        <a href="{{ route('admin.security') }}" class="btn btn-outline-warning">
                            <i class="fas fa-lock me-1"></i>Change Password
                        </a>
                        <a href="{{ route('admin.preferences') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-1"></i>Preferences
                        </a>
                    </div>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Activity Summary</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">View your recent administrative activities and system interactions.</p>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h6 class="mb-0">Users</h6>
                                <a href="{{ route('admin.users') }}" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-exchange-alt fa-2x text-success mb-2"></i>
                                <h6 class="mb-0">Transactions</h6>
                                <a href="{{ route('admin.transactions') }}" class="stretched-link"></a>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-chart-bar fa-2x text-info mb-2"></i>
                                <h6 class="mb-0">Reports</h6>
                                <a href="{{ route('admin.reports') }}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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
</script>
@endpush
