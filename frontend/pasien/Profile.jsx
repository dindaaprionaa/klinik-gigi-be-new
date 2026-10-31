import { useEffect, useState } from 'react';
import { pasienApi } from './api';

/**
 * Halaman profil pasien dengan upload foto.
 * Foto disimpan di backend pada public/assets/profile tanpa kolom database tambahan.
 */
export default function Profile() {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [message, setMessage] = useState({ type: null, text: '' });
    const [form, setForm] = useState({
        nama: '',
        no_identitas: '',
        jenis_kelamin: 'Laki-laki',
        tanggal_lahir: '',
        alamat: '',
        no_telepon: '',
        email: '',
    });
    const [photoUrl, setPhotoUrl] = useState(null);

    useEffect(() => {
        (async () => {
            try {
                const res = await pasienApi.profile();
                const data = res.data || {};
                setForm({
                    nama: data.nama || '',
                    no_identitas: data.no_identitas || '',
                    jenis_kelamin: data.jenis_kelamin || 'Laki-laki',
                    tanggal_lahir: data.tanggal_lahir ? String(data.tanggal_lahir).slice(0, 10) : '',
                    alamat: data.alamat || '',
                    no_telepon: data.no_telepon || '',
                    email: data.email || '',
                });
                setPhotoUrl(data.photo_url || null);
            } catch (e) {
                setMessage({ type: 'error', text: e.message || 'Gagal memuat profil' });
            } finally {
                setLoading(false);
            }
        })();
    }, []);

    function onChange(e) {
        setForm((s) => ({ ...s, [e.target.name]: e.target.value }));
    }

    async function onSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setMessage({ type: null, text: '' });
        try {
            const res = await pasienApi.updateProfile(form);
            const data = res.data || {};
            setPhotoUrl(data.photo_url || photoUrl);
            setMessage({ type: 'success', text: 'Profil berhasil diperbarui.' });
        } catch (e) {
            setMessage({ type: 'error', text: e.message || 'Gagal menyimpan profil' });
        } finally {
            setSaving(false);
        }
    }

    async function onPhotoChange(e) {
        const file = e.target.files?.[0];
        if (!file) return;

        setUploading(true);
        setMessage({ type: null, text: '' });
        try {
            const res = await pasienApi.uploadPhoto(file);
            setPhotoUrl(res.photo_url || null);
            setMessage({ type: 'success', text: 'Foto profil berhasil diunggah.' });
        } catch (e2) {
            setMessage({ type: 'error', text: e2.message || 'Gagal upload foto' });
        } finally {
            setUploading(false);
        }
    }

    if (loading) {
        return <div className="flex min-h-[40vh] items-center justify-center text-sm text-slate-500">Memuat profil...</div>;
    }

    return (
        <div className="mx-auto max-w-3xl px-4 py-8">
            <header className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Profil</h1>
                <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">Lengkapi data profil dan foto Anda.</p>
            </header>

            {message.type && (
                <div
                    className={`mb-4 rounded-xl border px-4 py-3 text-sm ${
                        message.type === 'success'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                            : 'border-red-200 bg-red-50 text-red-800'
                    }`}
                >
                    {message.text}
                </div>
            )}

            <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
                <aside className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div className="flex flex-col items-center text-center">
                        {photoUrl ? (
                            <img
                                src={photoUrl}
                                alt="Foto profil"
                                className="h-32 w-32 rounded-full object-cover ring-4 ring-emerald-100 dark:ring-emerald-900/40"
                            />
                        ) : (
                            <div className="flex h-32 w-32 items-center justify-center rounded-full bg-emerald-100 text-4xl font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                {form.nama?.slice(0, 1)?.toUpperCase() || 'P'}
                            </div>
                        )}
                        <label className="mt-4 inline-flex cursor-pointer rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            {uploading ? 'Mengunggah...' : 'Upload foto'}
                            <input type="file" accept="image/png,image/jpeg,image/webp" className="hidden" onChange={onPhotoChange} />
                        </label>
                        <p className="mt-2 text-xs text-slate-500">Opsional. File disimpan di folder assets backend.</p>
                    </div>
                </aside>

                <form onSubmit={onSubmit} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Nama</label>
                            <input name="nama" value={form.nama} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">No Identitas</label>
                            <input name="no_identitas" value={form.no_identitas} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Jenis Kelamin</label>
                            <select name="jenis_kelamin" value={form.jenis_kelamin} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" value={form.tanggal_lahir} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">No Telepon</label>
                            <input name="no_telepon" value={form.no_telepon} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                            <input type="email" name="email" value={form.email} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Alamat</label>
                            <textarea name="alamat" rows={3} value={form.alamat} onChange={onChange} className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end">
                        <button type="submit" disabled={saving} className="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                            {saving ? 'Menyimpan...' : 'Simpan profil'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
