<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - FinTrack</title>
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
            <a href="/" class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-sky-600 flex items-center justify-center text-white font-bold text-xl shadow-lg animate-bounce">
                FT
            </a>
            <div>
                <h1 class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-teal-400 via-sky-400 to-emerald-400">FinTrack</h1>
                <p class="text-xs text-gray-600">Sign in to continue</p>
            </div>
        </div>

        <!-- Theme Toggle -->
        <button id="theme-toggle" class="flex items-center gap-1 px-3 py-1.5 rounded-full text-sm bg-gray-200/80 border border-gray-300 text-gray-900 hover:bg-gray-300/80 transition">
            <span id="theme-icon">🌞</span>
            <span id="theme-text">Dark</span>
        </button>
    </header>

    <!-- Login Card -->
    <main class="transition-colors duration-300 rounded-2xl p-8 shadow-lg
                 bg-white dark:bg-[rgba(30,30,40,0.7)] backdrop-blur-2xl
                 border border-white/10 dark:border-white/20">
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-1 bg-clip-text text-transparent bg-gradient-to-r from-teal-400 via-sky-400 to-emerald-400">Welcome back</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">Log in to see your latest spends, budgets and insights.</p>
        </div>

        <form method="POST" action="{{ route('auth.login.post') }}" class="space-y-4 text-sm">
                @csrf
            <div>
                <label for="login" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Email or Username</label>
                <input type="text" id="login" name="login" required placeholder="you@example.com or username"
                    class="w-full rounded-xl border border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)]
                           px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="text-xs font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <a href="/forgot-password" class="text-xs text-sky-400 hover:text-sky-300">Forgot?</a>
                </div>
                <input type="password" id="password" name="password" required placeholder="Enter your password"
                    class="w-full rounded-xl border border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)]
                           px-3 py-2 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-400
                           focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 dark:border-white/20 bg-white dark:bg-[rgba(30,30,40,0.7)] text-teal-500 focus:ring-teal-500">
                    Remember me
                </label>
            </div>

            <div>
                <button type="submit" class="w-full rounded-full bg-gradient-to-r from-teal-500 to-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-200">Sign in</button>
            </div>
        </form>

        <div class="mt-5 flex items-center justify-between gap-3 text-xs text-gray-400 dark:text-gray-500">
            <span class="h-px flex-1 bg-gray-300/50 dark:bg-white/20"></span>
            <span>or</span>
            <span class="h-px flex-1 bg-gray-300/50 dark:bg-white/20"></span>
        </div>

        <div class="mt-4 text-xs text-gray-600 dark:text-gray-300 text-center">
            Don't have an account?
            <a href="/register" class="text-sky-400 hover:text-sky-300 font-medium">Sign up</a>
        </div>
    </main>

    <footer class="mt-6 text-[10px] text-gray-500 text-center dark:text-gray-400">
        Tip: use the same email you signed up with to sync your budgets.
    </footer>
</div>

<script>
(function(){
    const html = document.documentElement; 
    const toggle = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const key = 'fintrack-login-form-theme';

    let theme = localStorage.getItem(key) || 'light'; // default light (page remains light)

    function applyTheme() {
        if(theme === 'dark') {
            html.classList.add('dark'); // applies Tailwind dark only to form
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
</script>
</body>
</html>
