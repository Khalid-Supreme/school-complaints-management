import api from './api';

export default {
    async getCategories() {
        return api.get('/api/categories');
    },
    
    async getComplaints(page = 1) {
        return api.get(`/api/complaints?page=${page}`);
    },

    async getComplaint(id) {
        return api.get(`/api/complaints/${id}`);
    },

    async submitComplaint(data) {
        return api.post('/api/complaints', data);
    }
};
