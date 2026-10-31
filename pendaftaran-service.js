// src/services/pendaftaranService.js
import apiService from './apiService';

class PendaftaranService {
    // Daftar berobat
    async daftarBerobat(data) {
        return apiService.post('/daftar-berobat', data);
    }

    // Verifikasi pendaftaran (Admin)
    async verifikasi(id, data = {}) {
        return apiService.post(`${process.env.REACT_APP_API_PENDAFTARAN_VERIFY_URI}/${id}/verifikasi`, data);
    }

    // Get by pasien (Admin)
    async getByPasien(pasienId) {
        return apiService.get(`${process.env.REACT_APP_API_PENDAFTARAN_BY_PASIEN_URI}/${pasienId}`);
    }

    // Get by dokter (Admin)
    async getByDokter(dokterId) {
        return apiService.get(`${process.env.REACT_APP_API_PENDAFTARAN_BY_DOKTER_URI}/${dokterId}`);
    }
}

export default new PendaftaranService();
