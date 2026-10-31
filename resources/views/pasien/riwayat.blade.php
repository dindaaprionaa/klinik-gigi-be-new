@extends('layouts.pasien')

@section('title', 'Riwayat')
@section('heading', 'Riwayat Pendaftaran')
@section('subheading', 'Daftar kunjungan pasien.')

@section('content')
    <section class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total riwayat (halaman ini)</p>
            <p id="sum-total" class="mt-2 text-2xl font-semibold">0</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Halaman aktif</p>
            <p id="sum-page" class="mt-2 text-2xl font-semibold">1</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total halaman</p>
            <p id="sum-last" class="mt-2 text-2xl font-semibold">1</p>
        </div>
    </section>

    <div id="empty-box" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900/60">
        <p class="text-base font-medium text-slate-700 dark:text-slate-200">Belum ada data pendaftaran.</p>
        <p class="mt-2 text-sm text-slate-500">Silakan daftar berobat agar riwayat tampil di sini.</p>
    </div>

    <ul id="list" class="space-y-3"></ul>

    <nav class="mt-8 flex items-center justify-center gap-2" aria-label="Paginasi">
        <button id="prev-btn" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium disabled:opacity-40 dark:border-slate-600">Sebelumnya</button>
        <span id="page-text" class="px-2 text-sm text-slate-600 dark:text-slate-400">Halaman 1 / 1</span>
        <button id="next-btn" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium disabled:opacity-40 dark:border-slate-600">Berikutnya</button>
    </nav>
@endsection

@push('scripts')
<script>
    (function () {
        const token = window.__authToken;
        if (!token) return window.__showAlert('Token login tidak ditemukan. Silakan login ulang.');
        const headers = { Accept: 'application/json', Authorization: `Bearer ${token}` };

        const list = document.getElementById('list');
        const emptyBox = document.getElementById('empty-box');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const pageText = document.getElementById('page-text');

        const sumTotal = document.getElementById('sum-total');
        const sumPage = document.getElementById('sum-page');
        const sumLast = document.getElementById('sum-last');

        let page = 1;
        let lastPage = 1;

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function statusLabel(status) {
            const map = { pending: 'Menunggu', confirmed: 'Dikonfirmasi', checked_in: 'Check-in', completed: 'Selesai', cancelled: 'Dibatalkan' };
            return map[status] || status || '-';
        }

        async function load(p = 1) {
            const res = await fetch(window.pasienApiUrl('appointments') + '?page=' + encodeURIComponent(p), { headers });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal memuat riwayat');

            const rows = data.data || [];
            page = data.current_page || p;
            lastPage = data.last_page || 1;

            sumTotal.textContent = rows.length;
            sumPage.textContent = page;
            sumLast.textContent = lastPage;

            if (!rows.length) {
                list.innerHTML = '';
                emptyBox.classList.remove('hidden');
            } else {
                emptyBox.classList.add('hidden');
                list.innerHTML = rows.map(r => `
                    <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-white">${esc(r.no_antrian || `ID #${r.id}`)}</p>
                                <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">${esc(r.dokter?.nama || 'Dokter')} · ${esc(r.tanggal_pendaftaran || '')} ${r.jam_kunjungan ? `· ${esc(r.jam_kunjungan)}` : ''}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">${esc(statusLabel(r.status))}</span>
                        </div>
                        ${r.keluhan ? `<p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">${esc(r.keluhan)}</p>` : ''}
                    </li>
                `).join('');
            }

            prevBtn.disabled = page <= 1;
            nextBtn.disabled = page >= lastPage;
            pageText.textContent = `Halaman ${page} / ${lastPage}`;
        }

        prevBtn.addEventListener('click', () => { if (page > 1) load(page - 1).catch(e => window.__showAlert(e.message)); });
        nextBtn.addEventListener('click', () => { if (page < lastPage) load(page + 1).catch(e => window.__showAlert(e.message)); });

        load(1).catch((e) => window.__showAlert(e.message));
    })();
</script>
@endpush
