// src/services/laporanService.js
import apiService from './apiService';

class LaporanService {
    // General laporan
    async getLaporan() {
        return apiService.get(process.env.REACT_APP_API_LAPORAN_URI);
    }

    // Specific laporan
    async getLaporanPasien() {
        return apiService.get(process.env.REACT_APP_API_LAPORAN_PASIEN_URI);
    }

    async getLaporanRekamMedis() {
        return apiService.get(process.env.REACT_APP_API_LAPORAN_REKAM_MEDIS_URI);
    }

    async getLaporanDokter() {
        return apiService.get(process.env.REACT_APP_API_LAPORAN_DOKTER_URI);
    }

    async getLaporanPendaftaran() {
        return apiService.get(process.env.REACT_APP_API_LAPORAN_PENDAFTARAN_URI);
    }

    // Export
    async exportPDF(data = {}) {
        return apiService.post(process.env.REACT_APP_API_LAPORAN_EXPORT_PDF_URI, data);
    }

    async exportExcel(data = {}) {
        return apiService.post(process.env.REACT_APP_API_LAPORAN_EXPORT_EXCEL_URI, data);
    }
}

export default new LaporanService();
