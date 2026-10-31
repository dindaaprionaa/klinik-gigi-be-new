import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { pasienApi } from './api';

/**
 * Form pendaftaran: membutuhkan pasien_id dari profil/dashboard.
 */
export default function DaftarBerobat() {
    const [dokters, setDokters] = useState([]);
    const [jadwalList, setJadwalList] = useState([]);
    const [pasienId, setPasienId] = useState(null);
    const [loadingMeta, setLoadingMeta] = useState(true);
    const [loadingJadwal, setLoadingJadwal] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [message, setMessage] = useState({ type: null, text: '' });

    const [dokterId, setDokterId] = useState('');
    const [jadwalDokterId, setJadwalDokterId] = useState('');
    const [tanggal, setTanggal] = useState(() => new Date().toISOString().slice(0, 10));
    const [jamKunjungan, setJamKunjungan] = useState('');
    const [keluhan, setKeluhan] = useState('');

    useEffect(() => {
        (async () => {
            try {
                const [dash, dokRes] = await Promise.all([pasienApi.dashboard(), pasienApi.dokters()]);
                setPasienId(dash.pasien?.id ?? null);
                setDokters(dokRes.data || []);
            } catch (e) {
                setMessage({ type: 'error', text: e.message || 'Gagal memuat data awal' });
            } finally {
                setLoadingMeta(false);
            }
        })();
    }, []);

    useEffect(() => {
        if (!dokterId) {
            setJadwalList([]);
            setJadwalDokterId('');
            setJamKunjungan('');
            return;
        }
        let cancelled = false;
        (async () => {
            setLoadingJadwal(true);
            setJadwalDokterId('');
            setJamKunjungan('');
            try {
                const res = await pasienApi.jadwalByDokter(dokterId);
                if (!cancelled) setJadwalList(res.data || []);
            } catch {
                if (!cancelled) {
                    setJadwalList([]);
                    setMessage({ type: 'error', text: 'Tidak dapat memuat jadwal dokter.' });
                }
            } finally {
                if (!cancelled) setLoadingJadwal(false);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [dokterId]);

    useEffect(() => {
        const j = jadwalList.find((x) => String(x.id) === String(jadwalDokterId));
        if (j?.jam_mulai) setJamKunjungan(j.jam_mulai);
    }, [jadwalDokterId, jadwalList]);

    async function handleSubmit(e) {
        e.preventDefault();
        setMessage({ type: null, text: '' });
        if (!pasienId) {
            setMessage({ type: 'error', text: 'Profil pasien tidak ditemukan. Lengkapi data atau hubungi admin.' });
            return;
        }
        if (!dokterId || !jadwalDokterId || !tanggal || !jamKunjungan || !keluhan.trim()) {
            setMessage({ type: 'error', text: 'Lengkapi semua field wajib.' });
            return;
        }

        setSubmitting(true);
        try {
            await pasienApi.daftar({
                pasien_id: Number(pasienId),
                dokter_id: Number(dokterId),
                jadwal_dokter_id: Number(jadwalDokterId),
                tanggal_pendaftaran: tanggal,
                jam_kunjungan: jamKunjungan,
                keluhan: keluhan.trim(),
            });
            setMessage({ type: 'success', text: 'Pendaftaran berhasil dikirim. Tunggu verifikasi atau lihat antrian.' });
            setKeluhan('');
        } catch (err) {
            const msg = err.data?.errors
                ? Object.values(err.data.errors)
                      .flat()
                      .join(' ')
                : err.message;
            setMessage({ type: 'error', text: msg || 'Gagal mendaftar' });
        } finally {
            setSubmitting(false);
        }
    }

    if (loadingMeta) {
        return (
            <div className="flex min-h-[40vh] items-center justify-center text-slate-500">
                <p className="text-sm">Memuat formulir…</p>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-lg px-4 py-8">
            <header className="mb-6 flex items-center justify-between gap-4">
                <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Daftar berobat</h1>
                <Link to="/dashboard" className="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                    Dashboard
                </Link>
            </header>

            {message.type && (
                <div
                    className={`mb-6 rounded-xl border p-4 text-sm ${
                        message.type === 'success'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100'
                            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200'
                    }`}
                >
                    {message.text}
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                <div>
                    <label htmlFor="dokter" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Dokter
                    </label>
                    <select
                        id="dokter"
                        required
                        value={dokterId}
                        onChange={(e) => setDokterId(e.target.value)}
                        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                    >
                        <option value="">Pilih dokter</option>
                        {dokters.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.nama} — {d.spesialisasi}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="jadwal" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Jadwal praktik
                    </label>
                    <select
                        id="jadwal"
                        required
                        disabled={!dokterId || loadingJadwal}
                        value={jadwalDokterId}
                        onChange={(e) => setJadwalDokterId(e.target.value)}
                        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                    >
                        <option value="">{loadingJadwal ? 'Memuat jadwal…' : dokterId ? 'Pilih slot' : 'Pilih dokter dulu'}</option>
                        {jadwalList.map((j) => (
                            <option key={j.id} value={j.id}>
                                {j.hari} · {j.jam_mulai}–{j.jam_selesai}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="tanggal" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Tanggal kunjungan
                    </label>
                    <input
                        id="tanggal"
                        type="date"
                        required
                        value={tanggal}
                        min={new Date().toISOString().slice(0, 10)}
                        onChange={(e) => setTanggal(e.target.value)}
                        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                    />
                </div>

                <div>
                    <label htmlFor="jam" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Jam kunjungan
                    </label>
                    <input
                        id="jam"
                        type="time"
                        required
                        value={jamKunjungan}
                        onChange={(e) => setJamKunjungan(e.target.value)}
                        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                    />
                </div>

                <div>
                    <label htmlFor="keluhan" className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Keluhan
                    </label>
                    <textarea
                        id="keluhan"
                        required
                        rows={4}
                        value={keluhan}
                        onChange={(e) => setKeluhan(e.target.value)}
                        placeholder="Jelaskan keluhan utama Anda"
                        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                    />
                </div>

                <button
                    type="submit"
                    disabled={submitting || !pasienId}
                    className="w-full rounded-lg bg-emerald-600 py-3 text-sm font-semibold text-white shadow hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {submitting ? 'Mengirim…' : 'Kirim pendaftaran'}
                </button>

                {!pasienId && (
                    <p className="text-center text-xs text-amber-700 dark:text-amber-400">
                        Akun Anda belum terhubung ke data pasien. Pastikan email di profil sama dengan data pasien di klinik.
                    </p>
                )}
            </form>
        </div>
    );
}
