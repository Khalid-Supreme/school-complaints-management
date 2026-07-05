import { defineStore } from 'pinia';
import complaintService from '../services/complaintService';
import api from '../services/api';

export const useComplaintsStore = defineStore('complaints', {
    state: () => ({
        complaints: [],
        categories: [],
        assignments: [],
        staffList: [],
        meta: null,
        assignmentMeta: null,
        loading: false,
        error: null,
        lastComplaintId: null,
        lastReferenceNo: null,
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
            this.lastComplaintId = null;
            this.lastReferenceNo = null;
            try {
                const response = await complaintService.submitComplaint(data);
                this.lastComplaintId = response.data.complaint?.id;
                this.lastReferenceNo = response.data.complaint?.reference_no;
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to submit complaint';
                return false;
            } finally {
                this.loading = false;
            }
        },

        async fetchStaffAssignments(page = 1) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get(`/api/staff/assignments?page=${page}`);
                this.assignments = response.data.data;
                this.assignmentMeta = response.data.meta;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch assignments';
            } finally {
                this.loading = false;
            }
        },

        async fetchOfficersList() {
            try {
                const response = await api.get('/api/staff/list');
                this.staffList = response.data.data;
            } catch (err) {
                console.error('Failed to fetch staff list', err);
            }
        },

        async assignComplaint(complaintId, assignedTo, note = null) {
            this.loading = true;
            this.error = null;
            try {
                await api.post(`/api/complaints/${complaintId}/assign`, {
                    assigned_to: assignedTo,
                    note: note,
                });
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to assign complaint';
                return false;
            } finally {
                this.loading = false;
            }
        }
    }
});
