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
            password: await hashPassword(payload.password),
            confirm_password: await hashPassword(payload.confirm_password),
        });
    },

    async registerStaff(payload) {
        await initializeCsrf();
        return api.post('/api/register/staff', {
            ...payload,
            password: await hashPassword(payload.password),
            confirm_password: await hashPassword(payload.confirm_password),
        });
    },

    async resendVerification(email) {
        await initializeCsrf();
        return api.post('/api/register/resend', { email });
    },

    async forgotPassword(email) {
        await initializeCsrf();
        return api.post('/api/forgot-password', { email });
    },

    async resetPassword(payload) {
        await initializeCsrf();
        return api.post('/api/reset-password', payload);
    },

    async logout() {
        return api.post('/api/logout');
    },

    async getUser() {
        return api.get('/api/user');
    }
};
