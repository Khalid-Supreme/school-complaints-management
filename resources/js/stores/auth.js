import { defineStore } from 'pinia';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        initialized: false,
        loading: false,
        error: null,
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.user,
        role: (state) => state.user?.role?.slug ?? null,
        hasRole: (state) => (roleSlug) => state.user?.role?.slug === roleSlug,
    },
    
    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authService.login(credentials);
                this.user = response.data.user;
                if (response.data.token) {
                    localStorage.setItem('auth_token', response.data.token);
                }
                this.initialized = true;
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Login failed';
                return false;
            } finally {
                this.loading = false;
            }
        },
        
        async fetchUser() {
            try {
                const response = await authService.getUser();
                this.user = response.data;
                this.initialized = true;
                return this.user;
            } catch (err) {
                this.clearUser();
            }
        },
        
        async logout() {
            try {
                await authService.logout();
            } catch (e) {
                // Ignore errors on logout
            } finally {
                this.clearUser();
            }
        },
        
        clearUser() {
            this.user = null;
            this.initialized = true;
            localStorage.removeItem('auth_token');
        }
    }
});
