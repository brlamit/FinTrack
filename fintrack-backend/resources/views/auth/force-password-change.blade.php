<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set Password - FinTrack</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 font-inter min-h-screen flex items-center justify-center relative overflow-x-hidden">

<!-- Background shapes -->
<div class="absolute inset-0 pointer-events-none">
    <div class="absolute -top-32 -right-20 w-80 h-80 bg-gradient-to-br from-teal-500/15 to-sky-500/15 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-32 -left-16 w-80 h-80 bg-gradient-to-br from-emerald-500/15 to-sky-500/15 rounded-full blur-3xl"></div>
</div>

<div class="relative z-10 w-full max-w-md mx-auto px-4">
    <!-- Header -->
    <header class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ url('/') }}" class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-sky-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">FT</a>
            <div>
                <h1 class="text-lg font-semibold bg-gradient-to-r from-teal-400 via-cyan-400 to-sky-400 bg-clip-text text-transparent">FinTrack</h1>
                <p class="text-xs text-gray-400">Set a new password</p>
            </div>
        </div>
        <button id="force-theme-toggle" class="px-3 py-1.5 rounded-full text-[11px] bg-gray-800/70 border border-gray-700 text-gray-200 hover:bg-gray-700 transition">Light mode</button>
    </header>

    <!-- Glass card -->
    <main class="bg-gray-800/80 backdrop-blur-md border border-gray-700 rounded-2xl shadow-lg p-6 sm:p-7">
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-1">Create a secure password</h2>
            <p class="text-xs text-gray-400">For your security, please choose a fresh password before continuing.</p>
        </div>

        <form method="POST" action="{{ route('auth.force-password-change.post') }}" class="space-y-4 text-sm">
            @csrf
            <!-- New password -->
            <div>
                <label for="password" class="block mb-1 text-xs font-medium text-gray-300">New Password</label>
                <input type="password" id="password" name="password" required autofocus placeholder="Create a strong password"
                    class="w-full rounded-xl border border-gray-700 bg-gray-900/60 px-3 py-2 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 @error('password') border-red-500 ring-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-[11px] text-gray-400">At least 8 characters with uppercase, lowercase and a number.</p>
            </div>

            <!-- Confirm password -->
            <div>
                <label for="password_confirmation" class="block mb-1 text-xs font-medium text-gray-300">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter your new password"
                    class="w-full rounded-xl border border-gray-700 bg-gray-900/60 px-3 py-2 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            </div>

            <button type="submit" class="w-full rounded-full bg-gradient-to-r from-teal-500 to-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-200">
                Set password and continue
            </button>
        </form>
    </main>

    <footer class="mt-4 text-[10px] text-gray-500 text-center">
        <span>Choose a password you don't reuse on other sites.</span>
    </footer>
</div>

<script>
(function() {
    const body = document.body;
    const storageKey = 'fintrack-force-theme';
    const toggle = document.getElementById('force-theme-toggle');

    function applyTheme(theme) {
        if(theme === 'light') {
            body.classList.remove('bg-gray-900', 'text-gray-100');
            body.classList.add('bg-gray-50', 'text-gray-900');
            toggle.textContent = 'Dark mode';
        } else {
            body.classList.remove('bg-gray-50', 'text-gray-900');
            body.classList.add('bg-gray-900', 'text-gray-100');
            toggle.textContent = 'Light mode';
        }
    }

    let current = localStorage.getItem(storageKey) || 'dark';
    applyTheme(current);

    toggle.addEventListener('click', () => {
        current = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem(storageKey, current);
        applyTheme(current);
    });
})();
</script>
</body>
</html>
