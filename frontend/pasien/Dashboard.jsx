import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { pasienApi } from './api';
import { formatDate, statusLabel } from './utils';

/**
 * Ringkasan akun pasien: profil, kunjungan terakhir, total rekam medis.
 * Sesuaikan path <Link to="..."> dengan route React Anda.
 */
export default function Dashboard() {
    const [state, setState] = useState({ loading: true, error: null, payload: null });

    useEffect(() => {
        let cancelled = false;
        (async () => {
            try {
                const payload = await pasienApi.dashboard();
                if (!cancelled) setState({ loading: false, error: null, payload });
            } catch (e) {
                if (!cancelled) setState({ loading: false, error: e.message || 'Gagal memuat dashboard', payload: null });
            }
        })();
        return () => {
            cancelled = true;
        };
    }, []);

    if (state.loading) {
        return (
            <div className="flex min-h-[40vh] items-center justify-center text-slate-500">
                <p className="text-sm">Memuat dashboard…</p>
            </div>
        );
    }

    if (state.error) {
        return (
            <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                <p className="font-medium">Tidak dapat memuat data</p>
                <p className="mt-1 text-sm opacity-90">{state.error}</p>
            </div>
        );
    }

    const { pasien, pendaftaranTerbaru, totalKunjungan } = state.payload;

    return (
        <div className="mx-auto max-w-3xl space-y-8 px-4 py-8">
            <header>
                <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Dashboard</h1>
                <p className="mt-1 text-slate-600 dark:text-slate-400">
                    Halo, <span className="font-medium text-slate-900 dark:text-slate-200">{pasien?.nama || 'Pasien'}</span>
                </p>
            </header>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Total kunjungan tercatat</p>
                    <p className="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">{totalKunjungan ?? 0}</p>
                    <p className="mt-1 text-xs text-slate-500">Berdasarkan rekam medis</p>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">Aksi cepat</p>
                    <nav className="mt-3 flex flex-col gap-2 text-sm">
                        <Link
                            className="rounded-lg px-3 py-2 text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                            to="/daftar-berobat"
                        >
                            Daftar berobat
                        </Link>
                        <Link
                            className="rounded-lg px-3 py-2 text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                            to="/antrian"
                        >
                            Lihat antrian
                        </Link>
                        <Link
                            className="rounded-lg px-3 py-2 text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                            to="/riwayat"
                        >
                            Riwayat pendaftaran
                        </Link>
                    </nav>
                </div>
            </div>

            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <h2 className="text-sm font-semibold text-slate-900 dark:text-white">Pendaftaran terbaru</h2>
                {!pendaftaranTerbaru ? (
                    <p className="mt-4 text-sm text-slate-500">Belum ada riwayat pendaftaran.</p>
                ) : (
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-slate-500">Nomor antrian</dt>
                            <dd className="font-medium text-slate-900 dark:text-slate-100">{pendaftaranTerbaru.no_antrian || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-slate-500">Status</dt>
                            <dd className="font-medium text-slate-900 dark:text-slate-100">{statusLabel(pendaftaranTerbaru.status)}</dd>
                        </div>
                        <div>
                            <dt className="text-slate-500">Tanggal</dt>
                            <dd className="font-medium text-slate-900 dark:text-slate-100">{formatDate(pendaftaranTerbaru.tanggal_pendaftaran)}</dd>
                        </div>
                        <div>
                            <dt className="text-slate-500">Dokter</dt>
                            <dd className="font-medium text-slate-900 dark:text-slate-100">{pendaftaranTerbaru.dokter?.nama || '—'}</dd>
                        </div>
                    </dl>
                )}
            </section>
        </div>
    );
}
