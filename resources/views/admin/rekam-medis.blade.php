@extends('layouts.admin')

@section('title', 'Rekam Medis')
@section('heading', 'Rekam Medis')
@section('subheading', 'Daftar rekam medis (admin bisa melihat).')

@section('content')
    <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button id="refresh" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium dark:border-slate-700">Refresh</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-3">Tanggal</th>
                    <th class="py-2 pr-3">Pasien</th>
                    <th class="py-2 pr-3">Dokter</th>
                    <th class="py-2 pr-3">Keluhan</th>
                    <th class="py-2 pr-3">Diagnosis</th>
                </tr>
                </thead>
                <tbody id="tbl"></tbody>
            </table>
        </div>
        <p id="hint" class="mt-3 text-xs text-slate-500"></p>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const api = window.__adminApi;
        const alert = window.__adminAlert;
        const tbl = document.getElementById('tbl');
        const hint = document.getElementById('hint');
        const refreshBtn = document.getElementById('refresh');

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function load() {
            try {
                const d = await api('/rekam-medis');
                const rows = d.data || [];
                tbl.innerHTML = rows.map(r => `
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="py-2 pr-3 font-medium">${esc(r.tanggal_kunjungan)}</td>
                        <td class="py-2 pr-3">${esc(r.pasien?.nama)}</td>
                        <td class="py-2 pr-3">${esc(r.dokter?.nama)}</td>
                        <td class="py-2 pr-3">${esc(r.keluhan_utama)}</td>
                        <td class="py-2 pr-3">${esc(r.diagnosis)}</td>
                    </tr>
                `).join('');
                hint.textContent = `Menampilkan ${rows.length} rekam medis`;
            } catch (e) {
                alert(e.message || 'Gagal memuat rekam medis');
            }
        }

        refreshBtn.addEventListener('click', load);
        load();
    })();
</script>
@endpush

