@extends('layouts.admin')

@section('title', 'Preferences')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Admin Preferences</h5>
                </div>
                <div class="card-body">
                    @if (session('preferences_updated'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('preferences_updated') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.update-preferences') }}">
                        @csrf
                        @method('PUT')

                        <!-- Notification Preferences -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-bell me-2"></i>Notification Preferences</h6>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_email" 
                                       name="notify_email" value="1" 
                                       {{ old('notify_email', $user->preferences['notify_email'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_email">
                                    <strong>Email Notifications</strong>
                                    <small class="d-block text-muted">Receive email notifications for important system events</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_system" 
                                       name="notify_system" value="1" 
                                       {{ old('notify_system', $user->preferences['notify_system'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_system">
                                    <strong>System Alerts</strong>
                                    <small class="d-block text-muted">Get notified about system status and errors</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_users" 
                                       name="notify_users" value="1" 
                                       {{ old('notify_users', $user->preferences['notify_users'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_users">
                                    <strong>User Activity Notifications</strong>
                                    <small class="d-block text-muted">Get notified about new user registrations and activities</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <!-- Display Preferences -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-palette me-2"></i>Display Preferences</h6>

                            <div class="mb-3">
                                <label for="theme" class="form-label">Theme</label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="light" {{ old('theme', $user->preferences['theme'] ?? 'light') === 'light' ? 'selected' : '' }}>
                                        Light
                                    </option>
                                    <option value="dark" {{ old('theme', $user->preferences['theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>
                                        Dark
                                    </option>
                                    <option value="auto" {{ old('theme', $user->preferences['theme'] ?? 'light') === 'auto' ? 'selected' : '' }}>
                                        Auto (System)
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="dashboard_layout" class="form-label">Dashboard Layout</label>
                                <select class="form-select" id="dashboard_layout" name="dashboard_layout">
                                    <option value="default" {{ old('dashboard_layout', $user->preferences['dashboard_layout'] ?? 'default') === 'default' ? 'selected' : '' }}>
                                        Default
                                    </option>
                                    <option value="compact" {{ old('dashboard_layout', $user->preferences['dashboard_layout'] ?? 'default') === 'compact' ? 'selected' : '' }}>
                                        Compact
                                    </option>
                                    <option value="detailed" {{ old('dashboard_layout', $user->preferences['dashboard_layout'] ?? 'default') === 'detailed' ? 'selected' : '' }}>
                                        Detailed
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                <select class="form-select" id="items_per_page" name="items_per_page">
                                    <option value="10" {{ old('items_per_page', $user->preferences['items_per_page'] ?? 25) == 10 ? 'selected' : '' }}>
                                        10
                                    </option>
                                    <option value="25" {{ old('items_per_page', $user->preferences['items_per_page'] ?? 25) == 25 ? 'selected' : '' }}>
                                        25
                                    </option>
                                    <option value="50" {{ old('items_per_page', $user->preferences['items_per_page'] ?? 25) == 50 ? 'selected' : '' }}>
                                        50
                                    </option>
                                    <option value="100" {{ old('items_per_page', $user->preferences['items_per_page'] ?? 25) == 100 ? 'selected' : '' }}>
                                        100
                                    </option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Preferences
                            </button>
                            <a href="{{ route('admin.profile') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Profile
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
