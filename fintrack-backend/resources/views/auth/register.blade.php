<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - FinTrack</title>
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
                <p class="text-xs text-gray-600">Create your free account</p>
            </div>
        </div>

        <!-- Theme Toggle -->
        <button id="register-theme-toggle" class="flex items-center gap-1 px-3 py-1.5 rounded-full text-sm bg-gray-200/80 border border-gray-300 text-gray-900 hover:bg-gray-300/80 transition">
            <span id="theme-icon">🌞</span>
            <span id="theme-text">Dark</span>
        </button>
    </header>

    <!-- Register Card -->
    <main class="transition-colors duration-300 rounded-2xl p-8 shadow-lg
                 bg-white dark:bg-[rgba(30,30,40,0.7)] backdrop-blur-2xl
                 border border-white/10 dark:border-white/20">
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-1 bg-clip-text text-transparent bg-gradient-to-r from-teal-400 via-sky-400 to-emerald-400">Join FinTrack</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">Set up your space to track spending, budgets, and savings goals.</p>
        </div>
        

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-sky-500/40 bg-sky-500/10 px-3 py-2 text-xs text-sky-100 fintrack-alert-success">
                    {{ session('status') }}
                </div>
            @endif
        <form method="POST" action="{{ route('auth.register.post') }}" class="space-y-4 text-sm">
                @csrf

                <div>
                     <label for="name" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full rounded-xl border border-slate-700/70 bg-slate-900/60 px-3 py-2 text-xs text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 @error('name') border-red-500/70 ring-red-500/60 @enderror"
                        placeholder="Your full name"
                    >
                    @error('name')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                   <label for="email" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full rounded-xl border border-slate-700/70 bg-slate-900/60 px-3 py-2 text-xs text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 @error('email') border-red-500/70 ring-red-500/60 @enderror"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full rounded-xl border border-slate-700/70 bg-slate-900/60 px-3 py-2 text-xs text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 @error('password') border-red-500/70 ring-red-500/60 @enderror"
                        placeholder="Create a strong password"
                    >
                    @error('password')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                   <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-300">At least 8 characters with uppercase, lowercase, and a number.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        class="w-full rounded-xl border border-slate-700/70 bg-slate-900/60 px-3 py-2 text-xs text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                        placeholder="Re-enter your password"
                    >
                </div>

                <div class="pt-1">
                    <button type="submit" class="w-full rounded-full bg-gradient-to-r from-teal-500 to-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl hover:scale-[1.01] transition-all duration-200">
                        Create your account
                    </button>
                </div>
            </form>

        <div class="mt-5 flex items-center justify-between gap-3 text-xs text-gray-400 dark:text-gray-500">
            <span class="h-px flex-1 bg-gray-300/50 dark:bg-white/20"></span>
            <span>or</span>
            <span class="h-px flex-1 bg-gray-300/50 dark:bg-white/20"></span>
        </div>

        <div class="mt-4 text-xs text-gray-600 dark:text-gray-300 text-center">
            Already have an account?
            <a href="/login" class="text-sky-400 hover:text-sky-300 font-medium">Sign in</a>
        </div>
    </main>

    <footer class="mt-6 text-[10px] text-gray-500 text-center dark:text-gray-400">
        Tip: use a personal email you check often so you never miss budget reminders.
    </footer>
</div>

<script>
(function(){
    const html = document.documentElement; 
    const toggle = document.getElementById('register-theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const key = 'fintrack-register-form-theme';

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
</script>
</body>
</html>
