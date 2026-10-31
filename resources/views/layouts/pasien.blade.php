<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">@yield('heading')</h1>
                @hasSection('subheading')
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">@yield('subheading')</p>
                @endif
            </div>
            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ route('pasien.profile_page') }}" class="rounded-lg border border-slate-300 px-3 py-2 dark:border-slate-700">Profil</a>
                <a href="{{ route('pasien.daftar_page') }}" class="rounded-lg border border-slate-300 px-3 py-2 dark:border-slate-700">Daftar Berobat</a>
                <a href="{{ route('pasien.riwayat_page') }}" class="rounded-lg border border-slate-300 px-3 py-2 dark:border-slate-700">Riwayat</a>
                <a href="{{ url('/') }}" class="rounded-lg border border-slate-300 px-3 py-2 dark:border-slate-700">Beranda</a>
            </nav>
        </header>
        <div id="page-alert" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm"></div>
        @yield('content')
    </div>

    <script>
        window.__apiBase = '{{ url('/api') }}';
        window.__pasienPaths = @json(config('pasien_portal.paths'));
        window.pasienApiUrl = function (key, repl = {}) {
            let p = window.__pasienPaths[key];
            if (!p) return window.__apiBase;
            Object.keys(repl).forEach(function (k) {
                p = p.split('{' + k + '}').join(String(repl[k]));
            });
            return window.__apiBase + p;
        };
        window.__authToken = localStorage.getItem('auth_token') || localStorage.getItem('token');
        window.__showAlert = function (msg, ok = false) {
            const el = document.getElementById('page-alert');
            if (!el) return;
            el.classList.remove('hidden');
            el.textContent = msg;
            el.className = 'mb-4 rounded-lg border px-4 py-3 text-sm ' + (
                ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                   : 'border-red-200 bg-red-50 text-red-800'
            );
        };
    </script>
    @stack('scripts')
</body>
</html>
