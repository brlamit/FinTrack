@extends('layouts.admin')

@section('title', 'Edit Profile')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Profile Picture Section -->
                    <div class="text-center mb-4">
                        <h6 class="mb-3">Profile Picture</h6>
                        <form id="avatar-form" action="{{ route('admin.avatar.update') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                            @csrf
                            <input type="file" id="avatar-input" name="avatar" accept="image/*" class="d-none">
                            <div class="position-relative d-inline-block">
                                <label for="avatar-input" style="cursor: pointer;">
                                    <img id="profile-avatar-img" 
                                         src="{{ $user->avatar }}?v={{ $user->updated_at ? $user->updated_at->timestamp : time() }}" 
                                         alt="{{ $user->name ?? 'Profile' }}" 
                                         class="rounded-circle border" 
                                         width="120" height="120" 
                                         style="object-fit:cover;"
                                         crossorigin="anonymous"
                                         referrerpolicy="no-referrer"
                                         onerror="this.onerror=null; this.src='{{ asset('assets/uploads/images/default.png') }}';">
                                    <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2" style="transform: translate(25%, 25%);">
                                        <i class="fas fa-camera text-white"></i>
                                    </div>
                                </label>
                                <div id="avatar-spinner" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <p class="text-muted small mt-2">Click the image to change your profile picture</p>
                        <p class="text-muted small">Allowed formats: JPEG, PNG, JPG, GIF, WebP (Max: 5MB)</p>
                        
                        @if($user && $user->getRawOriginal('avatar'))
                            <form method="POST" action="{{ route('admin.avatar.remove') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                    <i class="fas fa-trash me-1"></i>Remove Picture
                                </button>
                            </form>
                        @endif
                    </div>

                    <hr>

                    <!-- Profile Information Form -->
                    <h6 class="mb-3">Profile Information</h6>
                    <form method="POST" action="{{ route('admin.profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>
                            @error('name') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
                            @error('email') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}" 
                                   placeholder="+1 (555) 000-0000">
                            @error('phone') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <a href="{{ route('admin.profile') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="{{ route('admin.security') }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-lock me-2"></i>Security Settings
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.preferences') }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-cog me-2"></i>Preferences
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
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
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('avatar-input');
        if (!input) return;

        input.addEventListener('change', async function() {
            if (!(input.files && input.files.length > 0)) return;
            
            const file = input.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (file.size > maxSize) {
                alert('Please choose an image smaller than 5MB.');
                input.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please choose a valid image file (JPEG, PNG, JPG, GIF, or WebP).');
                input.value = '';
                return;
            }

            const form = document.getElementById('avatar-form');
            const url = form.getAttribute('action');
            const token = document.querySelector('input[name="_token"]').value;
            const fd = new FormData();
            fd.append('_token', token);
            fd.append('avatar', file);

            const spinner = document.getElementById('avatar-spinner');
            if (spinner) spinner.style.display = 'block';

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!res.ok) {
                    const errorData = await res.json();
                    throw new Error(errorData.message || 'Upload failed');
                }

                const json = await res.json();
                const avatarUrl = json.avatar_url || json.avatar;
                const profileImg = document.getElementById('profile-avatar-img');
                
                if (profileImg && avatarUrl) {
                    profileImg.src = avatarUrl + '?v=' + Date.now();
                }

                // Update navbar avatar if exists
                const navbarAvatar = document.getElementById('navbar-avatar-img');
                if (navbarAvatar && avatarUrl) {
                    navbarAvatar.src = avatarUrl + '?v=' + Date.now();
                }

                // Show success toast
                showToast('Profile picture updated successfully!', 'success');
            } catch (e) {
                console.error(e);
                showToast('Failed to upload image. ' + e.message, 'danger');
            } finally {
                if (spinner) spinner.style.display = 'none';
                input.value = '';
            }
        });

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = 9999;
            toast.innerHTML = `
                <div class="toast show align-items-center text-white bg-${type} border-0">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
                    </div>
                </div>`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    });
</script>
@endpush
