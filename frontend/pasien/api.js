/**
 * Client API portal pasien — salin ke CRA di `src/services/pasienApi.js` jika perlu.
 * Base URL: REACT_APP_API_BASE_URL (contoh: http://127.0.0.1:8000/api)
 *
 * Path harus sama dengan config/pasien_portal.php → paths (backend).
 */
export const PASIEN_API_PATHS = {
    dashboard: '/pasien/dashboard',
    profile: '/pasien/profile',
    profile_photo: '/pasien/profile/photo',
    dokters: '/pasien/dokters',
    jadwal_by_dokter: '/pasien/dokter/{dokter_id}/jadwal',
    daftar: '/pasien/daftar',
    appointments: '/pasien/appointments',
    antrian: '/pasien/antrian',
};

const API_BASE = (process.env.REACT_APP_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/$/, '');

function expandPath(template, params = {}) {
    let p = template;
    Object.keys(params).forEach((k) => {
        p = p.split(`{${k}}`).join(String(params[k]));
    });
    return p;
}

function authHeader() {
    const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

export async function apiRequest(path, options = {}) {
    const url = `${API_BASE}${path.startsWith('/') ? path : `/${path}`}`;
    const isFormData = options.body instanceof FormData;
    const res = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...authHeader(),
            ...options.headers,
        },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(data.message || `Permintaan gagal (${res.status})`);
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data;
}

export const pasienApi = {
    dashboard: () => apiRequest(PASIEN_API_PATHS.dashboard),
    profile: () => apiRequest(PASIEN_API_PATHS.profile),
    updateProfile: (body) =>
        apiRequest(PASIEN_API_PATHS.profile, {
            method: 'PUT',
            body: JSON.stringify(body),
        }),
    uploadPhoto: (file) => {
        const form = new FormData();
        form.append('photo', file);
        return apiRequest(PASIEN_API_PATHS.profile_photo, {
            method: 'POST',
            body: form,
        });
    },
    antrian: () => apiRequest(PASIEN_API_PATHS.antrian),
    appointments: (page = 1) =>
        apiRequest(`${PASIEN_API_PATHS.appointments}?page=${encodeURIComponent(page)}`),
    dokters: () => apiRequest(PASIEN_API_PATHS.dokters),
    jadwalByDokter: (dokterId) =>
        apiRequest(expandPath(PASIEN_API_PATHS.jadwal_by_dokter, { dokter_id: dokterId })),
    daftar: (body) =>
        apiRequest(PASIEN_API_PATHS.daftar, {
            method: 'POST',
            body: JSON.stringify(body),
        }),
};
