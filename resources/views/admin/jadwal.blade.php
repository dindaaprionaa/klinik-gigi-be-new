@extends('layouts.admin')

@section('title', 'Jadwal')
@section('heading', 'Jadwal Dokter')
@section('subheading', 'CRUD jadwal praktik dokter.')

@section('content')
    <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button id="refresh" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium dark:border-slate-700">Refresh</button>
            </div>
            <button id="btn-add" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">+ Tambah Jadwal</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-3">Dokter</th>
                    <th class="py-2 pr-3">Hari</th>
                    <th class="py-2 pr-3">Jam</th>
                    <th class="py-2 pr-3">Kapasitas</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 pr-3">Aksi</th>
                </tr>
                </thead>
                <tbody id="tbl"></tbody>
            </table>
        </div>
        <p id="hint" class="mt-3 text-xs text-slate-500"></p>
    </div>

    <div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-title" class="text-lg font-semibold">Tambah Jadwal</h2>
                    <p class="text-xs text-slate-500">Field bertanda * wajib.</p>
                </div>
                <button id="modal-close" class="rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Tutup</button>
            </div>

            <form id="form" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <input type="hidden" id="id">

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Dokter *</label>
                    <select id="dokter_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></select>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Hari *</label>
                    <select id="hari" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option>Senin</option><option>Selasa</option><option>Rabu</option><option>Kamis</option><option>Jumat</option><option>Sabtu</option><option>Minggu</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Kapasitas *</label>
                    <input id="kapasitas" type="number" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Jam Mulai *</label>
                    <input id="jam_mulai" type="time" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Jam Selesai *</label>
                    <input id="jam_selesai" type="time" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Status</label>
                    <select id="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>

                <div id="form-errors" class="hidden sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></div>

                <div class="sm:col-span-2 flex justify-end gap-2">
                    <button type="button" id="btn-cancel" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium dark:border-slate-700">Batal</button>
                    <button type="submit" id="btn-save" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const api = window.__adminApi;
        const alert = window.__adminAlert;

        const tbl = document.getElementById('tbl');
        const hint = document.getElementById('hint');

        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const closeBtn = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('btn-cancel');
        const addBtn = document.getElementById('btn-add');
        const refreshBtn = document.getElementById('refresh');
        const form = document.getElementById('form');
        const errorsEl = document.getElementById('form-errors');

        const dokterSelect = document.getElementById('dokter_id');

        let rows = [];
        let dokters = [];

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function fillDoktersOptions(selectedId = '') {
            dokterSelect.innerHTML = `<option value="">Pilih dokter</option>` + dokters.map(d =>
                `<option value="${d.id}" ${String(d.id) === String(selectedId) ? 'selected' : ''}>${esc(d.nama)} — ${esc(d.spesialisasi)}</option>`
            ).join('');
        }

        function openModal(mode, row) {
            errorsEl.classList.add('hidden');
            errorsEl.textContent = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalTitle.textContent = mode === 'edit' ? 'Edit Jadwal' : 'Tambah Jadwal';

            document.getElementById('id').value = row?.id ?? '';
            fillDoktersOptions(row?.dokter_id ?? row?.dokter?.id ?? '');
            document.getElementById('hari').value = row?.hari ?? 'Senin';
            document.getElementById('jam_mulai').value = row?.jam_mulai ?? '';
            document.getElementById('jam_selesai').value = row?.jam_selesai ?? '';
            document.getElementById('kapasitas').value = row?.kapasitas ?? 1;
            document.getElementById('status').value = String(row?.status ? 1 : 0);
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function render() {
            tbl.innerHTML = rows.map(r => {
                const status = r.status
                    ? '<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Aktif</span>'
                    : '<span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-700">Nonaktif</span>';
                return `
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="py-2 pr-3 font-medium">${esc(r.dokter?.nama)}</td>
                        <td class="py-2 pr-3">${esc(r.hari)}</td>
                        <td class="py-2 pr-3">${esc(r.jam_mulai)} - ${esc(r.jam_selesai)}</td>
                        <td class="py-2 pr-3">${esc(r.kapasitas)}</td>
                        <td class="py-2 pr-3">${status}</td>
                        <td class="py-2 pr-3">
                            <div class="flex flex-wrap gap-2">
                                <button class="btn-edit rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold dark:border-slate-700" data-id="${r.id}">Edit</button>
                                <button class="btn-del rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700" data-id="${r.id}">Hapus</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            hint.textContent = `Menampilkan ${rows.length} jadwal`;

            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', () => {
                    const r = rows.find(x => String(x.id) === String(btn.dataset.id));
                    if (r) openModal('edit', r);
                });
            });
            document.querySelectorAll('.btn-del').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const r = rows.find(x => String(x.id) === String(btn.dataset.id));
                    if (!r) return;
                    if (!confirm(`Hapus jadwal ${r.dokter?.nama} (${r.hari} ${r.jam_mulai}-${r.jam_selesai})?`)) return;
                    try {
                        await api(`/admin/jadwal/${r.id}`, { method: 'DELETE' });
                        alert('Jadwal dihapus.', 'success');
                        await load();
                    } catch (e) {
                        alert(e.message || 'Gagal menghapus jadwal');
                    }
                });
            });
        }

        async function load() {
            try {
                const [jdw, dkt] = await Promise.all([
                    api('/admin/jadwal'),
                    api('/admin/dokter?per_page=100'),
                ]);
                rows = jdw.data || [];
                dokters = dkt.data || [];
                render();
            } catch (e) {
                alert(e.message || 'Gagal memuat jadwal');
            }
        }

        function collectPayload(isEdit) {
            const get = (id) => document.getElementById(id)?.value;
            const payload = {
                jam_mulai: get('jam_mulai'),
                jam_selesai: get('jam_selesai'),
                kapasitas: Number(get('kapasitas')),
            };
            if (!isEdit) {
                payload.dokter_id = Number(get('dokter_id'));
                payload.hari = get('hari');
            } else {
                payload.status = get('status') === '1';
            }
            return payload;
        }

        function showFormErrors(err) {
            const errors = err?.data?.errors;
            if (!errors) {
                errorsEl.textContent = err.message || 'Gagal menyimpan';
                errorsEl.classList.remove('hidden');
                return;
            }
            const msgs = [];
            Object.keys(errors).forEach(k => {
                const v = errors[k];
                if (Array.isArray(v)) msgs.push(...v);
                else if (v) msgs.push(String(v));
            });
            errorsEl.textContent = msgs.join(' ');
            errorsEl.classList.remove('hidden');
        }

        addBtn.addEventListener('click', () => openModal('create', null));
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        refreshBtn.addEventListener('click', load);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorsEl.classList.add('hidden');
            errorsEl.textContent = '';
            const id = document.getElementById('id').value;
            const isEdit = Boolean(id);
            try {
                if (isEdit) {
                    await api(`/admin/jadwal/${id}`, { method: 'PUT', body: JSON.stringify(collectPayload(true)) });
                } else {
                    await api('/admin/jadwal', { method: 'POST', body: JSON.stringify(collectPayload(false)) });
                }
                alert('Data jadwal tersimpan.', 'success');
                closeModal();
                await load();
            } catch (err) {
                showFormErrors(err);
            }
        });

        load();
    })();
</script>
@endpush

