<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel — {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Admin Panel</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">Kelola data utama rumah sakit dari satu halaman.</p>
            </div>
            <div class="flex items-center gap-2">
                <span id="admin-user" class="rounded-lg bg-white px-3 py-2 text-sm shadow-sm dark:bg-slate-900">Memuat user...</span>
                <button id="logout-btn" class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">Logout</button>
            </div>
        </header>

        <div id="global-alert" class="mb-4 hidden rounded-lg border px-4 py-3 text-sm"></div>

        <nav class="mb-6 flex flex-wrap gap-2">
            <button data-tab="dashboard" class="tab-btn rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white">Dashboard</button>
            <button data-tab="dokter" class="tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900">Dokter</button>
            <button data-tab="pasien" class="tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900">Pasien</button>
            <button data-tab="jadwal" class="tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900">Jadwal</button>
            <button data-tab="pendaftaran" class="tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900">Pendaftaran</button>
            <button data-tab="rekam" class="tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900">Rekam Medis</button>
            <button data-tab="laporan" class="tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900">Laporan</button>
        </nav>

        <section id="tab-dashboard" class="tab-panel space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Total Pasien</p><p id="kpi-pasien" class="mt-1 text-2xl font-semibold">0</p></div>
                <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Total Dokter</p><p id="kpi-dokter" class="mt-1 text-2xl font-semibold">0</p></div>
                <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Total Pendaftaran</p><p id="kpi-pendaftaran" class="mt-1 text-2xl font-semibold">0</p></div>
                <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Pendaftaran Hari Ini</p><p id="kpi-hari-ini" class="mt-1 text-2xl font-semibold">0</p></div>
                <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900"><p class="text-xs text-slate-500">Pending Verifikasi</p><p id="kpi-pending" class="mt-1 text-2xl font-semibold">0</p></div>
            </div>
        </section>

        <section id="tab-dokter" class="tab-panel hidden rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900">
            <h2 class="mb-3 text-lg font-semibold">Daftar Dokter</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-2 pr-3">Nama</th><th class="py-2 pr-3">Spesialisasi</th><th class="py-2 pr-3">Status</th></tr></thead>
                    <tbody id="tbl-dokter"></tbody>
                </table>
            </div>
        </section>

        <section id="tab-pasien" class="tab-panel hidden rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900">
            <h2 class="mb-3 text-lg font-semibold">Daftar Pasien</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-2 pr-3">Nama</th><th class="py-2 pr-3">No Identitas</th><th class="py-2 pr-3">Telepon</th></tr></thead>
                    <tbody id="tbl-pasien"></tbody>
                </table>
            </div>
        </section>

        <section id="tab-jadwal" class="tab-panel hidden rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900">
            <h2 class="mb-3 text-lg font-semibold">Jadwal Dokter</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-2 pr-3">Dokter</th><th class="py-2 pr-3">Hari</th><th class="py-2 pr-3">Jam</th><th class="py-2 pr-3">Kapasitas</th></tr></thead>
                    <tbody id="tbl-jadwal"></tbody>
                </table>
            </div>
        </section>

        <section id="tab-pendaftaran" class="tab-panel hidden rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900">
            <h2 class="mb-3 text-lg font-semibold">Pendaftaran (Verifikasi)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-2 pr-3">Antrian</th><th class="py-2 pr-3">Pasien</th><th class="py-2 pr-3">Dokter</th><th class="py-2 pr-3">Status</th><th class="py-2 pr-3">Aksi</th></tr></thead>
                    <tbody id="tbl-pendaftaran"></tbody>
                </table>
            </div>
        </section>

        <section id="tab-rekam" class="tab-panel hidden rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900">
            <h2 class="mb-3 text-lg font-semibold">Rekam Medis</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-2 pr-3">Tanggal</th><th class="py-2 pr-3">Pasien</th><th class="py-2 pr-3">Dokter</th><th class="py-2 pr-3">Diagnosis</th></tr></thead>
                    <tbody id="tbl-rekam"></tbody>
                </table>
            </div>
        </section>

        <section id="tab-laporan" class="tab-panel hidden rounded-xl bg-white p-4 shadow-sm dark:bg-slate-900">
            <h2 class="mb-3 text-lg font-semibold">Laporan Pasien</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-slate-500"><th class="py-2 pr-3">Nama</th><th class="py-2 pr-3">No Identitas</th><th class="py-2 pr-3">Email</th></tr></thead>
                    <tbody id="tbl-laporan"></tbody>
                </table>
            </div>
        </section>
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
                if (!res.ok) throw new Error(data.message || `Request gagal (${res.status})`);
                return data;
            }

            function row(cells) {
                return `<tr class="border-t border-slate-100 dark:border-slate-800">${cells.map(c => `<td class="py-2 pr-3">${c ?? '-'}</td>`).join('')}</tr>`;
            }

            async function loadMe() {
                const me = await api('/auth/me');
                if (!me.user || me.user.role !== 'admin') {
                    localStorage.removeItem('auth_token');
                    window.location.replace('{{ route('login') }}');
                    return;
                }
                document.getElementById('admin-user').textContent = `${me.user.name} (${me.user.role})`;
            }

            async function loadDashboard() {
                const d = await api('/admin/dashboard');
                document.getElementById('kpi-pasien').textContent = d.totalPasien ?? 0;
                document.getElementById('kpi-dokter').textContent = d.totalDokter ?? 0;
                document.getElementById('kpi-pendaftaran').textContent = d.totalPendaftaran ?? 0;
                document.getElementById('kpi-hari-ini').textContent = d.pendaftaranHariIni ?? 0;
                document.getElementById('kpi-pending').textContent = d.pendaftaranPending ?? 0;
            }

            async function loadDokter() {
                const d = await api('/admin/dokter?per_page=50');
                document.getElementById('tbl-dokter').innerHTML = (d.data || []).map(x =>
                    row([x.nama, x.spesialisasi, x.status ? 'Aktif' : 'Nonaktif'])
                ).join('');
            }

            async function loadPasien() {
                const d = await api('/admin/pasien?per_page=50');
                document.getElementById('tbl-pasien').innerHTML = (d.data || []).map(x =>
                    row([x.nama, x.no_identitas, x.no_telepon])
                ).join('');
            }

            async function loadJadwal() {
                const d = await api('/admin/jadwal');
                document.getElementById('tbl-jadwal').innerHTML = (d.data || []).map(x =>
                    row([x.dokter?.nama, x.hari, `${x.jam_mulai} - ${x.jam_selesai}`, x.kapasitas])
                ).join('');
            }

            async function loadPendaftaran() {
                const d = await api('/admin/pendaftaran');
                document.getElementById('tbl-pendaftaran').innerHTML = (d.data || []).map(x => {
                    const action = x.status === 'pending'
                        ? `<div class="flex gap-1"><button class="verify-btn rounded bg-emerald-600 px-2 py-1 text-xs text-white" data-id="${x.id}" data-status="confirmed">Confirm</button><button class="verify-btn rounded bg-rose-600 px-2 py-1 text-xs text-white" data-id="${x.id}" data-status="rejected">Reject</button></div>`
                        : '-';
                    return row([x.no_antrian, x.pasien?.nama, x.dokter?.nama, x.status, action]);
                }).join('');

                document.querySelectorAll('.verify-btn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        try {
                            await api(`/admin/pendaftaran/${btn.dataset.id}/verifikasi`, {
                                method: 'POST',
                                body: JSON.stringify({ status: btn.dataset.status }),
                            });
                            showAlert('Status pendaftaran diperbarui.', 'success');
                            await loadPendaftaran();
                            await loadDashboard();
                        } catch (e) {
                            showAlert(e.message);
                        }
                    });
                });
            }

            async function loadRekam() {
                const d = await api('/rekam-medis');
                document.getElementById('tbl-rekam').innerHTML = (d.data || []).map(x =>
                    row([x.tanggal_kunjungan, x.pasien?.nama, x.dokter?.nama, x.diagnosis])
                ).join('');
            }

            async function loadLaporan() {
                const d = await api('/admin/laporan/pasien');
                const rows = Array.isArray(d.data) ? d.data : (Array.isArray(d) ? d : []);
                document.getElementById('tbl-laporan').innerHTML = rows.map(x =>
                    row([x.nama, x.no_identitas, x.email])
                ).join('');
            }

            const loaders = {
                dashboard: loadDashboard,
                dokter: loadDokter,
                pasien: loadPasien,
                jadwal: loadJadwal,
                pendaftaran: loadPendaftaran,
                rekam: loadRekam,
                laporan: loadLaporan,
            };

            async function activate(tab) {
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    const active = btn.dataset.tab === tab;
                    btn.className = active
                        ? 'tab-btn rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white'
                        : 'tab-btn rounded-lg bg-white px-3 py-2 text-sm font-medium shadow-sm dark:bg-slate-900';
                });
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                document.getElementById(`tab-${tab}`).classList.remove('hidden');
                await loaders[tab]();
            }

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => activate(btn.dataset.tab).catch(e => showAlert(e.message)));
            });

            document.getElementById('logout-btn').addEventListener('click', async () => {
                try {
                    await api('/auth/logout', { method: 'POST' });
                } catch (_) {}
                localStorage.removeItem('auth_token');
                localStorage.removeItem('token');
                window.location.replace('{{ route('login') }}');
            });

            (async function init() {
                try {
                    await loadMe();
                    await activate('dashboard');
                } catch (e) {
                    showAlert(e.message || 'Gagal memuat halaman admin');
                }
            })();
        })();
    </script>
</body>
</html>
