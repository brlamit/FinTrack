<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FinTrack')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
        <div class="container px-4">
            <a class="navbar-brand fw-semibold" href="{{ route('user.dashboard') }}">FinTrack</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar" aria-controls="userNavbar" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="userNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.dashboard') }}">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.transactions') }}">Transactions</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.budgets') }}">Budgets</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.groups') }}">Groups</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('user.reports') }}">Reports</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown me-2">
                        {{-- Notifications dropdown --}}
                        @php
                            $__notif_unread = 0;
                            $__recent_notifs = [];
                            if (auth()->check()) {
                                $__notif_unread = \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count();
                                $__recent_notifs = \App\Models\Notification::where('user_id', auth()->id())->orderByDesc('created_at')->limit(5)->get();
                            }
                        @endphp
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem; display: {{ $__notif_unread ? 'inline-block' : 'none' }};">
                                {{ $__notif_unread }}
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow p-2" aria-labelledby="notificationsDropdown" style="min-width:320px;">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center">
                                <strong>Notifications</strong>
                                <a href="#" id="mark-all-notifs" class="small">Mark all read</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notif-list">
                                @forelse($__recent_notifs as $n)
                                    <li class="dropdown-item py-2 d-flex justify-content-between align-items-start" data-notif-id="{{ $n->id }}">
                                        <div>
                                            <div class="small fw-semibold">{{ $n->title }}</div>
                                            <div class="small text-muted">{{ Str::limit($n->message, 80) }}</div>
                                            <div class="small text-muted mt-1">{{ $n->created_at->diffForHumans() }}</div>
                                        </div>
                                        @if(!$n->is_read)
                                            <span class="badge bg-primary align-self-start ms-2">New</span>
                                        @endif
                                    </li>
                                @empty
                                    <li class="dropdown-item text-muted small">No notifications</li>
                                @endforelse
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li class="text-center"><a href="{{ route('user.notifications') ?? url('/notifications') }}" class="small">View all</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userNavbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @php $avatarUrl = auth()->user()->avatar; @endphp
                            @if($avatarUrl)
                                {{-- Append updated_at as cache-bust token to ensure newest image shows after upload --}}
                                <img id="navbar-avatar-img" src="{{ $avatarUrl }}{{ strpos($avatarUrl, '?') === false ? '?' : '&' }}v={{ auth()->user()->updated_at?->timestamp ?? time() }}" alt="{{ auth()->user()->name }}" class="rounded-circle" width="36" height="36" style="object-fit:cover;">
                            @else
                                <i class="fas fa-user-circle fa-lg"></i>
                            @endif
                            <span id="navbar-username">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userNavbarDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('user.profile') }}">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('user.preferences') }}">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('auth.logout') }}" onclick="event.preventDefault(); document.getElementById('user-logout-form').submit();">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <form id="user-logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

     <div class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-error">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        ['flash-success', 'flash-error'].forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            setTimeout(function () {
                if (window.bootstrap && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(el).close();
                } else {
                    // fallback: hide element
                    el.classList.remove('show');
                    el.style.display = 'none';
                }
            }, 7000);
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Supabase Realtime client (UMD) -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js/dist/umd/supabase.js"></script>
    <script>
        (function () {
            // Only run when a user is authenticated
            @if(auth()->check())
                const SUPABASE_URL = '{{ env('SUPABASE_URL', env('SUPABASE_PUBLIC_URL', '')) }}';
                const SUPABASE_ANON_KEY = '{{ env('SUPABASE_ANON_KEY', env('SUPABASE_KEY', '')) }}';
                const userId = '{{ auth()->id() }}';

                if (SUPABASE_URL && SUPABASE_ANON_KEY) {
                    try {
                        const supabase = supabase_js.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

                        const channel = supabase.channel(`public:notifications:user:${userId}`)
                            .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'notifications', filter: `user_id=eq.${userId}` }, payload => {
                                const row = payload.new;
                                // Prepend notification to list
                                const list = document.getElementById('notif-list');
                                if (list) {
                                    const li = document.createElement('li');
                                    li.className = 'dropdown-item py-2 d-flex justify-content-between align-items-start';
                                    li.setAttribute('data-notif-id', row.id);
                                    li.innerHTML = `
                                        <div>
                                            <div class="small fw-semibold">${escapeHtml(row.title)}</div>
                                            <div class="small text-muted">${escapeHtml(row.message?.slice(0,80) ?? '')}</div>
                                            <div class="small text-muted mt-1">just now</div>
                                        </div>
                                        <span class="badge bg-primary align-self-start ms-2">New</span>
                                    `;
                                    if (list.firstChild) list.insertBefore(li, list.firstChild);
                                    else list.appendChild(li);
                                }

                                // Update badge
                                const badge = document.getElementById('notif-badge');
                                if (badge) {
                                    let count = parseInt(badge.textContent || '0', 10) || 0;
                                    count += 1;
                                    badge.textContent = count;
                                    badge.style.display = 'inline-block';
                                }
                            })
                            .subscribe();

                        // Mark all read handler
                        document.getElementById('mark-all-notifs')?.addEventListener('click', function (ev) {
                            ev.preventDefault();
                            fetch('/api/notifications/mark-all-read', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                            }).then(r => {
                                if (r.ok) {
                                    // clear badge and mark items as read visually
                                    document.getElementById('notif-badge').style.display = 'none';
                                    document.querySelectorAll('#notif-list [data-notif-id]').forEach(el => {
                                        const badge = el.querySelector('.badge');
                                        if (badge) badge.remove();
                                    });
                                }
                            }).catch(()=>{});
                        });
                    } catch (e) {
                        console.error('Supabase realtime init failed', e);
                    }
                }

                function escapeHtml(unsafe) {
                    return String(unsafe)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }
            @endif
        })();
    </script>
    @stack('scripts')
</body>
</html>
