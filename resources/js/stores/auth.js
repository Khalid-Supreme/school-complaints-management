import { defineStore } from 'pinia';
import authService from '../services/authService';
import axios from '../bootstrap';

// Attach token to all axios requests if present
const token = localStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

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
        hasAnyRole: (state) => (roleSlugs = []) => {
            if (!state.user) return false;
            return roleSlugs.includes(state.user?.role?.slug);
        },
        isAdmin: (state) => state.user?.role?.slug === 'admin',
        isSubAdmin: (state) => state.user?.role?.slug === 'sub_admin',
        isAdministrator: (state) => ['admin', 'sub_admin'].includes(state.user?.role?.slug),
        isComplainant: (state) => ['student', 'staff'].includes(state.user?.role?.slug),
        isOfficer: (state) => state.user?.role?.slug === 'complaint_officer',
        isSecurity: (state) => state.user?.role?.slug === 'security',
        canAccessComplaintBackend: (state) => ['admin', 'sub_admin', 'complaint_officer'].includes(state.user?.role?.slug),
        canManageUsers: (state) => state.user?.role?.slug === 'admin',
        canManageComplaints: (state) => ['admin', 'sub_admin'].includes(state.user?.role?.slug),
        canManageStaff: (state) => state.user?.role?.slug === 'admin',
        canAssignRoles: (state) => state.user?.role?.slug === 'admin',
        canManageSettings: (state) => ['admin', 'sub_admin'].includes(state.user?.role?.slug),
        canAccessAdminDashboard: (state) => ['admin', 'sub_admin'].includes(state.user?.role?.slug),
        canAccessSecurityDashboard: (state) => ['admin', 'sub_admin', 'security'].includes(state.user?.role?.slug),
        canViewAllUsers: (state) => ['admin', 'sub_admin'].includes(state.user?.role?.slug),
        canCreateStaff: (state) => state.user?.role?.slug === 'admin',
        canPromoteToSubAdmin: (state) => state.user?.role?.slug === 'admin',
        canDemoteSubAdmin: (state) => state.user?.role?.slug === 'admin',
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
                    axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
                }
                this.initialized = true;
                return true;
            } catch (err) {
                this.error = err.response?.data?.errors?.username?.[0] 
                    || err.response?.data?.message 
                    || 'Login failed';
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
            delete axios.defaults.headers.common['Authorization'];
        },

        async forgotPassword(email) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authService.forgotPassword(email);
                return response.data.message;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to send reset link';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async resetPassword(payload) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authService.resetPassword(payload);
                return response.data.message;
            } catch (err) {
                this.error = err.response?.data?.errors?.email?.[0] 
                    || err.response?.data?.message 
                    || 'Failed to reset password';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async resendVerification(email) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authService.resendVerification(email);
                return response.data.message;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to resend verification email';
                return false;
            } finally {
                this.loading = false;
            }
        }
    }
});
