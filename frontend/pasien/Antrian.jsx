import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { pasienApi } from './api';
import { formatDate, statusLabel } from './utils';

/**
 * Antrian aktif pasien (satu pendaftaran non-selesai/non-batal terdekat).
 */
export default function Antrian() {
    const [state, setState] = useState({ loading: true, empty: false, error: null, item: null });

    useEffect(() => {
        let cancelled = false;
        (async () => {
            try {
                const res = await pasienApi.antrian();
                if (!cancelled) setState({ loading: false, empty: false, error: null, item: res.data });
            } catch (e) {
                if (cancelled) return;
                if (e.status === 404) {
                    setState({ loading: false, empty: true, error: null, item: null });
                    return;
                }
                setState({ loading: false, empty: false, error: e.message || 'Gagal memuat antrian', item: null });
            }
        })();
        return () => {
            cancelled = true;
        };
    }, []);

    if (state.loading) {
        return (
            <div className="flex min-h-[40vh] items-center justify-center text-slate-500">
                <p className="text-sm">Memuat antrian…</p>
            </div>
        );
    }

    if (state.error) {
        return (
            <div className="mx-auto max-w-lg px-4 py-8">
                <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
                    <p className="font-medium">{state.error}</p>
                    <Link to="/dashboard" className="mt-4 inline-block text-sm font-medium text-red-700 underline dark:text-red-300">
                        Kembali ke dashboard
                    </Link>
                </div>
            </div>
        );
    }

    if (state.empty) {
        return (
            <div className="mx-auto max-w-lg px-4 py-12 text-center">
                <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 dark:border-slate-700 dark:bg-slate-900/50">
                    <p className="text-slate-700 dark:text-slate-300">Tidak ada antrian aktif saat ini.</p>
                    <p className="mt-2 text-sm text-slate-500">Daftar kunjungan baru untuk mendapat nomor antrian.</p>
                    <Link
                        to="/daftar-berobat"
                        className="mt-6 inline-flex rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-emerald-700"
                    >
                        Daftar berobat
                    </Link>
                </div>
            </div>
        );
    }

    const a = state.item;
    const dokter = a.dokter;
    const jadwal = a.jadwal_dokter;

    return (
        <div className="mx-auto max-w-lg px-4 py-8">
            <header className="mb-6 flex items-center justify-between gap-4">
                <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Antrian saya</h1>
                <Link to="/dashboard" className="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                    Dashboard
                </Link>
            </header>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/80">
                <div className="bg-gradient-to-br from-emerald-600 to-teal-600 px-6 py-8 text-center text-white">
                    <p className="text-xs font-medium uppercase tracking-widest opacity-90">Nomor antrian</p>
                    <p className="mt-2 text-4xl font-bold tracking-tight">{a.no_antrian || '—'}</p>
                    <p className="mt-2 text-sm opacity-95">{statusLabel(a.status)}</p>
                </div>
                <div className="space-y-4 p-6 text-sm">
                    <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <span className="text-slate-500">Tanggal kunjungan</span>
                        <span className="font-medium text-slate-900 dark:text-slate-100">{formatDate(a.tanggal_pendaftaran)}</span>
                    </div>
                    <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <span className="text-slate-500">Jam</span>
                        <span className="font-medium text-slate-900 dark:text-slate-100">{a.jam_kunjungan || '—'}</span>
                    </div>
                    {a.estimasi_waktu_label && (
                        <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 dark:border-slate-800">
                            <span className="text-slate-500">Estimasi jam kehadiran</span>
                            <span className="text-right font-medium text-slate-900 dark:text-slate-100">{a.estimasi_waktu_label}</span>
                        </div>
                    )}
                    <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 dark:border-slate-800">
                        <span className="text-slate-500">Dokter</span>
                        <span className="text-right font-medium text-slate-900 dark:text-slate-100">{dokter?.nama || '—'}</span>
                    </div>
                    {jadwal && (
                        <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 dark:border-slate-800">
                            <span className="text-slate-500">Jadwal praktik</span>
                            <span className="text-right font-medium text-slate-900 dark:text-slate-100">
                                {jadwal.hari} · {jadwal.jam_mulai}–{jadwal.jam_selesai}
                            </span>
                        </div>
                    )}
                    <div>
                        <span className="text-slate-500">Keluhan</span>
                        <p className="mt-1 rounded-lg bg-slate-50 p-3 text-slate-800 dark:bg-slate-800/60 dark:text-slate-200">{a.keluhan || '—'}</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
