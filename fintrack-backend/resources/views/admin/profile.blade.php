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
                            {{-- Avatar upload (action only used if admin route exists) --}}
                            <form id="avatar-form" action="{{ Route::has('admin.avatar.update') ? route('admin.avatar.update') : '#' }}" method="POST" enctype="multipart/form-data" class="d-inline">
                                @csrf
                                <input type="file" id="avatar-input" name="avatar" accept="image/*" class="d-none">
                                <label for="avatar-input" style="cursor: pointer; display: inline-block; position: relative; width:100px; height:100px;">
                                    @php
                                        $user = auth()->user();
                                        $avatarUrl = $user && ($user->profile_picture ?? $user->avatar)
                                            ? (filter_var($user->profile_picture ?? $user->avatar, FILTER_VALIDATE_URL)
                                                ? ($user->profile_picture ?? $user->avatar)
                                                : Storage::url($user->profile_picture ?? $user->avatar))
                                            : asset('assets/uploads/images/default.png');
                                    @endphp
                                    <img id="profile-avatar-img" src="{{ $avatarUrl }}{{ strpos($avatarUrl, '?') === false ? '?' : '&' }}v={{ $user && $user->updated_at ? $user->updated_at->timestamp : time() }}" alt="{{ $user->name ?? 'Profile' }}" class="rounded-circle" width="100" height="100" style="object-fit:cover; display:block; margin:0 auto;">
                                    <div id="avatar-spinner" style="position:absolute; inset:0; display:none; align-items:center; justify-content:center;">
                                        <div class="spinner-border text-light" role="status" style="width:2rem; height:2rem;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </label>
                            </form>
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
                            <button type="submit" class="btn btn-danger btn-sm w-100">Remove Avatar</button>
                        </form>
                    @endif
                    <a href="{{ Route::has('admin.profile.edit') ? route('admin.profile.edit') : '#' }}" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="{{ Route::has('admin.security') ? route('admin.security') : '#' }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-lock"></i> Security Settings
                    </a>
                    <a href="{{ Route::has('admin.preferences') ? route('admin.preferences') : '#' }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-cog"></i> Preferences
                    </a>
                </div>
            </div>
        </div>

        <!-- Admin Overview -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Account Overview</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Admin profile page. Customize content here as needed.</p>

                    <p class="text-muted">Manage your account details and avatar from the edit page.</p>
                    <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Reuse the same avatar upload script if desired. Keep minimal here.
    (function(){
        const input = document.getElementById('avatar-input');
        if (!input) return;
        input.addEventListener('change', async function(){
            if (!(input.files && input.files.length > 0)) return;
            const file = input.files[0];
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) { alert('Please choose an image smaller than 5MB.'); return; }
            const form = document.getElementById('avatar-form');
            const url = form.getAttribute('action');
            if (!url || url === '#') { alert('Avatar upload not configured for admin.'); return; }
            const token = document.querySelector('input[name="_token"]').value;
            const fd = new FormData(); fd.append('_token', token); fd.append('avatar', file);
            const spinner = document.getElementById('avatar-spinner'); if (spinner) spinner.style.display = 'flex';
            try {
                const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Upload failed');
                const json = await res.json(); const avatarUrl = json.avatar_url || json.avatar;
                const profileImg = document.getElementById('profile-avatar-img'); if (profileImg && avatarUrl) profileImg.src = avatarUrl;
                alert('Profile picture updated');
            } catch (e) { console.error(e); alert('Failed to upload image.'); }
            finally { if (spinner) spinner.style.display = 'none'; input.value = ''; }
        });
    })();
</script>
@endpush
