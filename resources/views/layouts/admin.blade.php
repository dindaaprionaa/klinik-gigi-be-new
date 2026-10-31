<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto flex min-h-screen max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <aside class="w-64 shrink-0">
            <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Admin Panel</p>
                        <p id="admin-user" class="text-xs text-slate-500 dark:text-slate-400">Memuat...</p>
                    </div>
                </div>

                <nav class="mt-4 space-y-1 text-sm">
                    @php
                        $nav = [
                            ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
                            ['label' => 'Dokter', 'route' => 'admin.dokter'],
                            ['label' => 'Pasien', 'route' => 'admin.pasien'],
                            ['label' => 'Jadwal', 'route' => 'admin.jadwal'],
                            ['label' => 'Pendaftaran', 'route' => 'admin.pendaftaran'],
                            ['label' => 'Rekam Medis', 'route' => 'admin.rekam_medis'],
                            ['label' => 'Laporan', 'route' => 'admin.laporan'],
                        ];
                    @endphp
                    @foreach ($nav as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-lg px-3 py-2 font-medium transition
                           {{ $active ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <button id="logout-btn" class="mt-4 w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Logout
                </button>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="mb-4">
                <h1 class="text-2xl font-semibold tracking-tight">@yield('heading')</h1>
                @hasSection('subheading')
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">@yield('subheading')</p>
                @endif
            </header>

            <div id="global-alert" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm"></div>
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
            if (!token) {
                window.location.replace('{{ route('login') }}');
                return;
            }

            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
            };

            const alertBox = document.getElementById('global-alert');
            function showAlert(msg, type = 'error') {
                if (!alertBox) return;
                alertBox.textContent = msg;
                alertBox.classList.remove('hidden');
                alertBox.className = 'mb-4 rounded-lg border px-4 py-3 text-sm ' + (
                    type === 'error'
                        ? 'border-red-200 bg-red-50 text-red-800'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-800'
                );
            }

            async function api(path, opts = {}) {
                const res = await fetch(`{{ url('/api') }}${path}`, {
                    ...opts,
                    headers: { ...headers, ...(opts.headers || {}) },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const err = new Error(data.message || `Request gagal (${res.status})`);
                    err.status = res.status;
                    err.data = data;
                    throw err;
                }
                return data;
            }

            window.__adminApi = api;
            window.__adminAlert = showAlert;

            (async () => {
                try {
                    const me = await api('/auth/me');
                    if (!me.user || me.user.role !== 'admin') {
                        localStorage.removeItem('auth_token');
                        localStorage.removeItem('token');
                        window.location.replace('{{ route('login') }}');
                        return;
                    }
                    const userEl = document.getElementById('admin-user');
                    if (userEl) userEl.textContent = `${me.user.name} (${me.user.role})`;
                } catch (e) {
                    showAlert(e.message || 'Gagal memuat user');
                }
            })();

            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', async () => {
                    try { await api('/auth/logout', { method: 'POST' }); } catch (_) {}
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('token');
                    window.location.replace('{{ route('login') }}');
                });
            }
        })();
    </script>

    @stack('scripts')
</body>
</html>
