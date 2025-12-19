<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - FinTrack</title>

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

        body.theme-light input[type="text"] {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #020617;
        }

        body.theme-light input::placeholder {
            color: #94a3b8;
        }

        body.theme-light #verify-theme-toggle {
            background-color: #e2e8f0;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        /* Alert visibility tweaks in light mode */
        body.theme-light .fintrack-alert-success {
            background-color: #eff6ff; /* sky/blue tinted */
            border-color: #38bdf8;     /* sky-400 */
            color: #0369a1;           /* sky-700 */
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
                    <p class="text-xs text-slate-400">Verify your email</p>
                </div>
            </div>

            <button id="verify-theme-toggle" type="button" class="px-3 py-1.5 rounded-full text-[11px] bg-slate-900/70 border border-slate-700/70 text-slate-200 hover:bg-slate-800/80 transition">
                Light mode
            </button>
        </header>

        <main class="glass p-6 sm:p-7">
            <div class="mb-6 text-center">
                <h2 class="text-xl font-semibold mb-1">Enter verification code</h2>
                <p class="text-xs text-slate-400">We sent a 4‑digit code to <span class="font-medium text-slate-200">{{ $email }}</span>. Enter it below to continue.</p>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-sky-500/40 bg-sky-500/10 px-3 py-2 text-xs text-sky-100 text-center fintrack-alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('auth.otp.verify') }}" id="otp-form" class="text-center space-y-4">
                @csrf

                <div class="flex justify-center gap-3 mb-2">
                    <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_1" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-slate-700/70 bg-slate-900/60 text-center text-lg sm:text-2xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 @error('otp') border-red-500/70 ring-red-500/60 @enderror" autofocus>
                    <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_2" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-slate-700/70 bg-slate-900/60 text-center text-lg sm:text-2xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_3" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-slate-700/70 bg-slate-900/60 text-center text-lg sm:text-2xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_4" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-slate-700/70 bg-slate-900/60 text-center text-lg sm:text-2xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                </div>

                @error('otp')
                    <p class="text-[11px] text-red-400 text-center">{{ $message }}</p>
                @enderror

                <button type="submit" class="w-full rounded-full bg-gradient-to-r from-teal-500 to-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-200">
                    Verify code
                </button>
            </form>

            <form method="POST" action="{{ route('auth.otp.resend') }}" class="mt-4 text-center text-[11px] text-slate-300">
                @csrf
                <button type="submit" class="text-sky-400 hover:text-sky-300 font-medium">Resend code</button>
            </form>
        </main>

        <footer class="mt-4 text-[10px] text-slate-500 text-center">
            <span>Codes expire after a short time to keep your account secure.</span>
        </footer>
    </div>

    <script>
        (function () {
            var body = document.body;
            var storageKey = 'fintrack-verify-theme';
            var toggle = document.getElementById('verify-theme-toggle');

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

        const inputs = document.querySelectorAll('.otp-input');
        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                const value = input.value.replace(/\D/g, '');
                input.value = value;
                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>
