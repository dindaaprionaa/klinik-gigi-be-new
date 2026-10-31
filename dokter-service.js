// src/services/dokterService.js
import apiService from './apiService';

class DokterService {
    // CRUD Dokter
    async getAll(params = {}) {
        return apiService.get(process.env.REACT_APP_API_DOKTER_URI, params);
    }

    async getById(id) {
        return apiService.get(`${process.env.REACT_APP_API_DOKTER_URI}/${id}`);
    }

    async create(data) {
        return apiService.post(process.env.REACT_APP_API_DOKTER_URI, data);
    }

    async update(id, data) {
        return apiService.put(`${process.env.REACT_APP_API_DOKTER_URI}/${id}`, data);
    }

    async delete(id) {
        return apiService.delete(`${process.env.REACT_APP_API_DOKTER_URI}/${id}`);
    }

    // Spesialisasi
    async getBySpesialisasi(spesialisasi) {
        return apiService.get(`${process.env.REACT_APP_API_DOKTER_SPESIALISASI_URI}/${spesialisasi}`);
    }
}

export default new DokterService();
