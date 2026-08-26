import { defineStore } from 'pinia';
import complaintService from '../services/complaintService';
import api from '../services/api';
import { getSecurityMessage, getThrottleMessage } from '../utils/securityMessages';

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
        statusFilter: null,
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
                const params = this.statusFilter ? { status: this.statusFilter } : {};
                const response = await complaintService.getComplaints(page, params);
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
                const code = err.response?.data?.code;
                const retry = err.response?.headers?.['retry-after'] || err.response?.data?.retry_after;
                if (err.__isSecurityBlock) {
                    const mapped = (code ? getSecurityMessage(code, retry) : null) || getThrottleMessage(retry);
                    this.error = mapped ? mapped.detail : (err.response?.data?.error || err.response?.data?.message || 'Failed to submit complaint');
                } else {
                    this.error = err.response?.data?.message
                        || err.response?.data?.error
                        || Object.values(err.response?.data?.errors || {}).flat()[0]
                        || 'Failed to submit complaint';
                }
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
