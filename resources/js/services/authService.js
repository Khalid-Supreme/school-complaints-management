import api, { initializeCsrf } from './api';

export default {
    async login(credentials) {
        await initializeCsrf();
        return api.post('/api/login', credentials);
    },
    
    async logout() {
        return api.post('/api/logout');
    },

    async getUser() {
        return api.get('/api/user');
    }
};
