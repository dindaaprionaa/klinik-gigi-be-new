@extends('layouts.pasien')

@section('title', 'Daftar Berobat')
@section('heading', 'Daftar Berobat')
@section('subheading', 'Pilih dokter dan jadwal kunjungan.')

@section('content')
    <form id="daftar-form" class="mx-auto max-w-xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div>
            <label class="mb-1 block text-sm font-medium">Dokter</label>
            <select id="dokter" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">Memuat daftar dokter...</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Jadwal Praktik</label>
            <select id="jadwal" disabled class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">Pilih dokter dulu</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Tanggal Kunjungan</label>
            <input id="tanggal" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Jam Kunjungan</label>
            <input id="jam" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Keluhan</label>
            <textarea id="keluhan" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" placeholder="Contoh: Demam, batuk, sakit kepala"></textarea>
        </div>

        <button type="submit" id="submit-btn" class="w-full rounded-lg bg-emerald-600 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
            Kirim Pendaftaran
        </button>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        const token = window.__authToken;
        if (!token) return window.__showAlert('Token login tidak ditemukan. Silakan login ulang.');
        const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` };

        const dokterSelect = document.getElementById('dokter');
        const jadwalSelect = document.getElementById('jadwal');
        const tanggalInput = document.getElementById('tanggal');
        const jamInput = document.getElementById('jam');
        const keluhanInput = document.getElementById('keluhan');
        const form = document.getElementById('daftar-form');

        let pasienId = null;
        let jadwalRows = [];

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function loadInitial() {
            const [dashRes, dokterRes] = await Promise.all([
                fetch(window.pasienApiUrl('dashboard'), { headers }),
                fetch(window.pasienApiUrl('dokters'), { headers }),
            ]);
            const dash = await dashRes.json();
            const dok = await dokterRes.json();

            if (!dashRes.ok) throw new Error(dash.message || 'Gagal memuat data pasien');
            if (!dokResOk(dokterRes, dok)) throw new Error(dok.message || 'Gagal memuat dokter');

            pasienId = dash.pasien?.id || null;
            const dokters = dok.data || [];
            if (!dokters.length) {
                dokterSelect.innerHTML = `<option value="">Tidak ada dokter tersedia</option>`;
                return;
            }
            dokterSelect.innerHTML = `<option value="">Pilih dokter</option>` + dokters.map(d =>
                `<option value="${d.id}">${esc(d.nama)} — ${esc(d.spesialisasi)}</option>`
            ).join('');
        }

        function dokResOk(resp, payload) {
            return resp.ok && payload && (Array.isArray(payload.data) || payload.data === undefined);
        }

        dokterSelect.addEventListener('change', async function () {
            const dokterId = dokterSelect.value;
            jadwalRows = [];
            jadwalSelect.innerHTML = `<option value="">${dokterId ? 'Memuat jadwal...' : 'Pilih dokter dulu'}</option>`;
            jadwalSelect.disabled = !dokterId;
            if (!dokterId) return;

            const res = await fetch(`${window.__apiBase}/pasien/dokter/${dokterId}/jadwal`, { headers });
            const data = await res.json();
            if (!res.ok) {
                window.__showAlert(data.message || 'Gagal memuat jadwal dokter');
                jadwalSelect.innerHTML = `<option value="">Tidak ada jadwal</option>`;
                return;
            }
            jadwalRows = data.data || [];
            if (!jadwalRows.length) {
                jadwalSelect.innerHTML = `<option value="">Tidak ada jadwal tersedia</option>`;
                return;
            }
            jadwalSelect.innerHTML = `<option value="">Pilih jadwal</option>` + jadwalRows.map(j =>
                `<option value="${j.id}">${esc(j.hari)} · ${esc(j.jam_mulai)} - ${esc(j.jam_selesai)}</option>`
            ).join('');
        });

        jadwalSelect.addEventListener('change', function () {
            const j = jadwalRows.find(x => String(x.id) === String(jadwalSelect.value));
            if (j?.jam_mulai) jamInput.value = j.jam_mulai;
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!pasienId) return window.__showAlert('Profil pasien belum terhubung dengan akun ini.');
            if (!dokterSelect.value || !jadwalSelect.value || !tanggalInput.value || !jamInput.value || !keluhanInput.value.trim()) {
                return window.__showAlert('Lengkapi semua field wajib.');
            }

            const body = {
                pasien_id: Number(pasienId),
                dokter_id: Number(dokterSelect.value),
                jadwal_dokter_id: Number(jadwalSelect.value),
                tanggal_pendaftaran: tanggalInput.value,
                jam_kunjungan: jamInput.value,
                keluhan: keluhanInput.value.trim(),
            };
            const res = await fetch(window.pasienApiUrl('daftar'), {
                method: 'POST',
                headers: { ...headers, 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (!res.ok) {
                const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal mendaftar');
                return window.__showAlert(msg);
            }
            window.__showAlert('Pendaftaran berhasil dikirim.', true);
            keluhanInput.value = '';
        });

        tanggalInput.min = new Date().toISOString().slice(0, 10);
        loadInitial().catch((e) => window.__showAlert(e.message));
    })();
</script>
@endpush
