// src/services/api.js
const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://127.0.0.1:8000/api';

class ApiService {
    constructor() {
        this.baseURL = API_BASE_URL;
    }

    // Helper method untuk membuat full URL
    getUrl(uri) {
        return `${this.baseURL}${uri}`;
    }

    // Helper method untuk headers dengan token
    getAuthHeaders() {
        const token = localStorage.getItem('auth_token') || localStorage.getItem('token');
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` })
        };
    }

    // Generic API methods
    async get(uri, params = {}) {
        const url = new URL(this.getUrl(uri));
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));

        const response = await fetch(url, {
            method: 'GET',
            headers: this.getAuthHeaders()
        });

        return this.handleResponse(response);
    }

    async post(uri, data = {}) {
        const response = await fetch(this.getUrl(uri), {
            method: 'POST',
            headers: this.getAuthHeaders(),
            body: JSON.stringify(data)
        });

        return this.handleResponse(response);
    }

    async put(uri, data = {}) {
        const response = await fetch(this.getUrl(uri), {
            method: 'PUT',
            headers: this.getAuthHeaders(),
            body: JSON.stringify(data)
        });

        return this.handleResponse(response);
    }

    async delete(uri) {
        const response = await fetch(this.getUrl(uri), {
            method: 'DELETE',
            headers: this.getAuthHeaders()
        });

        return this.handleResponse(response);
    }

    /**
     * Master dokter (admin) — selaras DokterController::index + Blade admin/dokter.
     * @param {Object} params search, status ('1'|'0'|true|false, omit jika semua), page, per_page (1–100)
     */
    async listAdminDokter(params = {}) {
        return this.get('/admin/dokter', params);
    }

    /**
     * Master pasien (admin) — selaras PasienController::index + Blade admin/pasien.
     * @param {Object} params search, jenis_kelamin ('Laki-laki'|'Perempuan'), page, per_page (1–100)
     */
    async listAdminPasien(params = {}) {
        return this.get('/admin/pasien', params);
    }

    async handleResponse(response) {
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const err = new Error(data.message || `HTTP error! status: ${response.status}`);
            err.status = response.status;
            err.data = data;
            throw err;
        }

        return data;
    }
}

export default new ApiService();
