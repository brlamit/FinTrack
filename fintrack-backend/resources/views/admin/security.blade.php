@extends('layouts.admin')

@section('title', 'Security Settings')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                </div>
                <div class="card-body">
                    @if (session('password_updated'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('password_updated') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.update-password') }}" id="password-update-form">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                       id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                       id="new_password" name="new_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Password must be at least 8 characters and contain uppercase, lowercase, and numbers.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" 
                                       id="new_password_confirmation" name="new_password_confirmation" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password_confirmation">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock me-2"></i>Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Session Management -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-desktop me-2"></i>Active Sessions</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <p class="text-muted mb-3">
                        Manage your active sessions and sign out from other devices.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Last Active</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-active">
                                    <td>
                                        <i class="fas fa-laptop me-2"></i> Current Session
                                    </td>
                                    <td>Now</td>
                                    <td>{{ request()->ip() }}</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <div class="alert alert-warning">
                        <h6 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Sign Out All Sessions</h6>
                        <p class="mb-2">This will sign you out from all devices except the current one.</p>
                        <form method="POST" action="{{ route('admin.logout-all') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm" 
                                    onclick="return confirm('Are you sure? You will be signed out from all other devices.')">
                                <i class="fas fa-sign-out-alt me-2"></i>Sign Out All
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Account Created:</strong></p>
                            <p class="text-muted">{{ $user->created_at ? $user->created_at->format('M d, Y \a\t h:i A') : '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Last Password Change:</strong></p>
                            <p class="text-muted">{{ $user->password_changed_at ? $user->password_changed_at->format('M d, Y \a\t h:i A') : 'Never' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Last Login:</strong></p>
                            <p class="text-muted">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y \a\t h:i A') : '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Account Role:</strong></p>
                            <p><span class="badge bg-danger">{{ ucfirst($user->role ?? 'Admin') }}</span></p>
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
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>
@endpush
