// src/services/jadwalService.js
import apiService from './apiService';

class JadwalService {
    // CRUD Jadwal
    async getAll(params = {}) {
        return apiService.get(process.env.REACT_APP_API_JADWAL_URI, params);
    }

    async getById(id) {
        return apiService.get(`${process.env.REACT_APP_API_JADWAL_URI}/${id}`);
    }

    async create(data) {
        return apiService.post(process.env.REACT_APP_API_JADWAL_URI, data);
    }

    async update(id, data) {
        return apiService.put(`${process.env.REACT_APP_API_JADWAL_URI}/${id}`, data);
    }

    async delete(id) {
        return apiService.delete(`${process.env.REACT_APP_API_JADWAL_URI}/${id}`);
    }

    // Get jadwal by dokter
    async getByDokter(dokterId) {
        return apiService.get(`${process.env.REACT_APP_API_JADWAL_BY_DOKTER_URI}/${dokterId}`);
    }
}

export default new JadwalService();
