// src/services/rekamMedisService.js
import apiService from './apiService';

class RekamMedisService {
    // CRUD Rekam Medis
    async getAll(params = {}) {
        return apiService.get(process.env.REACT_APP_API_REKAM_MEDIS_URI, params);
    }

    async getById(id) {
        return apiService.get(`${process.env.REACT_APP_API_REKAM_MEDIS_URI}/${id}`);
    }

    async create(data) {
        return apiService.post(process.env.REACT_APP_API_REKAM_MEDIS_URI, data);
    }

    async update(id, data) {
        return apiService.put(`${process.env.REACT_APP_API_REKAM_MEDIS_URI}/${id}`, data);
    }

    async delete(id) {
        return apiService.delete(`${process.env.REACT_APP_API_REKAM_MEDIS_URI}/${id}`);
    }

    // Filter by pasien
    async getByPasien(pasienId) {
        return apiService.get(`${process.env.REACT_APP_API_REKAM_MEDIS_BY_PASIEN_URI}/${pasienId}`);
    }

    // Filter by dokter
    async getByDokter(dokterId) {
        return apiService.get(`${process.env.REACT_APP_API_REKAM_MEDIS_BY_DOKTER_URI}/${dokterId}`);
    }
}

export default new RekamMedisService();
