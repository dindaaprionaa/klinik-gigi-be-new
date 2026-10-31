@extends('layouts.admin')

@section('title', 'Pasien')
@section('heading', 'Pasien')
@section('subheading', 'CRUD data pasien.')

@section('content')
    <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <input id="search" type="search" placeholder="Cari nama, no. identitas, pendaftaran, email…"
                       class="w-72 min-w-[12rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                <select id="filter-jk" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <option value="">Semua jenis kelamin</option>
                    <option value="Laki-laki">Laki-laki</option>
                    <option value="Perempuan">Perempuan</option>
                </select>
                <button id="refresh" type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium dark:border-slate-700">Refresh</button>
            </div>
            <button id="btn-add" type="button" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">+ Tambah Pasien</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2 pr-3">Nama</th>
                    <th class="py-2 pr-3">No Pendaftaran</th>
                    <th class="py-2 pr-3">No Identitas</th>
                    <th class="py-2 pr-3">Telepon</th>
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
        <div class="w-full max-w-3xl rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="modal-title" class="text-lg font-semibold">Tambah Pasien</h2>
                    <p class="text-xs text-slate-500">Field bertanda * wajib.</p>
                </div>
                <button id="modal-close" class="rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Tutup</button>
            </div>

            <form id="form" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <input type="hidden" id="id">

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">No Pendaftaran *</label>
                    <input id="no_pendaftaran" data-field="no_pendaftaran" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Nama *</label>
                    <input id="nama" data-field="nama" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">No Identitas *</label>
                    <input id="no_identitas" data-field="no_identitas" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Jenis Kelamin *</label>
                    <select id="jenis_kelamin" data-field="jenis_kelamin" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Tanggal Lahir *</label>
                    <input id="tanggal_lahir" data-field="tanggal_lahir" type="date" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">No Telepon *</label>
                    <input id="no_telepon" data-field="no_telepon" required maxlength="50" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Alamat *</label>
                    <textarea id="alamat" data-field="alamat" rows="2" required maxlength="2000" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="text-xs font-medium text-slate-600 dark:text-slate-400">Email</label>
                    <input id="email" data-field="email" type="email" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
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
        const filterJk = document.getElementById('filter-jk');
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
            const jk = filterJk.value;
            if (jk) p.set('jenis_kelamin', jk);
            return '/admin/pasien?' + p.toString();
        }

        function clearFieldHighlights() {
            document.querySelectorAll('[data-field]').forEach((el) => {
                el.classList.remove('ring-2', 'ring-red-500', 'border-red-500');
            });
        }

        function openModal(mode, row) {
            clearFieldHighlights();
            errorsEl.classList.add('hidden');
            errorsEl.textContent = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalTitle.textContent = mode === 'edit' ? 'Edit Pasien' : 'Tambah Pasien';

            document.getElementById('id').value = row?.id ?? '';
            document.getElementById('no_pendaftaran').value = row?.no_pendaftaran ?? '';
            document.getElementById('nama').value = row?.nama ?? '';
            document.getElementById('no_identitas').value = row?.no_identitas ?? '';
            document.getElementById('jenis_kelamin').value = row?.jenis_kelamin ?? 'Laki-laki';
            document.getElementById('tanggal_lahir').value = row?.tanggal_lahir ? String(row.tanggal_lahir).slice(0, 10) : '';
            document.getElementById('alamat').value = row?.alamat ?? '';
            document.getElementById('no_telepon').value = row?.no_telepon ?? '';
            document.getElementById('email').value = row?.email ?? '';
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function render() {
            tbl.innerHTML = rows.map((r) => `
                <tr class="border-t border-slate-100 dark:border-slate-800">
                    <td class="py-2 pr-3 font-medium">${esc(r.nama)}</td>
                    <td class="py-2 pr-3">${esc(r.no_pendaftaran)}</td>
                    <td class="py-2 pr-3">${esc(r.no_identitas)}</td>
                    <td class="py-2 pr-3">${esc(r.no_telepon)}</td>
                    <td class="py-2 pr-3">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn-edit rounded-lg border border-slate-300 px-2 py-1 text-xs font-semibold dark:border-slate-700" data-id="${r.id}">Edit</button>
                            <button type="button" class="btn-del rounded-lg bg-rose-600 px-2 py-1 text-xs font-semibold text-white hover:bg-rose-700" data-id="${r.id}">Hapus</button>
                        </div>
                    </td>
                </tr>
            `).join('');

            const { total, from, to, current_page, last_page } = meta;
            hint.textContent = total
                ? `Menampilkan ${from ?? 0}–${to ?? 0} dari ${total} pasien (halaman ${current_page}/${last_page})`
                : 'Tidak ada data pasien.';

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
                    if (!confirm(`Hapus pasien: ${r.nama}?`)) return;
                    try {
                        await api(`/admin/pasien/${r.id}`, { method: 'DELETE' });
                        alert('Pasien dihapus.', 'success');
                        await load(meta.current_page);
                    } catch (e) {
                        alert(e.message || 'Gagal menghapus pasien');
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
                alert(e.message || 'Gagal memuat pasien');
            }
        }

        function collectPayload() {
            const get = (id) => document.getElementById(id)?.value;
            const emailTrim = get('email')?.trim();
            return {
                no_pendaftaran: get('no_pendaftaran')?.trim(),
                nama: get('nama')?.trim(),
                no_identitas: get('no_identitas')?.trim(),
                jenis_kelamin: get('jenis_kelamin'),
                tanggal_lahir: get('tanggal_lahir'),
                alamat: get('alamat')?.trim(),
                no_telepon: get('no_telepon')?.trim(),
                email: emailTrim ? emailTrim : null,
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
        filterJk.addEventListener('change', () => load(1));
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
            try {
                if (id) {
                    await api(`/admin/pasien/${id}`, { method: 'PUT', body: JSON.stringify(collectPayload()) });
                } else {
                    await api('/admin/pasien', { method: 'POST', body: JSON.stringify(collectPayload()) });
                }
                alert('Data pasien tersimpan.', 'success');
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

