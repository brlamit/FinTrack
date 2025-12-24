<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ config('app.name', 'FinTrack') }} – Your Money Command Center</title>

<!-- SEO -->
<meta name="description" content="FinTrack helps you track expenses, manage budgets, and gain real-time insights into your personal finances.">
<meta name="theme-color" content="#0a0a0f">
<link rel="icon" href="/favicon.ico">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<style>
:root {
    --glass-bg: rgba(30,30,40,0.7);
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
}
.theme-light {
    --glass-bg: rgba(255,255,255,0.9);
    --text-primary: #111827; /* slightly dark text for light mode */
    --text-secondary: #475569;
}
.glass {
    background-color: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: 1.5rem;
    border: 1px solid rgba(255,255,255,0.08);
}
.gradient-text {
    background: linear-gradient(90deg,#14b8a6,#0ea5e9,#10b981);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.hero-bg {
    background: radial-gradient(circle at top left, rgba(20,184,166,.15), transparent 50%),
                radial-gradient(circle at bottom right, rgba(14,165,233,.15), transparent 50%);
}
@keyframes float {
    0%,100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}
.animate-float { animation: float 6s ease-in-out infinite; }
.section-animate { opacity: 0; transform: translateY(16px); animation: fadeUp .8s ease forwards; }
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body class="hero-bg relative min-h-screen px-4 py-12 transition-colors duration-500 text-[var(--text-primary)]">

<!-- Background glow -->
<div class="absolute inset-0 pointer-events-none">
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[600px] bg-gradient-to-br from-teal-500/10 to-sky-500/10 blur-3xl rounded-full"></div>
</div>

<div class="relative z-10 max-w-6xl mx-auto">

<!-- HEADER -->
<header class="flex items-center justify-between mb-14">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-sky-600 flex items-center justify-center text-white font-bold text-2xl animate-float">
            FT
        </div>
        <div>
            <h2 class="text-xl font-bold gradient-text">FinTrack</h2>
            <p class="text-sm text-[var(--text-secondary)]">Money made simple</p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <!-- Theme toggle with icon -->
        <button id="theme-toggle" class="px-3 py-2 rounded-full text-xs bg-slate-900/70 border border-slate-700/70 text-white flex items-center gap-1">
            <span id="theme-icon">🌞</span>
            <span id="theme-text">Light</span>
        </button>

        @auth
        <a href="/dashboard" class="px-6 py-3 rounded-full bg-gradient-to-r from-teal-500 to-sky-600 text-white font-medium hover:scale-105 transition">
            Dashboard
        </a>
        @else
        <a href="{{ route('auth.login') }}" class="px-6 py-3 rounded-full bg-teal-500 text-white font-semibold shadow hover:bg-teal-600 transition">
            Login
        </a>
        <a href="{{ route('auth.register') }}" class="px-6 py-3 rounded-full border border-teal-500 text-teal-500 font-semibold shadow hover:bg-teal-500 hover:text-white transition">
            Get Started
        </a>
        @endauth
    </div>
</header>

<!-- HERO -->
<section class="glass p-8 lg:p-12 mb-10 section-animate">
    <div class="relative mb-6">
        <div class="absolute -inset-3 bg-gradient-to-r from-teal-500/20 to-sky-500/20 blur-2xl rounded-full"></div>
        <h1 class="relative text-4xl lg:text-5xl font-bold">
            Take control of your<br>
            <span class="gradient-text">money story</span>
        </h1>
    </div>

    <p class="text-lg text-[var(--text-secondary)] max-w-xl mb-8">
        Track expenses, manage budgets, and understand your finances with clarity.
    </p>

    <div class="flex flex-wrap gap-4">
        <a href="{{ route('auth.login') }}" class="px-6 py-3 rounded-full bg-teal-500 text-white font-semibold shadow hover:bg-teal-600 transition">
            Login
        </a>
        <a href="{{ route('auth.register') }}" class="px-6 py-3 rounded-full border border-teal-500 text-teal-500 font-semibold shadow hover:bg-teal-500 hover:text-white transition">
            Get Started
        </a>
    </div>

    <div class="mt-4 text-xs text-[var(--text-secondary)] flex gap-3">
        <span>🔒 No ads</span>
        <span>•</span>
        <span>No credit card required</span>
    </div>
</section>

<!-- HIGHLIGHTS -->
<section class="grid md:grid-cols-3 gap-6 mb-12 section-animate">
    <div class="glass p-6 hover:-translate-y-1 transition">
        <h3 class="font-semibold mb-2">Automatic Categorization</h3>
        <p class="text-sm text-[var(--text-secondary)]">Expenses grouped smartly with zero effort.</p>
    </div>
    <div class="glass p-6 hover:-translate-y-1 transition">
        <h3 class="font-semibold mb-2">Real-time Insights</h3>
        <p class="text-sm text-[var(--text-secondary)]">Know where your money goes instantly.</p>
    </div>
    <div class="glass p-6 hover:-translate-y-1 transition">
        <h3 class="font-semibold mb-2">Safe & Private</h3>
        <p class="text-sm text-[var(--text-secondary)]">Your data stays encrypted and yours.</p>
    </div>
</section>

<!-- FOOTER -->
<footer class="text-xs flex justify-between text-[var(--text-secondary)]">
    <span>© {{ date('Y') }} FinTrack</span>
    <span>Secure • Private • Simple</span>
</footer>

<!-- THEME SCRIPT -->
<script>
(function(){
    const body = document.body;
    const toggle = document.getElementById('theme-toggle');
    const icon = document.getElementById('theme-icon');
    const text = document.getElementById('theme-text');
    const key = 'fintrack-theme';

    let current = localStorage.getItem(key) || 'dark';

    function apply() {
        const isLight = current === 'light';
        body.classList.toggle('theme-light', isLight);
        icon.textContent = isLight ? '🌙' : '🌞';
        text.textContent = isLight ? 'Dark' : 'Light';
    }

    apply();

    toggle.onclick = () => {
        current = current === 'light' ? 'dark' : 'light';
        localStorage.setItem(key, current);
        apply();
    };
})();
</script>
</body>
</html>
