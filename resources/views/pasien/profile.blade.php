@extends('layouts.pasien')

@section('title', 'Profil')
@section('heading', 'Profil Pasien')
@section('subheading', 'Lengkapi data profil dan upload foto (opsional).')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-col items-center text-center">
                <img id="photo-preview" src="" alt="Foto profil" class="hidden h-32 w-32 rounded-full object-cover ring-4 ring-emerald-100 dark:ring-emerald-900/40" />
                <div id="photo-placeholder" class="flex h-32 w-32 items-center justify-center rounded-full bg-emerald-100 text-4xl font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">P</div>
                <label class="mt-4 inline-flex cursor-pointer rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Upload foto
                    <input id="photo-input" type="file" accept="image/png,image/jpeg,image/webp" class="hidden">
                </label>
                <p class="mt-2 text-xs text-slate-500">Opsional. File disimpan di `public/assets/profile`.</p>
            </div>
        </aside>

        <form id="profile-form" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Nama</label><input name="nama" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
                <div><label class="mb-1 block text-sm font-medium">No Identitas</label><input name="no_identitas" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
                <div><label class="mb-1 block text-sm font-medium">Jenis Kelamin</label><select name="jenis_kelamin" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option></select></div>
                <div><label class="mb-1 block text-sm font-medium">Tanggal Lahir</label><input type="date" name="tanggal_lahir" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
                <div><label class="mb-1 block text-sm font-medium">No Telepon</label><input name="no_telepon" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Email</label><input name="email" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
                <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium">Alamat</label><textarea name="alamat" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea></div>
            </div>
            <div class="mt-6 flex justify-end"><button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Simpan profil</button></div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const token = window.__authToken;
        if (!token) return window.__showAlert('Token login tidak ditemukan. Silakan login ulang.');
        const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` };

        const form = document.getElementById('profile-form');
        const preview = document.getElementById('photo-preview');
        const placeholder = document.getElementById('photo-placeholder');
        const photoInput = document.getElementById('photo-input');

        function setPhoto(url, name) {
            if (url) {
                preview.src = url;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            } else {
                preview.classList.add('hidden');
                placeholder.textContent = (name || 'P').slice(0, 1).toUpperCase();
                placeholder.classList.remove('hidden');
            }
        }

        async function loadProfile() {
            const res = await fetch(window.pasienApiUrl('profile'), { headers });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal memuat profil');
            const p = data.data || {};
            form.nama.value = p.nama || '';
            form.no_identitas.value = p.no_identitas || '';
            form.jenis_kelamin.value = p.jenis_kelamin || 'Laki-laki';
            form.tanggal_lahir.value = p.tanggal_lahir ? String(p.tanggal_lahir).slice(0, 10) : '';
            form.no_telepon.value = p.no_telepon || '';
            form.email.value = p.email || '';
            form.alamat.value = p.alamat || '';
            setPhoto(p.photo_url, p.nama);
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const payload = {
                nama: form.nama.value,
                no_identitas: form.no_identitas.value,
                jenis_kelamin: form.jenis_kelamin.value,
                tanggal_lahir: form.tanggal_lahir.value,
                no_telepon: form.no_telepon.value,
                email: form.email.value,
                alamat: form.alamat.value
            };
            const res = await fetch(window.pasienApiUrl('profile'), {
                method: 'PUT',
                headers: { ...headers, 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) return window.__showAlert(data.message || 'Gagal menyimpan profil');
            window.__showAlert('Profil berhasil diperbarui.', true);
            setPhoto(data.data?.photo_url, data.data?.nama);
        });

        photoInput.addEventListener('change', async function () {
            const file = photoInput.files?.[0];
            if (!file) return;
            const body = new FormData();
            body.append('photo', file);
            const res = await fetch(window.pasienApiUrl('profile_photo'), {
                method: 'POST',
                headers,
                body,
            });
            const data = await res.json();
            if (!res.ok) return window.__showAlert(data.message || 'Gagal upload foto');
            window.__showAlert('Foto profil berhasil diunggah.', true);
            setPhoto(data.photo_url, form.nama.value);
        });

        loadProfile().catch((e) => window.__showAlert(e.message));
    })();
</script>
@endpush
