<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FinTrack')</title>

    <!-- Bootstrap 5.3 (latest stable) + Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    @stack('styles')
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
        <div class="container px-4">
            <a class="navbar-brand fw-bold" href="{{ route('user.dashboard') }}">FinTrack</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="userNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('user.dashboard') ? 'active fw-semibold' : '' }}" href="{{ route('user.dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.transactions') }}">Transactions</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.budgets') }}">Budgets</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.groups') }}">Groups</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.reports') }}">Reports</a></li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown me-3">
                        @php
                            $unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() : 0;
                            $recentNotifications = auth()->check()
                                ? \App\Models\Notification::where('user_id', auth()->id())->latest()->take(5)->get()
                                : collect();
                        @endphp

                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell fa-lg"></i>
                            @if($unreadCount > 0)
                                <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="min-width: 340px;">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                                <strong>Notifications</strong>
                                <a href="#" id="mark-all-notifs" class="small text-primary">Mark all as read</a>
                            </li>
                            <div id="notif-list" class="max-h-80 overflow-auto">
                                @forelse($recentNotifications as $n)
                                    <li class="dropdown-item py-3 border-bottom" data-notif-id="{{ $n->id }}">
                                        <div class="d-flex justify-content-between">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold small">{{ $n->title }}</div>
                                                <div class="text-muted small">{{ Str::limit($n->message, 80) }}</div>
                                                <div class="text-muted smaller mt-1">{{ $n->created_at->diffForHumans() }}</div>
                                            </div>
                                            @if(!$n->is_read)
                                                <span class="badge bg-primary ms-2">New</span>
                                            @endif
                                        </div>
                                    </li>
                                @empty
                                    <li class="dropdown-item text-center text-muted py-4">No notifications yet</li>
                                @endforelse
                            </div>
                            <li class="text-center py-2">
                                <a href="{{ route('user.notifications', [], false) ?? '#' }}" class="small">View all notifications</a>
                            </li>
                        </ul>
                    </li>

                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}?v={{ auth()->user()->updated_at?->timestamp ?? now()->timestamp }}"
                                     alt="{{ auth()->user()->name }}"
                                     class="rounded-circle"
                                     width="38" height="38" style="object-fit: cover;">
                            @else
                                <i class="fas fa-user-circle fa-2x text-secondary"></i>
                            @endif
                            <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="{{ route('user.profile') }}"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="{{ route('user.preferences') }}"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form id="user-logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-error">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- Auto-hide flash messages -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('#flash-success, #flash-error').forEach(alert => {
                setTimeout(() => {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }, 7000);
            });
        });
    </script>

    <!-- Supabase Realtime Notifications -->
    <script>
        (function () {
            @if(auth()->check())
                const SUPABASE_URL = '{{ config('services.supabase.url') }}';
                const SUPABASE_ANON_KEY = '{{ config('services.supabase.key') }}';
                const userId = '{{ auth()->id() }}';

                if (!SUPABASE_URL || !SUPABASE_ANON_KEY) return;

                const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

                supabase.channel(`notifications:user:${userId}`)
                    .on('postgres_changes', {
                        event: 'INSERT',
                        schema: 'public',
                        table: 'notifications',
                        filter: `user_id=eq.${userId}`
                    }, (payload) => {
                        const n = payload.new;

                        // Add to dropdown
                        const list = document.getElementById('notif-list');
                        const item = document.createElement('li');
                        item.className = 'dropdown-item py-3 border-bottom';
                        item.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold small">${escape(n.title)}</div>
                                    <div class="text-muted small">${escape(n.message?.substring(0,80))}</div>
                                    <div class="text-muted smaller mt-1">just now</div>
                                </div>
                                <span class="badge bg-primary ms-2">New</span>
                            </div>
                        `;
                        list.insertBefore(item, list.firstChild);

                        // Update badge
                        const badge = document.getElementById('notif-badge') || createBadge();
                        let count = (parseInt(badge.textContent) || 0) + 1;
                        badge.textContent = count;
                        badge.style.display = 'inline-block';
                    })
                    .subscribe();

                function createBadge() {
                    const bell = document.querySelector('[data-bs-toggle="dropdown"] i.fa-bell').parentElement;
                    const span = document.createElement('span');
                    span.id = 'notif-badge';
                    span.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    span.style.fontSize = '0.65rem';
                    bell.style.position = 'relative';
                    bell.appendChild(span);
                    return span;
                }

                function escape(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                // Mark all as read
                document.getElementById('mark-all-notifs')?.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await fetch('/api/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    document.getElementById('notif-badge')?.remove();
                    document.querySelectorAll('#notif-list .badge').forEach(b => b.remove());
                });
            @endif
        })();
    </script>

<!-- Tawk.to Live Chat - 100% Working -->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/691d1d94458429195a03786f/1jacrn3bg';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
})();
</script>
</body>
</html>