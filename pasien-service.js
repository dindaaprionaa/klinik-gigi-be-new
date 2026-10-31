// src/services/pasienService.js
import apiService from './apiService';

class PasienService {
    // CRUD Pasien
    async getAll(params = {}) {
        return apiService.get(process.env.REACT_APP_API_PASIEN_URI, params);
    }

    async getById(id) {
        return apiService.get(`${process.env.REACT_APP_API_PASIEN_URI}/${id}`);
    }

    async create(data) {
        return apiService.post(process.env.REACT_APP_API_PASIEN_URI, data);
    }

    async update(id, data) {
        return apiService.put(`${process.env.REACT_APP_API_PASIEN_URI}/${id}`, data);
    }

    async delete(id) {
        return apiService.delete(`${process.env.REACT_APP_API_PASIEN_URI}/${id}`);
    }

    // Pasien-specific endpoints (/api/pasien/...)
    async getDashboard() {
        const uri = process.env.REACT_APP_API_PASIEN_DASHBOARD_URI || '/pasien/dashboard';
        return apiService.get(uri);
    }

    async getRiwayat(page = 1) {
        const uri = process.env.REACT_APP_API_RIWAYAT_URI || '/pasien/appointments';
        return apiService.get(uri, { page });
    }

    async getAntrian() {
        const uri = process.env.REACT_APP_API_ANTRIAN_URI || '/pasien/antrian';
        return apiService.get(uri);
    }

    async daftarBerobat(data) {
        const uri = process.env.REACT_APP_API_PASIEN_DAFTAR_URI || '/pasien/daftar';
        return apiService.post(uri, data);
    }

    async getDoktersForPasien() {
        const uri = process.env.REACT_APP_API_PASIEN_DOKTERS_URI || '/pasien/dokters';
        return apiService.get(uri);
    }

    async getJadwalByDokter(dokterId) {
        return apiService.get(`/pasien/dokter/${dokterId}/jadwal`);
    }

    async getPasienSaya() {
        const uri = process.env.REACT_APP_API_PASIEN_PROFILE_URI || '/pasien/profile';
        return apiService.get(uri);
    }
}

export default new PasienService();
