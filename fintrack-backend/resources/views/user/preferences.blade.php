@extends('layouts.user')

@section('title', 'Preferences')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">User Preferences</h5>
                </div>
                <div class="card-body">
                    @if (session('preferences_updated'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('preferences_updated') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.update-preferences') }}">
                        @csrf
                        @method('PUT')

                        <!-- Notification Preferences -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Notification Preferences</h6>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_email" 
                                       name="notify_email" value="1" 
                                       {{ old('notify_email', auth()->user()->preferences['notify_email'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_email">
                                    <strong>Email Notifications</strong>
                                    <small class="d-block text-muted">Receive email notifications for important updates</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_budget" 
                                       name="notify_budget" value="1" 
                                       {{ old('notify_budget', auth()->user()->preferences['notify_budget'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_budget">
                                    <strong>Budget Alerts</strong>
                                    <small class="d-block text-muted">Get notified when you approach budget limits</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_group" 
                                       name="notify_group" value="1" 
                                       {{ old('notify_group', auth()->user()->preferences['notify_group'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_group">
                                    <strong>Group Notifications</strong>
                                    <small class="d-block text-muted">Get notified about group activity and invitations</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_weekly" 
                                       name="notify_weekly" value="1" 
                                       {{ old('notify_weekly', auth()->user()->preferences['notify_weekly'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_weekly">
                                    <strong>Weekly Summary</strong>
                                    <small class="d-block text-muted">Receive a weekly summary of your spending</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <!-- Display Preferences -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Display Preferences</h6>

                            <div class="mb-3">
                                <label for="theme" class="form-label">Theme</label>
                                <select class="form-select" id="theme" name="theme">
                                    <option value="light" {{ old('theme', auth()->user()->preferences['theme'] ?? 'light') === 'light' ? 'selected' : '' }}>
                                        Light
                                    </option>
                                    <option value="dark" {{ old('theme', auth()->user()->preferences['theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>
                                        Dark
                                    </option>
                                    <option value="auto" {{ old('theme', auth()->user()->preferences['theme'] ?? 'light') === 'auto' ? 'selected' : '' }}>
                                        Auto (System)
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD" {{ old('currency', auth()->user()->preferences['currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>
                                        USD ($)
                                    </option>
                                    <option value="EUR" {{ old('currency', auth()->user()->preferences['currency'] ?? 'USD') === 'EUR' ? 'selected' : '' }}>
                                        EUR (€)
                                    </option>
                                    <option value="GBP" {{ old('currency', auth()->user()->preferences['currency'] ?? 'USD') === 'GBP' ? 'selected' : '' }}>
                                        GBP (£)
                                    </option>
                                    <option value="INR" {{ old('currency', auth()->user()->preferences['currency'] ?? 'USD') === 'INR' ? 'selected' : '' }}>
                                        INR (₹)
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="language" class="form-label">Language</label>
                                <select class="form-select" id="language" name="language">
                                    <option value="en" {{ old('language', auth()->user()->preferences['language'] ?? 'en') === 'en' ? 'selected' : '' }}>
                                        English
                                    </option>
                                    <option value="es" {{ old('language', auth()->user()->preferences['language'] ?? 'en') === 'es' ? 'selected' : '' }}>
                                        Spanish
                                    </option>
                                    <option value="fr" {{ old('language', auth()->user()->preferences['language'] ?? 'en') === 'fr' ? 'selected' : '' }}>
                                        French
                                    </option>
                                    <option value="de" {{ old('language', auth()->user()->preferences['language'] ?? 'en') === 'de' ? 'selected' : '' }}>
                                        German
                                    </option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <!-- Privacy Preferences -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">Privacy Preferences</h6>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_profile" 
                                       name="show_profile" value="1" 
                                       {{ old('show_profile', auth()->user()->preferences['show_profile'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_profile">
                                    <strong>Public Profile</strong>
                                    <small class="d-block text-muted">Allow other users to view your profile</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="share_stats" 
                                       name="share_stats" value="1" 
                                       {{ old('share_stats', auth()->user()->preferences['share_stats'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="share_stats">
                                    <strong>Share Anonymous Stats</strong>
                                    <small class="d-block text-muted">Help improve FinTrack by sharing anonymous spending insights</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                            <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
