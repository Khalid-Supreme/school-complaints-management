import { defineStore } from 'pinia';
import complaintService from '../services/complaintService';

export const useComplaintsStore = defineStore('complaints', {
    state: () => ({
        complaints: [],
        categories: [],
        meta: null,
        loading: false,
        error: null,
    }),
    
    actions: {
        async fetchCategories() {
            try {
                const response = await complaintService.getCategories();
                this.categories = response.data.data;
            } catch (err) {
                console.error('Failed to fetch categories', err);
            }
        },

        async fetchComplaints(page = 1) {
            this.loading = true;
            this.error = null;
            try {
                const response = await complaintService.getComplaints(page);
                this.complaints = response.data.data;
                this.meta = response.data.meta;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch complaints';
            } finally {
                this.loading = false;
            }
        },

        async submitComplaint(data) {
            this.loading = true;
            this.error = null;
            try {
                await complaintService.submitComplaint(data);
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to submit complaint';
                return false;
            } finally {
                this.loading = false;
            }
        }
    }
});
