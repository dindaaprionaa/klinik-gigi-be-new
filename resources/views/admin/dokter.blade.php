@extends('layouts.admin')

@section('title', 'Dokter')
@section('heading', 'Dokter')
@section('subheading', 'CRUD data dokter.')

@section('content')
    <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <input id="search" type="search" placeholder="Cari nama, spesialisasi, email, telepon…"
                       class="w-72 min-w-[12rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                <select id="filter-status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="all">Semua status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
                <button id="refresh" type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium dark:border-slate-700">Refresh</button>
            </div>
            <button id="btn-add" type="button" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">+ Tambah Dokter</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-3">Nama</th>
                    <th class="py-2 pr-3">Spesialisasi</th>
                    <th class="py-2 pr-3">Email</th>
                    <th class="py-2 pr-3">Telepon</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 pr-3">Aksi</th>
                </tr>
                </thead>
                <tbody id="tbl"></tbody>
            </table>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
            <p id="hint" class="text-xs text-slate-500"></p>
            <div class="flex items-center gap-2">
                <button type="button" id="prev-page" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium disabled:opacity-40 dark:border-slate-600">Sebelumnya</button>
                <span id="page-info" class="text-xs text-slate-600 dark:text-slate-400"></span>
                <button type="button" id="next-page" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium disabled:opacity-40 dark:border-slate-600">Berikutnya</button>
            </div>
        </div>
    </div>

    <div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-title" class="text-lg font-semibold">Tambah Dokter</h2>
                    <p class="text-xs text-slate-500">Field bertanda * wajib.</p>
                </div>
                <button id="modal-close" class="rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Tutup</button>
            </div>

            <form id="form" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <input type="hidden" id="id">

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Nama *</label>
                    <input id="nama" data-field="nama" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">No Identitas *</label>
                    <input id="no_identitas" data-field="no_identitas" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">No Lisensi *</label>
                    <input id="no_lisensi" data-field="no_lisensi" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Spesialisasi *</label>
                    <input id="spesialisasi" data-field="spesialisasi" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Hari Libur</label>
                    <select id="hari_libur" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="">—</option>
                        <option>Senin</option><option>Selasa</option><option>Rabu</option><option>Kamis</option><option>Jumat</option><option>Sabtu</option><option>Minggu</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Email *</label>
                    <input id="email" data-field="email" type="email" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">No Telepon *</label>
                    <input id="no_telepon" data-field="no_telepon" required maxlength="50" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Jam Mulai *</label>
                    <input id="jam_praktek_mulai" data-field="jam_praktek_mulai" type="time" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Jam Selesai *</label>
                    <input id="jam_praktek_selesai" data-field="jam_praktek_selesai" type="time" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Alamat *</label>
                    <textarea id="alamat" data-field="alamat" rows="2" required maxlength="2000" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Status *</label>
                    <select id="status" data-field="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
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
        const q = document.getElementById('search');
        const filterStatus = document.getElementById('filter-status');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const pageInfo = document.getElementById('page-info');

        const modal = document.getElementById('modal');
        const modalTitle = document.getElementById('modal-title');
        const closeBtn = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('btn-cancel');
        const addBtn = document.getElementById('btn-add');
        const refreshBtn = document.getElementById('refresh');
        const form = document.getElementById('form');
        const errorsEl = document.getElementById('form-errors');

        const fields = [
            'id', 'nama', 'no_identitas', 'spesialisasi', 'no_lisensi', 'no_telepon', 'email', 'alamat',
            'jam_praktek_mulai', 'jam_praktek_selesai', 'hari_libur', 'status',
        ];

        let rows = [];
        let meta = { current_page: 1, last_page: 1, total: 0, from: 0, to: 0 };
        let searchTimer = null;
        const perPage = 20;

        function esc(s) {
            return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function buildQuery(page) {
            const p = new URLSearchParams();
            p.set('page', String(page));
            p.set('per_page', String(perPage));
            const s = q.value.trim();
            if (s) p.set('search', s);
            const st = filterStatus.value;
            if (st !== 'all') p.set('status', st);
            return '/admin/dokter?' + p.toString();
        }

        function openModal(mode, row) {
            clearFieldHighlights();
            errorsEl.classList.add('hidden');
            errorsEl.textContent = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalTitle.textContent = mode === 'edit' ? 'Edit Dokter' : 'Tambah Dokter';

            fields.forEach((f) => {
                const el = document.getElementById(f);
                if (!el) return;
                if (f === 'id') el.value = row?.id ?? '';
                else if (f === 'status') el.value = String(row?.status ? 1 : 0);
                else el.value = row?.[f] ?? '';
            });
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function clearFieldHighlights() {
            document.querySelectorAll('[data-field]').forEach((el) => {
                el.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
            });
        }

        function render() {
            tbl.innerHTML = rows.map((r) => {
                const status = r.status
                    ? '<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Aktif</span>'
                    : '<span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-700">Nonaktif</span>';
                return `
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="py-2 pr-3 font-medium">${esc(r.nama)}</td>
                        <td class="py-2 pr-3">${esc(r.spesialisasi)}</td>
                        <td class="py-2 pr-3">${esc(r.email)}</td>
                        <td class="py-2 pr-3">${esc(r.no_telepon)}</td>
                        <td class="py-2 pr-3">${status}</td>
                        <td class="py-2 pr-3">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="btn-edit rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold dark:border-slate-700" data-id="${r.id}">Edit</button>
                                <button type="button" class="btn-del rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700" data-id="${r.id}">Hapus</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            const { total, from, to, current_page, last_page } = meta;
            hint.textContent = total
                ? `Menampilkan ${from ?? 0}–${to ?? 0} dari ${total} dokter (halaman ${current_page}/${last_page})`
                : 'Tidak ada data dokter.';

            pageInfo.textContent = `Halaman ${current_page} / ${last_page}`;
            prevBtn.disabled = current_page <= 1;
            nextBtn.disabled = current_page >= last_page;

            document.querySelectorAll('.btn-edit').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const r = rows.find((x) => String(x.id) === String(btn.dataset.id));
                    if (r) openModal('edit', r);
                });
            });
            document.querySelectorAll('.btn-del').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const r = rows.find((x) => String(x.id) === String(btn.dataset.id));
                    if (!r) return;
                    if (!confirm(`Hapus dokter: ${r.nama}?`)) return;
                    try {
                        await api(`/admin/dokter/${r.id}`, { method: 'DELETE' });
                        alert('Dokter dihapus.', 'success');
                        await load(meta.current_page);
                    } catch (e) {
                        alert(e.message || 'Gagal menghapus dokter');
                    }
                });
            });
        }

        async function load(page = 1) {
            try {
                const d = await api(buildQuery(page));
                rows = d.data || [];
                meta = {
                    current_page: d.current_page || 1,
                    last_page: d.last_page || 1,
                    total: d.total ?? 0,
                    from: d.from ?? 0,
                    to: d.to ?? 0,
                };
                render();
            } catch (e) {
                alert(e.message || 'Gagal memuat dokter');
            }
        }

        function collectPayload() {
            const get = (id) => document.getElementById(id)?.value;
            return {
                nama: get('nama')?.trim(),
                spesialisasi: get('spesialisasi')?.trim(),
                no_telepon: get('no_telepon')?.trim(),
                hari_libur: get('hari_libur') || null,
                status: get('status') === '1',
                no_identitas: get('no_identitas')?.trim(),
                no_lisensi: get('no_lisensi')?.trim(),
                email: get('email')?.trim(),
                alamat: get('alamat')?.trim(),
                jam_praktek_mulai: get('jam_praktek_mulai'),
                jam_praktek_selesai: get('jam_praktek_selesai'),
            };
        }

        function showFormErrors(err) {
            clearFieldHighlights();
            const errors = err?.data?.errors;
            if (!errors) {
                errorsEl.textContent = err.message || 'Gagal menyimpan';
                errorsEl.classList.remove('hidden');
                return;
            }
            const msgs = [];
            Object.keys(errors).forEach((k) => {
                const v = errors[k];
                if (Array.isArray(v)) msgs.push(...v);
                else if (v) msgs.push(String(v));
                const el = document.querySelector(`[data-field="${k}"]`);
                if (el) el.classList.add('ring-2', 'ring-red-500', 'border-red-500');
            });
            errorsEl.textContent = msgs.join(' ');
            errorsEl.classList.remove('hidden');
        }

        function scheduleSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => load(1), 320);
        }

        addBtn.addEventListener('click', () => openModal('create', null));
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        refreshBtn.addEventListener('click', () => load(meta.current_page));
        q.addEventListener('input', scheduleSearch);
        filterStatus.addEventListener('change', () => load(1));
        prevBtn.addEventListener('click', () => {
            if (meta.current_page > 1) load(meta.current_page - 1);
        });
        nextBtn.addEventListener('click', () => {
            if (meta.current_page < meta.last_page) load(meta.current_page + 1);
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorsEl.classList.add('hidden');
            errorsEl.textContent = '';
            clearFieldHighlights();
            const id = document.getElementById('id').value;
            const isEdit = Boolean(id);
            try {
                if (isEdit) {
                    await api(`/admin/dokter/${id}`, { method: 'PUT', body: JSON.stringify(collectPayload()) });
                } else {
                    await api('/admin/dokter', { method: 'POST', body: JSON.stringify(collectPayload()) });
                }
                alert('Data dokter tersimpan.', 'success');
                closeModal();
                await load(meta.current_page);
            } catch (err) {
                showFormErrors(err);
            }
        });

        load(1);
    })();
</script>
@endpush

