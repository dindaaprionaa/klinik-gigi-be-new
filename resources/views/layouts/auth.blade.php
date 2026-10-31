<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') — {{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(16,185,129,0.18),transparent)] dark:bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(16,185,129,0.12),transparent)]"></div>
        <div class="pointer-events-none absolute -right-24 top-1/4 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl dark:bg-emerald-500/10"></div>
        <div class="pointer-events-none absolute -left-24 bottom-1/4 h-72 w-72 rounded-full bg-teal-400/10 blur-3xl dark:bg-teal-500/10"></div>

        <div class="relative mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-12 sm:px-6">
            <header class="mb-8 text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-medium text-emerald-700 transition hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm shadow-emerald-600/25">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>
            </header>

            <main class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-black/20 sm:p-10">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    @yield('heading')
                </h1>
                @hasSection('subheading')
                    <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        @yield('subheading')
                    </p>
                @endif

                @yield('content')
            </main>

            <footer class="mt-8 text-center text-sm text-slate-500 dark:text-slate-500">
                @yield('footer')
            </footer>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
