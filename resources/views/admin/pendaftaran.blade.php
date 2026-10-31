@extends('layouts.admin')

@section('title', 'Pendaftaran')
@section('heading', 'Pendaftaran')
@section('subheading', 'Verifikasi pendaftaran pasien.')

@section('content')
    <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <button id="refresh" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium dark:border-slate-700">Refresh</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-3">Antrian</th>
                    <th class="py-2 pr-3">Tanggal</th>
                    <th class="py-2 pr-3">Pasien</th>
                    <th class="py-2 pr-3">Dokter</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 pr-3">Aksi</th>
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

        let rows = [];

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function badge(status) {
            const map = {
                pending: 'bg-amber-100 text-amber-800',
                confirmed: 'bg-emerald-100 text-emerald-800',
                checked_in: 'bg-sky-100 text-sky-800',
                completed: 'bg-slate-200 text-slate-800',
                cancelled: 'bg-rose-100 text-rose-800',
            };
            const cls = map[status] || 'bg-slate-200 text-slate-800';
            return `<span class="rounded-full px-2 py-1 text-xs font-medium ${cls}">${esc(status)}</span>`;
        }

        function render() {
            tbl.innerHTML = rows.map(r => {
                const actions = r.status === 'pending'
                    ? `<div class="flex gap-1">
                           <button class="verify rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700" data-id="${r.id}" data-status="confirmed">Confirm</button>
                           <button class="verify rounded bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700" data-id="${r.id}" data-status="rejected">Reject</button>
                       </div>`
                    : '-';
                return `
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="py-2 pr-3 font-medium">${esc(r.no_antrian)}</td>
                        <td class="py-2 pr-3">${esc(r.tanggal_pendaftaran)} ${r.jam_kunjungan ? '· ' + esc(r.jam_kunjungan) : ''}</td>
                        <td class="py-2 pr-3">${esc(r.pasien?.nama)}</td>
                        <td class="py-2 pr-3">${esc(r.dokter?.nama)}</td>
                        <td class="py-2 pr-3">${badge(r.status)}</td>
                        <td class="py-2 pr-3">${actions}</td>
                    </tr>
                `;
            }).join('');

            hint.textContent = `Menampilkan ${rows.length} pendaftaran (halaman ${1})`;

            document.querySelectorAll('.verify').forEach(btn => {
                btn.addEventListener('click', async () => {
                    try {
                        await api(`/admin/pendaftaran/${btn.dataset.id}/verifikasi`, {
                            method: 'POST',
                            body: JSON.stringify({ status: btn.dataset.status }),
                        });
                        alert('Status pendaftaran diperbarui.', 'success');
                        await load();
                    } catch (e) {
                        alert(e.message || 'Gagal verifikasi');
                    }
                });
            });
        }

        async function load() {
            try {
                const d = await api('/admin/pendaftaran');
                rows = (d.data || []).sort((a, b) => {
                    const ta = new Date(a.created_at || 0).getTime();
                    const tb = new Date(b.created_at || 0).getTime();
                    return ta - tb || (a.id - b.id);
                });
                render();
            } catch (e) {
                alert(e.message || 'Gagal memuat pendaftaran');
            }
        }

        refreshBtn.addEventListener('click', load);
        load();
    })();
</script>
@endpush

