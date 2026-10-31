// src/services/dashboardService.js
import apiService from './apiService';

class DashboardService {
    // Admin Dashboard
    async getDashboard() {
        return apiService.get(process.env.REACT_APP_API_DASHBOARD_URI);
    }

    async getStatistikPasien() {
        return apiService.get(process.env.REACT_APP_API_DASHBOARD_STATISTIK_PASIEN_URI);
    }

    async getStatistikDokter() {
        return apiService.get(process.env.REACT_APP_API_DASHBOARD_STATISTIK_DOKTER_URI);
    }

    async getStatistikPendaftaran() {
        return apiService.get(process.env.REACT_APP_API_DASHBOARD_STATISTIK_PENDAFTARAN_URI);
    }

    async getPendaftaranTerbaru() {
        return apiService.get(process.env.REACT_APP_API_DASHBOARD_PENDAFTARAN_TERBARU_URI);
    }

    async getAktivitasHariIni() {
        return apiService.get(process.env.REACT_APP_API_DASHBOARD_AKTIVITAS_HARI_INI_URI);
    }
}

export default new DashboardService();
