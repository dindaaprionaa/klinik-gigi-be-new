@extends('layouts.auth')

@section('title', 'Daftar')

@section('heading', 'Buat akun baru')

@section('subheading')
    Registrasi cepat. Peran default pasien; pilih lain hanya jika diizinkan organisasi Anda.
@endsection

@section('content')
    <div id="auth-alert" class="mt-6 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200" role="alert"></div>

    <form id="register-form" class="mt-8 space-y-5" novalidate>
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Nama lengkap</label>
            <input type="text" id="name" name="name" autocomplete="name" required
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20" placeholder="Nama Anda">
            <p id="name-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
            <input type="email" id="email" name="email" autocomplete="email" required
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20" placeholder="nama@email.com">
            <p id="email-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <div>
            <label for="role" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Peran</label>
            <select id="role" name="role"
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20">
                <option value="pasien" selected>Pasien</option>
                <option value="dokter">Dokter</option>
                <option value="admin">Admin</option>
            </select>
            <p id="role-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Kata sandi</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20" placeholder="Minimal 8 karakter">
            <p id="password-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Konfirmasi kata sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
                class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:focus:border-emerald-400 dark:focus:ring-emerald-400/20" placeholder="Ulangi kata sandi">
            <p id="password_confirmation-error" class="mt-1.5 hidden text-xs text-red-600 dark:text-red-400"></p>
        </div>

        <button type="submit" id="submit-btn"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-900">
            <span id="submit-label">Daftar</span>
            <svg id="submit-spinner" class="hidden h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </form>
@endsection

@section('footer')
    Sudah punya akun?
    <a href="{{ route('login') }}" class="font-medium text-emerald-700 underline-offset-4 hover:text-emerald-800 hover:underline dark:text-emerald-400 dark:hover:text-emerald-300">Masuk</a>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('register-form');
    const alertEl = document.getElementById('auth-alert');
    const submitBtn = document.getElementById('submit-btn');
    const submitLabel = document.getElementById('submit-label');
    const spinner = document.getElementById('submit-spinner');

    const fields = ['name', 'email', 'role', 'password', 'password_confirmation'];

    function clearErrors() {
        alertEl.classList.add('hidden');
        alertEl.textContent = '';
        fields.forEach((name) => {
            const err = document.getElementById(name + '-error');
            const input = document.getElementById(name);
            if (err) { err.classList.add('hidden'); err.textContent = ''; }
            if (input) input.classList.remove('border-red-500', 'dark:border-red-500');
        });
    }

    function showAlert(msg) {
        alertEl.textContent = msg;
        alertEl.classList.remove('hidden');
    }

    function setLoading(loading) {
        submitBtn.disabled = loading;
        spinner.classList.toggle('hidden', !loading);
        submitLabel.textContent = loading ? 'Memproses…' : 'Daftar';
    }

    function applyFieldErrors(errors) {
        for (const key of Object.keys(errors)) {
            const errEl = document.getElementById(key + '-error');
            const input = document.getElementById(key);
            if (errEl && errors[key]) {
                const msg = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
                errEl.textContent = msg;
                errEl.classList.remove('hidden');
            }
            if (input) input.classList.add('border-red-500', 'dark:border-red-500');
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        setLoading(true);

        const roleVal = document.getElementById('role').value;
        const payload = {
            name: document.getElementById('name').value.trim(),
            email: document.getElementById('email').value.trim(),
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value,
        };
        if (roleVal && roleVal !== 'pasien') {
            payload.role = roleVal;
        }

        try {
            const res = await fetch('{{ url('/api/auth/register') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                if (data.errors) applyFieldErrors(data.errors);
                showAlert(data.message || 'Pendaftaran gagal. Periksa formulir.');
                return;
            }

            if (data.token) {
                try { localStorage.setItem('auth_token', data.token); } catch (_) {}
            }
            const role = data?.user?.role;
            if (role === 'admin') {
                window.location.href = '{{ url('/admin') }}';
            } else if (role === 'pasien') {
                window.location.href = '{{ route('pasien.profile_page') }}';
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
