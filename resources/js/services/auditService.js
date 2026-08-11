import api from './api';

export default {
    async getLogs(params = {}) {
        const query = new URLSearchParams();
        if (params.page) query.set('page', params.page);
        if (params.action) query.set('action', params.action);
        if (params.search) query.set('search', params.search);
        if (params.ip_address) query.set('ip_address', params.ip_address);
        if (params.date_from) query.set('date_from', params.date_from);
        if (params.date_to) query.set('date_to', params.date_to);
        if (params.per_page) query.set('per_page', params.per_page);
        const qs = query.toString();
        return api.get(`/api/admin/audit/logs${qs ? '?' + qs : ''}`);
    },
};