<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FinTrack</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0a0a0f;
            --accent-teal: #14b8a6;
            --accent-sky: #0ea5e9;
            --accent-emerald: #10b981;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --card-bg: rgba(30, 30, 40, 0.9);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .glass {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .gradient-text {
            background: linear-gradient(90deg, #14b8a6, #0ea5e9, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-bg {
            background: radial-gradient(circle at top left, rgba(20, 184, 166, 0.18) 0%, transparent 52%),
                        radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.18) 0%, transparent 52%);
        }

        .theme-light {
            --bg-dark: #f8fafc;
            --text-primary: #020617;
            --text-secondary: #475569;
            --card-bg: rgba(255, 255, 255, 0.96);
        }

        /* Light mode overrides for better contrast */
        body.theme-light .glass {
            border-color: #e2e8f0;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.10);
        }

        body.theme-light .text-slate-400,
        body.theme-light .text-slate-500 {
            color: #64748b;
        }

        body.theme-light .text-slate-300,
        body.theme-light .text-slate-200,
        body.theme-light .text-slate-100 {
            color: #0f172a;
        }

        body.theme-light input[type="email"],
        body.theme-light input[type="password"] {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #020617;
        }

        body.theme-light input::placeholder {
            color: #94a3b8;
        }

        body.theme-light #reset-theme-toggle {
            background-color: #e2e8f0;
            border-color: #cbd5e1;
            color: #0f172a;
        }
    </style>
</head>
<body class="hero-bg min-h-screen flex items-center justify-center px-4 py-10 relative">

    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-[-120px] right-[-80px] w-[360px] h-[360px] bg-gradient-to-br from-teal-500/15 to-sky-500/15 blur-3xl rounded-full"></div>
        <div class="absolute bottom-[-120px] left-[-60px] w-[320px] h-[320px] bg-gradient-to-br from-emerald-500/15 to-sky-500/15 blur-3xl rounded-full"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-4 sm:px-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <a href="/" class="flex items-center gap-2">
                <div class="h-9 w-9 rounded-2xl bg-gradient-to-br from-emerald-400 via-cyan-400 to-sky-500 flex items-center justify-center shadow-lg shadow-emerald-500/40">
                    <span class="text-xs font-black tracking-tight text-slate-950">FT</span>
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold tracking-tight bg-gradient-to-r from-emerald-400 via-cyan-300 to-sky-400 bg-clip-text text-transparent">
                        FinTrack
                    </div>
                    <div class="text-[11px] text-slate-400">Smart money, calm mind</div>
                </div>
            </a>

            <button id="reset-theme-toggle" type="button" class="inline-flex items-center gap-1.5 rounded-full border border-slate-600/70 bg-slate-900/40 px-3 py-1.5 text-[11px] font-medium text-slate-200 shadow-sm shadow-black/30 hover:border-emerald-400/80 hover:text-emerald-300 transition-colors">
                <span class="h-4 w-4 rounded-full bg-gradient-to-br from-emerald-400 to-sky-500"></span>
                <span id="reset-theme-label">Light mode</span>
            </button>
        </div>

        <!-- Card -->
        <div class="glass rounded-2xl px-5 py-6 sm:px-7 sm:py-7">
            <div class="mb-6 text-center">
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">Reset your password</h1>
                <p class="mt-1.5 text-xs text-slate-400 max-w-sm mx-auto">
                    Choose a strong, unique password to keep your FinTrack account secure.
                </p>
            </div>

            <form method="POST" action="{{ route('auth.reset-password.post') }}" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-medium text-slate-300">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email ?? '') }}"
                        readonly
                        required
                        class="w-full rounded-lg border border-slate-600/70 bg-slate-900/60 px-3 py-2.5 text-sm text-slate-100 shadow-inner shadow-black/40 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/80 @error('email') border-red-500 focus:ring-red-500/80 @enderror"
                    >
                    <p class="mt-1 text-[11px] text-slate-400">
                        We sent the verification code to this email.
                    </p>
                    @error('email')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-medium text-slate-300">New password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full rounded-lg border border-slate-600/70 bg-slate-900/60 px-3 py-2.5 text-sm text-slate-100 shadow-inner shadow-black/40 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/80 @error('password') border-red-500 focus:ring-red-500/80 @enderror"
                    >
                    @error('password')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-[11px] text-slate-400">
                        Use at least 8 characters with a mix of letters, numbers and symbols.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-xs font-medium text-slate-300">Confirm new password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        class="w-full rounded-lg border border-slate-600/70 bg-slate-900/60 px-3 py-2.5 text-sm text-slate-100 shadow-inner shadow-black/40 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/80"
                    >
                </div>

                <div class="pt-1.5">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-gradient-to-r from-emerald-400 via-emerald-500 to-sky-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-emerald-500/30 transition hover:brightness-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/80 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                    >
                        Reset password
                    </button>
                </div>
            </form>

            <p class="mt-4 text-center text-[11px] text-slate-500">
                Remembered your password?
                <a href="{{ route('auth.login') }}" class="font-medium text-emerald-400 hover:text-emerald-300">Back to sign in</a>
            </p>
        </div>
    </div>

    <script>
        (function() {
            const body = document.body;
            const storageKey = 'fintrack-reset-theme';
            const toggle = document.getElementById('reset-theme-toggle');
            const label = document.getElementById('reset-theme-label');

            function applyTheme(theme) {
                if (theme === 'light') {
                    body.classList.add('theme-light');
                    label.textContent = 'Dark mode';
                } else {
                    body.classList.remove('theme-light');
                    label.textContent = 'Light mode';
                }
            }

            const saved = localStorage.getItem(storageKey) || 'dark';
            applyTheme(saved);

            toggle.addEventListener('click', function() {
                const current = localStorage.getItem(storageKey) || 'dark';
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem(storageKey, next);
                applyTheme(next);
            });
        })();
    </script>
</body>
</html>