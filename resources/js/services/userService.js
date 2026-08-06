import api from './api';

export default {
    async getStudents(params = {}) {
        const query = new URLSearchParams();
        if (params.page) query.set('page', params.page);
        if (params.search) query.set('search', params.search);
        if (params.department_id) query.set('department_id', params.department_id);
        if (params.gender) query.set('gender', params.gender);
        if (params.status) query.set('status', params.status);
        const qs = query.toString();
        return api.get(`/api/admin/users/students${qs ? '?' + qs : ''}`);
    },

    async getStaff(params = {}) {
        const query = new URLSearchParams();
        if (params.page) query.set('page', params.page);
        if (params.search) query.set('search', params.search);
        if (params.department_id) query.set('department_id', params.department_id);
        if (params.role) query.set('role', params.role);
        if (params.status) query.set('status', params.status);
        const qs = query.toString();
        return api.get(`/api/admin/users/staff${qs ? '?' + qs : ''}`);
    },

    async create(payload) {
        return api.post('/api/admin/users', payload);
    },

    async update(id, payload) {
        return api.put(`/api/admin/users/${id}`, payload);
    },

    async get(id) {
        return api.get(`/api/admin/users/${id}`);
    },

    async destroy(id) {
        return api.delete(`/api/admin/users/${id}`);
    },

    async updateRole(id, role) {
        return api.patch(`/api/admin/users/${id}/role`, { role });
    },

    async resetPassword(id, password) {
        return api.post(`/api/admin/users/${id}/reset-password`, { password });
    },
};
