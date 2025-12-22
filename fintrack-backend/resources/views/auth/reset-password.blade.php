<!DOCTYPE html>
<html lang="en" class="transition-colors duration-300">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - FinTrack</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: 'class'
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; transition: background-color 0.3s, color 0.3s; }
  .glass { @apply rounded-2xl p-6 shadow-lg transition-colors duration-300; }
  .hero-bg { background: radial-gradient(circle at top left, rgba(20, 184, 166, 0.18) 0%, transparent 52%), radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.18) 0%, transparent 52%); }
  body.theme-light { --bg-dark: #f8fafc; --text-primary: #020617; --card-bg: rgba(255, 255, 255, 0.96); }
  body.theme-light .glass { @apply bg-white border border-gray-200; }
  body.dark .glass { @apply bg-[rgba(30,30,40,0.7)] border border-white/20; }
  input { @apply w-full rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 transition; }
  body.theme-light input { @apply bg-white text-gray-900 border-gray-300; }
  body.dark input { @apply bg-[rgba(30,30,40,0.7)] text-gray-100 border-white/20; }
</style>
</head>
<body class="hero-bg min-h-screen flex items-center justify-center px-4 py-10 relative">

<!-- Background shapes -->
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
        <div class="text-sm font-semibold tracking-tight bg-gradient-to-r from-emerald-400 via-cyan-300 to-sky-400 bg-clip-text text-transparent">FinTrack</div>
        <div class="text-[11px] text-slate-400">Smart money, calm mind</div>
      </div>
    </a>

    <button id="reset-theme-toggle" class="inline-flex items-center gap-1.5 rounded-full border border-slate-600/70 bg-slate-900/40 px-3 py-1.5 text-[11px] font-medium text-slate-200 shadow-sm shadow-black/30 hover:border-emerald-400/80 hover:text-emerald-300 transition-colors">
      <span class="h-4 w-4 rounded-full bg-gradient-to-br from-emerald-400 to-sky-500"></span>
      <span id="reset-theme-label">Light mode</span>
    </button>
  </div>

  <!-- Card -->
  <div class="glass rounded-2xl px-5 py-6 sm:px-7 sm:py-7 text-center">
    <div class="mb-6">
      <h1 class="text-xl font-semibold tracking-tight text-slate-50">Reset your password</h1>
      <p class="mt-1.5 text-xs text-slate-400 max-w-sm mx-auto">Choose a strong, unique password to keep your FinTrack account secure.</p>
    </div>

    <form method="POST" action="{{ route('auth.reset-password.post') }}" class="space-y-4">
      @csrf
      <div class="space-y-1.5">
        <label for="email" class="block text-xs font-medium text-slate-300">Email address</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email ?? '') }}" readonly required>
      </div>

      <div class="space-y-1.5">
        <label for="password" class="block text-xs font-medium text-slate-300">New password</label>
        <input type="password" id="password" name="password" required>
      </div>

      <div class="space-y-1.5">
        <label for="password_confirmation" class="block text-xs font-medium text-slate-300">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>
      </div>

      <button type="submit" class="w-full rounded-full bg-gradient-to-r from-emerald-400 via-emerald-500 to-sky-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg hover:brightness-105 transition">Reset password</button>
    </form>

    <p class="mt-4 text-[11px] text-slate-500">
      Remembered your password?
      <a href="{{ route('auth.login') }}" class="font-medium text-emerald-400 hover:text-emerald-300">Back to sign in</a>
    </p>
  </div>
</div>

<script>
(function() {
  const html = document.documentElement;
  const toggle = document.getElementById('reset-theme-toggle');
  const label = document.getElementById('reset-theme-label');
  const storageKey = 'fintrack-reset-form-theme';

  let theme = localStorage.getItem(storageKey) || 'light';

  function applyTheme() {
    if(theme === 'dark') {
      html.classList.add('dark');
      label.textContent = 'Light mode';
    } else {
      html.classList.remove('dark');
      label.textContent = 'Dark mode';
    }
  }

  applyTheme();

  toggle.addEventListener('click', () => {
    theme = theme === 'light' ? 'dark' : 'light';
    localStorage.setItem(storageKey, theme);
    applyTheme();
  });
})();
</script>
</body>
</html>
