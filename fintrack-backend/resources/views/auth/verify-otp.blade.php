<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code - FinTrack</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class'
  }
</script>
</head>
<body class="relative min-h-screen bg-gray-50 transition-colors duration-300">

<div class="relative z-10 w-full max-w-md mx-auto px-4 py-10">
    <!-- Header -->
    <header class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/" class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-sky-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                FT
            </a>
            <div>
                <h1 class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-teal-400 via-sky-400 to-emerald-400">FinTrack</h1>
                <p class="text-xs text-gray-600">Verify your email</p>
            </div>
        </div>

        <!-- Theme Toggle -->
        <button id="verify-theme-toggle" class="flex items-center gap-1 px-3 py-1.5 rounded-full text-sm bg-gray-200/80 border border-gray-300 text-gray-900 hover:bg-gray-300/80 transition">
            <span id="theme-icon">🌞</span>
            <span id="theme-text">Dark</span>
        </button>
    </header>

    <!-- Verify Code Card -->
    <main class="transition-colors duration-300 rounded-2xl p-8 shadow-lg
                 bg-white dark:bg-[rgba(30,30,40,0.7)] backdrop-blur-2xl
                 border border-white/10 dark:border-white/20 text-center">
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-1 bg-clip-text text-transparent bg-gradient-to-r from-teal-400 via-sky-400 to-emerald-400">Enter Verification Code</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">We sent a 4‑digit code to <span class="font-medium text-gray-900 dark:text-gray-100">{{ $email }}</span>. Enter it below to continue.</p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-sky-500/40 bg-sky-500/10 px-3 py-2 text-xs text-sky-100 text-center fintrack-alert-success">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.otp.verify') }}" id="otp-form" class="flex flex-col items-center gap-4">
            @csrf
            <div class="flex justify-center gap-3">
                <input type="text" inputmode="numeric" maxlength="1" name="otp_1" autofocus class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)] text-center text-lg sm:text-2xl text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <input type="text" inputmode="numeric" maxlength="1" name="otp_2" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)] text-center text-lg sm:text-2xl text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <input type="text" inputmode="numeric" maxlength="1" name="otp_3" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)] text-center text-lg sm:text-2xl text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <input type="text" inputmode="numeric" maxlength="1" name="otp_4" class="otp-input w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)] text-center text-lg sm:text-2xl text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            </div>

            @error('otp')
                <p class="text-[11px] text-red-400">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-teal-500 to-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-200">
                Verify Code
            </button>
        </form>

        <form method="POST" action="{{ route('auth.otp.resend') }}" class="mt-4 text-[11px] text-gray-600 dark:text-gray-300">
            @csrf
            <button type="submit" class="text-sky-400 hover:text-sky-300 font-medium">Resend Code</button>
        </form>
    </main>

    <footer class="mt-4 text-[10px] text-gray-500 text-center dark:text-gray-400">
        Codes expire after a short time to keep your account secure.
    </footer>
</div>

<script>
(function(){
    const html = document.documentElement; 
    const toggle = document.getElementById('verify-theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const key = 'fintrack-verify-form-theme';

    let theme = localStorage.getItem(key) || 'light'; // default light

    function applyTheme() {
        if(theme === 'dark') {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        icon.textContent = theme === 'dark' ? '🌞' : '🌙';
        text.textContent = theme === 'dark' ? 'Light' : 'Dark';
    }

    applyTheme();

    toggle.addEventListener('click', () => {
        theme = theme === 'light' ? 'dark' : 'light';
        localStorage.setItem(key, theme);
        applyTheme();
    });
})();

// OTP input auto-focus
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
