@extends('layouts.user')

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
                            {{-- Avatar upload form: clicking the picture opens file picker and auto-submits --}}
                            <form id="avatar-form" action="{{ route('user.avatar.update') }}" method="POST" enctype="multipart/form-data" class="d-inline">
                                @csrf
                                <input type="file" id="avatar-input" name="avatar" accept="image/*" class="d-none">
                                <label for="avatar-input" style="cursor: pointer; display: inline-block; position: relative; width:100px; height:100px;">
                                    @php $avatarUrl = auth()->user()->avatar; @endphp
                                    @if($avatarUrl)
                                        {{-- Use the accessor URL directly (may be a full Supabase URL). Append cache-bust token. --}}
                                        <img id="profile-avatar-img" src="{{ $avatarUrl }}{{ strpos($avatarUrl, '?') === false ? '?' : '&' }}v={{ auth()->user()->updated_at?->timestamp ?? time() }}" alt="{{ auth()->user()->name }}" class="rounded-circle" width="100" height="100" style="object-fit:cover; display:block;">
                                    @else
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; margin: 0 auto;">
                                            <span style="font-size: 48px;">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div id="avatar-spinner" style="position:absolute; inset:0; display:none; align-items:center; justify-content:center;">
                                        <div class="spinner-border text-light" role="status" style="width:2rem; height:2rem;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </label>
                            </form>
                        </div>
                        <h5>{{ auth()->user()->name }}</h5>
                        <p class="text-muted">{{ auth()->user()->username }}</p>
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
                                    @php
                                        $tx = is_array($transaction) ? (object) $transaction : $transaction;
                                        $transactionDescription = $tx->description ?? '—';

                                        $transactionCategory = null;
                                        if (isset($tx->category) && is_object($tx->category)) {
                                            $transactionCategory = $tx->category->name ?? null;
                                        } elseif (!empty($tx->category_name)) {
                                            $transactionCategory = $tx->category_name;
                                        }

                                        $transactionAmount = (float) ($tx->amount ?? 0);

                                        $rawDate = $tx->date ?? ($tx->transaction_date ?? null);
                                        if ($rawDate instanceof \Carbon\CarbonInterface) {
                                            $transactionDate = $rawDate->format('M d, Y');
                                        } elseif ($rawDate) {
                                            try {
                                                $transactionDate = \Illuminate\Support\Carbon::parse($rawDate)->format('M d, Y');
                                            } catch (\Exception $e) {
                                                $transactionDate = (string) $rawDate;
                                            }
                                        } else {
                                            $transactionDate = '—';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $transactionDescription }}</td>
                                        <td>
                                            @if($transactionCategory)
                                                <span class="badge bg-secondary">{{ $transactionCategory }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">${{ number_format($transactionAmount, 2) }}</td>
                                        <td>{{ $transactionDate }}</td>
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
                            @php
                                $groupInstance = is_array($group) ? (object) $group : $group;
                                $groupName = $groupInstance->name ?? 'Unnamed Group';
                                $rawMembers = $groupInstance->members ?? [];
                                $membersCollection = $rawMembers instanceof \Illuminate\Support\Collection ? $rawMembers : collect($rawMembers);
                                $memberCount = $membersCollection->count();
                                $groupDescription = $groupInstance->description ?? null;
                                $groupId = $groupInstance->id ?? null;
                            @endphp
                            <div class="col-md-6 mb-3">
                                <div class="card border-left-primary">
                                    <div class="card-body">
                                        <h6 class="font-weight-bold text-primary">{{ $groupName }}</h6>
                                        <p class="text-sm text-muted mb-2">
                                            <i class="fas fa-users"></i> 
                                            {{ $memberCount }} members
                                        </p>
                                        @if($groupDescription)
                                            <p class="text-sm">{{ \Illuminate\Support\Str::limit($groupDescription, 50) }}</p>
                                        @endif
                                        <a href="{{ $groupId ? route('user.group', $groupId) : '#' }}" class="btn btn-sm btn-outline-primary">
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

@push('scripts')
<script>
    (function(){
        const input = document.getElementById('avatar-input');
        if (!input) return;
        input.addEventListener('change', async function(){
            if (!(input.files && input.files.length > 0)) return;

            const file = input.files[0];
            // basic client-side validation
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                alert('Please choose an image smaller than 5MB.');
                return;
            }

            const form = document.getElementById('avatar-form');
            const url = form.getAttribute('action');
            const token = document.querySelector('input[name="_token"]').value;

            const fd = new FormData();
            fd.append('_token', token);
            fd.append('avatar', file);

            // Inline preview: show the chosen image immediately in profile and navbar
            let profileImg = document.getElementById('profile-avatar-img');
            const navbarImg = document.getElementById('navbar-avatar-img');
            const previousProfileSrc = profileImg ? profileImg.src : null;
            const previousNavbarSrc = navbarImg ? navbarImg.src : null;

            // If profile image element doesn't exist (user had no avatar), create one and remove placeholder
            if (!profileImg) {
                const label = document.querySelector('label[for="avatar-input"]');
                if (label) {
                    const placeholder = label.querySelector('.rounded-circle.bg-primary');
                    const img = document.createElement('img');
                    img.id = 'profile-avatar-img';
                    img.width = 100; img.height = 100;
                    img.className = 'rounded-circle';
                    img.style.objectFit = 'cover';
                    img.style.display = 'block';
                    if (placeholder) label.replaceChild(img, placeholder); else label.insertBefore(img, label.firstChild);
                    profileImg = img;
                }
            }

            try {
                // set a local preview while upload proceeds
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (profileImg) profileImg.src = e.target.result;
                    if (navbarImg) navbarImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } catch (e) {
                // ignore preview errors
            }

            const spinner = document.getElementById('avatar-spinner');
            try {
                if (spinner) spinner.style.display = 'flex';
                const res = await fetch(url, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!res.ok) throw new Error('Upload failed with status ' + res.status);

                const json = await res.json();
                if (!json || !json.success) throw new Error('Upload failed');

                // Update profile image and navbar image
                let profileImg = document.getElementById('profile-avatar-img');
                const navbarImg = document.getElementById('navbar-avatar-img');
                const avatarUrl = json.avatar_url || json.avatar || json.avatar_url;
                if (profileImg && avatarUrl) profileImg.src = avatarUrl;
                if (navbarImg && avatarUrl) navbarImg.src = avatarUrl;

                // Optionally show a success toast
                const flash = document.createElement('div');
                flash.className = 'alert alert-success';
                flash.textContent = 'Profile picture updated';
                document.querySelector('.container').prepend(flash);
                setTimeout(() => flash.remove(), 4000);

            } catch (err) {
                console.error(err);
                alert('Failed to upload image. Try again.');
                // revert preview if possible
                try {
                    if (profileImg && typeof previousProfileSrc !== 'undefined' && previousProfileSrc) profileImg.src = previousProfileSrc;
                    if (navbarImg && typeof previousNavbarSrc !== 'undefined' && previousNavbarSrc) navbarImg.src = previousNavbarSrc;
                } catch (e) {
                    // ignore revert errors
                }
            } finally {
                if (spinner) spinner.style.display = 'none';
                // clear input so same file can be reselected later
                input.value = '';
            }
        });
    })();
</script>
@endpush
