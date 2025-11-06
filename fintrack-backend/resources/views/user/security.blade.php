@extends('layouts.admin')

@section('title', 'Security Settings')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    @if (session('password_updated'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('password_updated') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($errors->has('password_errors'))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->get('password_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.update-password') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" name="current_password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                   id="new_password" name="new_password" required>
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Password must be at least 8 characters and contain uppercase, lowercase, and numbers.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" 
                                   id="new_password_confirmation" name="new_password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Session Management -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Active Sessions</h5>
                </div>
                <div class="card-body">
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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-active">
                                    <td>
                                        <i class="fas fa-laptop"></i> Current Session
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
                        <h6 class="mb-2"><i class="fas fa-exclamation-triangle"></i> Sign Out All Sessions</h6>
                        <p class="mb-2">This will sign you out from all devices.</p>
                        <form method="POST" action="{{ route('user.logout-all') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm" 
                                    onclick="return confirm('Are you sure? You will be signed out from all devices.')">
                                Sign Out All
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Two-Factor Authentication -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Two-Factor Authentication</h5>
                </div>
                <div class="card-body">
                    @if(auth()->user()->two_factor_enabled)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Two-Factor Authentication is <strong>ENABLED</strong>
                        </div>
                        <form method="POST" action="{{ route('user.disable-2fa') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Are you sure? This will make your account less secure.')">
                                Disable 2FA
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Two-Factor Authentication is <strong>DISABLED</strong>
                        </div>
                        <p class="text-muted mb-3">
                            Enable two-factor authentication to add an extra layer of security to your account.
                        </p>
                        <a href="{{ route('user.enable-2fa') }}" class="btn btn-success btn-sm">
                            Enable 2FA
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
