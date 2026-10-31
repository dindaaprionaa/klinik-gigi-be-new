// src/services/authService.js
import apiService from './apiService';

class AuthService {
    async login(email, password) {
        try {
            const response = await apiService.post(
                process.env.REACT_APP_API_LOGIN_URI,
                { email, password }
            );

            if (response.token) {
                localStorage.setItem('token', response.token);
                localStorage.setItem('user', JSON.stringify(response.user));
            }

            return response;
        } catch (error) {
            throw error;
        }
    }

    async register(userData) {
        try {
            const response = await apiService.post(
                process.env.REACT_APP_API_REGISTER_URI,
                userData
            );

            if (response.token) {
                localStorage.setItem('token', response.token);
                localStorage.setItem('user', JSON.stringify(response.user));
            }

            return response;
        } catch (error) {
            throw error;
        }
    }

    async getCurrentUser() {
        try {
            const response = await apiService.get(process.env.REACT_APP_API_ME_URI);
            return response.user;
        } catch (error) {
            this.logout();
            throw error;
        }
    }

    async getProfile() {
        try {
            const response = await apiService.get(process.env.REACT_APP_API_PROFILE_URI);
            return response.user;
        } catch (error) {
            throw error;
        }
    }

    async updateProfile(profileData) {
        try {
            const response = await apiService.put(
                process.env.REACT_APP_API_PROFILE_URI,
                profileData
            );
            return response;
        } catch (error) {
            throw error;
        }
    }

    async logout() {
        try {
            await apiService.post(process.env.REACT_APP_API_LOGOUT_URI);
        } catch (error) {
            // Ignore logout errors
        } finally {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        }
    }

    getStoredUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    getToken() {
        return localStorage.getItem('token');
    }

    isAuthenticated() {
        return !!this.getToken();
    }
}

export default new AuthService();
