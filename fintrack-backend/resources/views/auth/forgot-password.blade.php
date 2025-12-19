<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - FinTrack</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

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

        body.theme-light input[type="email"] {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #020617;
        }

        body.theme-light input::placeholder {
            color: #94a3b8;
        }

        body.theme-light #forgot-theme-toggle {
            background-color: #e2e8f0;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        /* Alert visibility tweaks in light mode */
        body.theme-light .fintrack-alert-success {
            background-color: #ecfdf3; /* emerald-50 */
            border-color: #4ade80;     /* emerald-400 */
            color: #166534;           /* emerald-700 */
        }
    </style>
</head>
<body class="hero-bg min-h-screen flex items-center justify-center px-4 py-10 relative">

    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-[-120px] right-[-80px] w-[360px] h-[360px] bg-gradient-to-br from-teal-500/15 to-sky-500/15 blur-3xl rounded-full"></div>
        <div class="absolute bottom-[-120px] left-[-60px] w-[320px] h-[320px] bg-gradient-to-br from-emerald-500/15 to-sky-500/15 blur-3xl rounded-full"></div>
    </div>

    <div class="w-full max-w-md mx-auto relative z-10">
        <header class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-sky-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                    FT
                </a>
                <div>
                    <h1 class="text-lg font-semibold gradient-text">FinTrack</h1>
                    <p class="text-xs text-slate-400">Reset your password</p>
                </div>
            </div>

            <button id="forgot-theme-toggle" type="button" class="px-3 py-1.5 rounded-full text-[11px] bg-slate-900/70 border border-slate-700/70 text-slate-200 hover:bg-slate-800/80 transition">
                Light mode
            </button>
        </header>

        <main class="glass p-6 sm:p-7">
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-1">Reset password</h2>
                <p class="text-xs text-slate-400">Enter your email and we'll send you a 4‑digit code to verify it's really you.</p>
            </div>

            @if(session('status'))
                <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-100 fintrack-alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('auth.send-reset-link') }}" class="space-y-4 text-sm">
                @csrf

                <div>
                    <label for="email" class="block mb-1 text-xs font-medium text-slate-300">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full rounded-xl border border-slate-700/70 bg-slate-900/60 px-3 py-2 text-xs text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 @error('email') border-red-500/70 ring-red-500/60 @enderror"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1">
                    <button type="submit" class="w-full rounded-full bg-gradient-to-r from-teal-500 to-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-200">
                        Send verification code
                    </button>
                </div>
            </form>

            <div class="mt-4 text-[11px] text-slate-300 text-center">
                <a href="{{ route('auth.login') }}" class="text-sky-400 hover:text-sky-300 font-medium">Back to login</a>
            </div>
        </main>

        <footer class="mt-4 text-[10px] text-slate-500 text-center">
            <span>We only use your email to send secure reset instructions.</span>
        </footer>
    </div>

    <script>
        (function () {
            var body = document.body;
            var storageKey = 'fintrack-forgot-theme';
            var toggle = document.getElementById('forgot-theme-toggle');

            function applyTheme(theme) {
                if (theme === 'light') {
                    body.classList.add('theme-light');
                } else {
                    body.classList.remove('theme-light');
                }
            }

            var current = localStorage.getItem(storageKey) || 'dark';
            applyTheme(current);

            function updateLabel() {
                if (!toggle) return;
                toggle.textContent = current === 'light' ? 'Dark mode' : 'Light mode';
            }

            updateLabel();

            if (toggle) {
                toggle.addEventListener('click', function () {
                    current = current === 'light' ? 'dark' : 'light';
                    localStorage.setItem(storageKey, current);
                    applyTheme(current);
                    updateLabel();
                });
            }
        })();
    </script>
</body>
</html>
