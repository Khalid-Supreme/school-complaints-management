import api, { initializeCsrf } from './api';
import { hashPassword } from '../utils/crypto';

export default {
    async login(credentials) {
        await initializeCsrf();
        return api.post('/api/login', {
            username: credentials.username,
            password: await hashPassword(credentials.password),
        });
    },

    async registerStudent(payload) {
        await initializeCsrf();
        return api.post('/api/register/student', {
            ...payload,
            password: payload.password,
            confirm_password: payload.confirm_password,
        });
    },

    async registerStaff(payload) {
        await initializeCsrf();
        return api.post('/api/register/staff', {
            ...payload,
            password: payload.password,
            confirm_password: payload.confirm_password,
        });
    },

    async resendVerification(identifier) {
        await initializeCsrf();
        return api.post('/api/register/resend', { identifier });
    },

    async forgotPassword(email) {
        await initializeCsrf();
        return api.post('/api/forgot-password', { email });
    },

    async resetPassword(payload) {
        await initializeCsrf();
        return api.post('/api/reset-password', payload);
    },

    async changePassword(payload) {
        await initializeCsrf();
        const body = {
            password: payload.password,
            password_confirmation: payload.password_confirmation,
        };
        // Forced change (must_change_password) does not re-collect the current
        // password — the user already authenticated with it this session.
        if (payload.current_password) {
            body.current_password = payload.current_password;
        }
        return api.post('/api/change-password', body);
    },

    async verifyPasswordChange(code) {
        await initializeCsrf();
        return api.post('/api/password-change/verify', { code });
    },

    async resendPasswordChange() {
        await initializeCsrf();
        return api.post('/api/password-change/verify/resend');
    },

    async getPasswordChangeStatus() {
        return api.get('/api/password-change/status');
    },

    async logout() {
        return api.post('/api/logout');
    },

    async getUser() {
        return api.get('/api/user');
    }
};
