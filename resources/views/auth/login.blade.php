@extends('layouts.auth')

@section('title', 'Masuk')

@section('heading', 'Masuk ke akun')

@section('subheading')
    Gunakan email dan kata sandi terdaftar. Sesi aman mendukung aplikasi web dan token API.
@endsection

@section('content')
    <div id="auth-alert" class="mt-6 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200" role="alert"></div>

    <form id="login-form" class="mt-8 space-y-5" novalidate>
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
            <input type="email" id="email" name="email" autocomplete="email" required
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20" placeholder="nama@email.com">
            <p id="email-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <label for="password" class="text-sm font-medium text-slate-700 dark:text-slate-300">Kata sandi</label>
            </div>
            <div class="relative">
                <input type="password" id="password" name="password" autocomplete="current-password" required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-12 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20" placeholder="••••••••">
                <button type="button" id="toggle-password" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-300" aria-label="Tampilkan kata sandi">
                    <svg class="h-5 w-5" id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <svg class="hidden h-5 w-5" id="eye-closed" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.274M6.228 6.228 3 3m12.728 12.728L21 21" /></svg>
                </button>
            </div>
            <p id="password-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <button type="submit" id="submit-btn"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-900">
            <span id="submit-label">Masuk</span>
            <svg id="submit-spinner" class="hidden h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </form>
@endsection

@section('footer')
    Belum punya akun?
    <a href="{{ route('register') }}" class="font-medium text-emerald-700 underline-offset-4 hover:text-emerald-800 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300">Daftar</a>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('login-form');
    const alertEl = document.getElementById('auth-alert');
    const submitBtn = document.getElementById('submit-btn');
    const submitLabel = document.getElementById('submit-label');
    const spinner = document.getElementById('submit-spinner');

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailErr = document.getElementById('email-error');
    const passwordErr = document.getElementById('password-error');

    function clearErrors() {
        alertEl.classList.add('hidden');
        alertEl.textContent = '';
        [emailErr, passwordErr].forEach((el) => { el.classList.add('hidden'); el.textContent = ''; });
        [emailInput, passwordInput].forEach((el) => el.classList.remove('border-red-500', 'dark:border-red-500'));
    }

    function showAlert(msg) {
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
    }

    function setLoading(loading) {
        submitBtn.disabled = loading;
        spinner.classList.toggle('hidden', !loading);
        submitLabel.textContent = loading ? 'Memproses…' : 'Masuk';
    }

    document.getElementById('toggle-password').addEventListener('click', function () {
        const isPwd = passwordInput.type === 'password';
        passwordInput.type = isPwd ? 'text' : 'password';
        document.getElementById('eye-open').classList.toggle('hidden', !isPwd);
        document.getElementById('eye-closed').classList.toggle('hidden', isPwd);
        this.setAttribute('aria-label', isPwd ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        setLoading(true);

        try {
            const res = await fetch('{{ url('/api/auth/login') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    email: emailInput.value.trim(),
                    password: passwordInput.value,
                }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                if (data.errors) {
                    if (data.errors.email) {
                        emailErr.textContent = Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email;
                        emailErr.classList.remove('hidden');
                        emailInput.classList.add('border-red-500', 'dark:border-red-500');
                    }
                    if (data.errors.password) {
                        passwordErr.textContent = Array.isArray(data.errors.password) ? data.errors.password[0] : data.errors.password;
                        passwordErr.classList.remove('hidden');
                        passwordInput.classList.add('border-red-500', 'dark:border-red-500');
                    }
                }
                showAlert(data.message || 'Gagal masuk. Periksa email dan kata sandi.');
                return;
            }

            if (data.token) {
                try { localStorage.setItem('auth_token', data.token); } catch (_) {}
            }
            const role = data?.user?.role;
            if (role === 'admin') {
                window.location.href = '{{ url('/admin') }}';
            } else if (role === 'pasien') {
                window.location.href = '{{ route('pasien.daftar_page') }}';
            } else {
                window.location.href = '{{ url('/') }}';
            }
        } catch (err) {
            showAlert('Tidak dapat menghubungi server. Coba lagi.');
        } finally {
            setLoading(false);
        }
    });
})();
</script>
@endpush
