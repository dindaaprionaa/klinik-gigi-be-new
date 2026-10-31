import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { pasienApi } from './api';
import { formatDate, statusLabel } from './utils';

/**
 * Daftar pendaftaran pasien dengan paginasi (endpoint Laravel paginate).
 */
export default function Riwayat() {
    const [state, setState] = useState({
        loading: true,
        error: null,
        page: 1,
        lastPage: 1,
        rows: [],
    });

    const load = useCallback(async (page) => {
        setState((s) => ({ ...s, loading: true, error: null }));
        try {
            const res = await pasienApi.appointments(page);
            const rows = res.data ?? [];
            const lastPage = res.last_page ?? 1;
            setState({ loading: false, error: null, page: res.current_page ?? page, lastPage, rows });
        } catch (e) {
            setState((s) => ({
                ...s,
                loading: false,
                error: e.message || 'Gagal memuat riwayat',
                rows: [],
            }));
        }
    }, []);

    useEffect(() => {
        load(1);
    }, [load]);

    if (state.loading && state.rows.length === 0) {
        return (
            <div className="flex min-h-[40vh] items-center justify-center text-slate-500">
                <p className="text-sm">Memuat riwayat…</p>
            </div>
        );
    }

    if (state.error && state.rows.length === 0) {
        return (
            <div className="mx-auto max-w-3xl px-4 py-8">
                <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                    <p>{state.error}</p>
                    <button
                        type="button"
                        onClick={() => load(1)}
                        className="mt-4 text-sm font-semibold text-red-700 underline dark:text-red-300"
                    >
                        Coba lagi
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-4 py-8">
            <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Riwayat pendaftaran</h1>
                    <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">Semua kunjungan yang pernah Anda daftarkan</p>
                </div>
                <Link to="/daftar-berobat" className="text-sm font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                    + Daftar baru
                </Link>
            </header>

            <section className="mb-6 grid gap-4 sm:grid-cols-3">
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Total riwayat</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{state.rows.length}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Halaman aktif</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{state.page}</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Total halaman</p>
                    <p className="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{state.lastPage}</p>
                </div>
            </section>

            {state.rows.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900/60">
                    <p className="text-base font-medium text-slate-700 dark:text-slate-200">Belum ada data pendaftaran.</p>
                    <p className="mt-2 text-sm text-slate-500">Mulai buat jadwal kunjungan untuk melihat riwayat Anda di halaman ini.</p>
                    <Link
                        to="/daftar-berobat"
                        className="mt-5 inline-flex rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Daftar berobat sekarang
                    </Link>
                </div>
            ) : (
                <ul className="space-y-3">
                    {state.rows.map((row) => (
                        <li
                            key={row.id}
                            className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/80 dark:hover:border-slate-700"
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-semibold text-slate-900 dark:text-white">{row.no_antrian || `ID #${row.id}`}</p>
                                    <p className="mt-0.5 text-sm text-slate-600 dark:text-slate-400">
                                        {row.dokter?.nama || 'Dokter'} · {formatDate(row.tanggal_pendaftaran)} {row.jam_kunjungan ? `· ${row.jam_kunjungan}` : ''}
                                    </p>
                                </div>
                                <span className="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {statusLabel(row.status)}
                                </span>
                            </div>
                            {row.keluhan ? (
                                <p className="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">
                                    {row.keluhan}
                                </p>
                            ) : null}
                        </li>
                    ))}
                </ul>
            )}

            {state.lastPage > 1 && (
                <nav className="mt-8 flex items-center justify-center gap-2" aria-label="Paginasi">
                    <button
                        type="button"
                        disabled={state.page <= 1 || state.loading}
                        onClick={() => load(state.page - 1)}
                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium disabled:opacity-40 dark:border-slate-600"
                    >
                        Sebelumnya
                    </button>
                    <span className="px-2 text-sm text-slate-600 dark:text-slate-400">
                        Halaman {state.page} / {state.lastPage}
                    </span>
                    <button
                        type="button"
                        disabled={state.page >= state.lastPage || state.loading}
                        onClick={() => load(state.page + 1)}
                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium disabled:opacity-40 dark:border-slate-600"
                    >
                        Berikutnya
                    </button>
                </nav>
            )}
        </div>
    );
}
