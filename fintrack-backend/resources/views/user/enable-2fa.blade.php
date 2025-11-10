@extends('layouts.user')

@section('title', 'Enable Two-Factor Authentication')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Enable Two-Factor Authentication</h5>
                </div>
                <div class="card-body">
                    <p>Two-factor authentication adds an extra layer of security to your account by requiring a second form of verification.</p>

                    <div class="alert alert-info">
                        <strong>Step 1:</strong> Scan the QR code with your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.)
                    </div>

                    <div class="text-center mb-4">
                        <p>QR Code will appear here</p>
                        <div style="width: 200px; height: 200px; background: #f0f0f0; margin: 0 auto; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center;">
                            <span class="text-muted">QR Code</span>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Step 2:</strong> Enter the 6-digit code from your authenticator app
                    </div>

                    <form method="POST" action="{{ route('user.update-password') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Verification Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="000000" maxlength="6" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Enable 2FA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
