<template>
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Button icon="pi pi-arrow-left" text rounded class="text-surface-500 hover:bg-surface-100" @click="$router.back()" />
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-extrabold text-surface-900 dark:text-surface-0">Complaint Details</h1>
                    <span class="font-mono text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">{{ complaint?.reference_no }}</span>
                    <StatusBadge v-if="complaint?.status" :status="complaint.status" />
                    <Tag :value="complaint?.priority" :severity="prioritySeverity(complaint?.priority)" class="capitalize text-xs font-bold" v-if="complaint?.priority" />
                </div>
                <p class="text-xs text-surface-400 mt-1">Category: <strong class="text-surface-600 dark:text-surface-300">{{ complaint?.category?.name }}</strong> &bull; Submitted: {{ submittedDate }}</p>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-20">
            <i class="pi pi-spin pi-spinner text-3xl text-blue-600"></i>
        </div>

        <div v-else-if="!complaint" class="text-center py-20 text-surface-500">
            <i class="pi pi-exclamation-circle text-4xl block mb-3"></i>
            <p class="font-semibold">Complaint not found or access denied.</p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Complaint Body -->
                <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-surface-100 dark:border-surface-800">
                            <i class="pi pi-lock text-blue-600"></i>
                            <span>Decrypted Complaint Content</span>
                            <span class="ml-auto text-xs font-normal text-green-600 bg-green-50 dark:bg-green-950/30 border border-green-200/30 px-2 py-0.5 rounded-full">AES-256 Decrypted</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-1">Subject</p>
                                <p class="text-surface-900 dark:text-surface-0 font-semibold text-lg">{{ complaint.title }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-1">Description</p>
                                <div class="bg-surface-50 dark:bg-surface-900 rounded-lg p-4 border border-surface-100 dark:border-surface-800 text-sm text-surface-700 dark:text-surface-300 leading-relaxed whitespace-pre-wrap">{{ complaint.description }}</div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Attachments -->
                <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-surface-100 dark:border-surface-800">
                            <i class="pi pi-paperclip text-slate-500"></i>
                            <span>Attached Evidence ({{ attachments.length }})</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="attachments.length === 0" class="text-center py-6 text-surface-400 text-sm">
                            <i class="pi pi-folder-open text-2xl block mb-2"></i>
                            No attachments uploaded.
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="att in attachments" :key="att.id" class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 dark:border-surface-700 hover:bg-surface-50 dark:hover:bg-surface-800 transition-colors">
                                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/30 flex items-center justify-center text-blue-600">
                                    <i :class="`pi ${fileIcon(att.mime_type)} text-sm`"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-surface-800 dark:text-surface-200 truncate">{{ att.original_filename }}</p>
                                    <p class="text-xs text-surface-400">{{ (att.file_size / 1024).toFixed(1) }} KB &bull; {{ att.mime_type }}</p>
                                </div>
                                <Button
                                    icon="pi pi-download"
                                    label="Download"
                                    size="small"
                                    text
                                    :loading="downloadingId === att.id"
                                    class="text-blue-600 hover:bg-blue-50"
                                    @click="downloadAttachment(att)"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Messages/Chat Thread -->
                <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-surface-100 dark:border-surface-800">
                            <i class="pi pi-comments text-indigo-500"></i>
                            <span>Secure Message Thread</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3 max-h-80 overflow-y-auto mb-4 pr-1" ref="messagesContainer">
                            <div v-if="messages.length === 0" class="text-center py-6 text-surface-400 text-sm">
                                <i class="pi pi-comments text-2xl block mb-2"></i>
                                No messages yet. Start the conversation.
                            </div>
                            <div v-for="msg in messages" :key="msg.id"
                                class="flex gap-3"
                                :class="msg.user_id === authStore.user?.id ? 'flex-row-reverse' : 'flex-row'"
                            >
                                <div class="w-8 h-8 rounded-full bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-500 font-bold text-xs shrink-0">
                                    {{ msg.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                                </div>
                                <div :class="msg.user_id === authStore.user?.id ? 'items-end' : 'items-start'" class="flex flex-col gap-1 max-w-[75%]">
                                    <span class="text-xs text-surface-400 font-medium">{{ msg.user?.name }} &bull; {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                                    <div :class="msg.user_id === authStore.user?.id ? 'bg-indigo-600 text-white' : 'bg-surface-100 dark:bg-surface-800 text-surface-800 dark:text-surface-200'" class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed">
                                        {{ msg.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-3 border-t border-surface-100 dark:border-surface-800">
                            <InputText v-model="newMessage" placeholder="Type a secure message..." class="flex-1" @keyup.enter="sendMessage" />
                            <Button icon="pi pi-send" :loading="sendingMessage" class="bg-indigo-600 hover:bg-indigo-700 border-none text-white" @click="sendMessage" />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Sidebar Column -->
            <div class="space-y-6">
                <!-- Officer Actions (only for complaint_officer role) -->
                <Card v-if="authStore.isOfficer" class="border border-purple-200 dark:border-purple-800 shadow-sm bg-purple-50/30 dark:bg-purple-950/10">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-purple-100 dark:border-purple-900">
                            <i class="pi pi-pencil text-purple-600"></i>
                            <span>Update Resolution Status</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <p class="text-xs text-surface-500">Current: <StatusBadge :status="complaint.status" /></p>
                            <Select v-model="newStatus" :options="officerStatusOptions" optionLabel="label" optionValue="value" placeholder="Select new status" class="w-full" />
                            <Textarea v-model="resolutionNote" placeholder="Add a resolution note or update..." rows="3" class="w-full" />
                            <Button label="Update Status" icon="pi pi-check" :loading="updatingStatus" class="w-full bg-purple-600 hover:bg-purple-700 border-none text-white font-bold" @click="updateStatus" />
                        </div>
                    </template>
                </Card>

                <!-- Admin Actions (assignment) -->
                <Card v-if="authStore.hasRole('admin')" class="border border-emerald-200 dark:border-emerald-800 shadow-sm bg-emerald-50/30 dark:bg-emerald-950/10">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-emerald-100 dark:border-emerald-900">
                            <i class="pi pi-user-plus text-emerald-600"></i>
                            <span>Assign to Officer</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <p class="text-xs text-surface-500" v-if="complaint.currentAssignment">
                                Currently assigned to: <strong class="text-surface-700 dark:text-surface-300">{{ complaint.currentAssignment?.assigned_to?.name }}</strong>
                            </p>
                            <Select v-model="assignTo" :options="staffList" optionLabel="name" optionValue="id" placeholder="Select an officer" class="w-full">
                                <template #option="slotProps">
                                    <div>
                                        <span class="font-medium">{{ slotProps.option.name }}</span>
                                        <span class="text-surface-400 ml-2 text-xs">({{ slotProps.option.department || 'N/A' }})</span>
                                    </div>
                                </template>
                            </Select>
                            <Textarea v-model="assignNote" placeholder="Assignment instructions (optional)..." rows="2" class="w-full" />
                            <Button label="Assign Complaint" icon="pi pi-send" :loading="assigning" class="w-full bg-emerald-600 hover:bg-emerald-700 border-none text-white font-bold" @click="assignComplaint" />
                        </div>
                    </template>
                </Card>

                <!-- Complaint Meta -->
                <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                    <template #title>
                        <div class="text-sm font-bold pb-3 border-b border-surface-100 dark:border-surface-800">Complaint Information</div>
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-surface-500">Complainant</span>
                                <span class="font-semibold text-surface-800 dark:text-surface-200">{{ complaint.complainant?.name || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-surface-500">Department</span>
                                <span class="font-semibold text-surface-800 dark:text-surface-200">{{ complaint.complainant?.department || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-surface-500">Source</span>
                                <span class="font-semibold text-surface-800 dark:text-surface-200 capitalize">{{ complaint.source || 'Web' }}</span>
                            </div>
                            <div v-if="complaint.assigned_at" class="flex justify-between">
                                <span class="text-surface-500">Assigned At</span>
                                <span class="font-semibold text-surface-800 dark:text-surface-200">{{ new Date(complaint.assigned_at).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="complaint.resolved_at" class="flex justify-between">
                                <span class="text-surface-500">Resolved At</span>
                                <span class="font-semibold text-green-700 dark:text-green-400">{{ new Date(complaint.resolved_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Assignment History -->
                <Card class="border border-surface-200 dark:border-surface-800 shadow-sm" v-if="authStore.isAdmin || authStore.isOfficer">
                    <template #title>
                        <div class="text-sm font-bold pb-3 border-b border-surface-100 dark:border-surface-800">Assignment History</div>
                    </template>
                    <template #content>
                        <div v-if="assignmentHistory.length === 0" class="text-center text-xs text-surface-400 py-3">No assignment history.</div>
                        <div v-else class="space-y-2">
                            <div v-for="h in assignmentHistory" :key="h.id" class="flex items-start gap-2 text-xs">
                                <div class="w-1.5 h-1.5 rounded-full bg-surface-400 mt-1.5 shrink-0"></div>
                                <div>
                                    <span class="font-semibold text-surface-700 dark:text-surface-300">{{ h.assigned_to }}</span>
                                    <span class="text-surface-400"> by {{ h.assigned_by }} &bull; {{ new Date(h.assigned_at).toLocaleDateString() }}</span>
                                    <span v-if="h.is_current" class="ml-1 text-green-600 font-bold">(Current)</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useAuthStore } from '../../stores/auth';
import { useComplaintsStore } from '../../stores/complaints';
import api from '../../services/api';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import StatusBadge from '../../components/StatusBadge.vue';

const route = useRoute();
const toast = useToast();
const authStore = useAuthStore();
const complaintsStore = useComplaintsStore();

const complaintId = route.params.id;
const complaint = ref(null);
const messages = ref([]);
const attachments = ref([]);
const assignmentHistory = ref([]);
const staffList = ref([]);
const loading = ref(true);
const newMessage = ref('');
const sendingMessage = ref(false);
const messagesContainer = ref(null);
const downloadingId = ref(null);

// Officer actions
const newStatus = ref(null);
const resolutionNote = ref('');
const updatingStatus = ref(false);

// Admin actions
const assignTo = ref(null);
const assignNote = ref('');
const assigning = ref(false);

const officerStatusOptions = [
    { label: 'Under Review', value: 'under_review' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Resolved', value: 'resolved' },
    { label: 'Closed', value: 'closed' },
];

const submittedDate = computed(() => complaint.value?.submitted_at ? new Date(complaint.value.submitted_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : '');
const prioritySeverity = (p) => ({ high: 'danger', critical: 'danger', medium: 'warn', low: 'info' }[p] ?? 'secondary');
const fileIcon = (mime) => mime?.startsWith('image/') ? 'pi-image' : mime === 'application/pdf' ? 'pi-file-pdf' : 'pi-file';

const downloadAttachment = async (att) => {
    downloadingId.value = att.id;
    try {
        const res = await api.get(
            `/api/complaints/${complaintId}/attachments/${att.id}/download`,
            { responseType: 'blob' }
        );

        // Detect mime from response or fallback to attachment's mime_type
        const mime = res.data.type || att.mime_type || 'application/octet-stream';
        const blob = new Blob([res.data], { type: mime });
        const url = window.URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', att.original_filename || 'attachment');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (e) {
        toast.add({
            severity: 'error',
            summary: 'Download failed',
            detail: e.response?.data?.message || 'You may not have access to this file.',
            life: 4000
        });
    } finally {
        downloadingId.value = null;
    }
};

const loadComplaint = async () => {
    loading.value = true;
    try {
        const res = await api.get(`/api/complaints/${complaintId}`);
        complaint.value = res.data.data;
    } catch (e) {
        complaint.value = null;
    } finally {
        loading.value = false;
    }
};

const loadMessages = async () => {
    try {
        const res = await api.get(`/api/complaints/${complaintId}/messages`);
        messages.value = res.data.data?.data || res.data.data || [];
        await nextTick();
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    } catch (e) { console.warn(e); }
};

const loadAttachments = async () => {
    try {
        const res = await api.get(`/api/complaints/${complaintId}/attachments`);
        attachments.value = res.data.data || [];
    } catch (e) { console.warn(e); }
};

const loadAssignmentHistory = async () => {
    if (!authStore.isAdmin && !authStore.isOfficer) return;
    try {
        const res = await api.get(`/api/complaints/${complaintId}/assignments`);
        assignmentHistory.value = res.data.data || [];
    } catch (e) { console.warn(e); }
};

const loadStaffList = async () => {
    if (!authStore.isAdmin) return;
    try {
        const res = await api.get('/api/staff/list');
        staffList.value = res.data.data || [];
    } catch (e) { console.warn(e); }
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    sendingMessage.value = true;
    try {
        await api.post(`/api/complaints/${complaintId}/messages`, { message: newMessage.value });
        newMessage.value = '';
        await loadMessages();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Message could not be sent.', life: 3000 });
    } finally {
        sendingMessage.value = false;
    }
};

const updateStatus = async () => {
    if (!newStatus.value) { toast.add({ severity: 'warn', summary: 'Select Status', detail: 'Please select a new status.', life: 3000 }); return; }
    updatingStatus.value = true;
    try {
        await api.patch(`/api/complaints/${complaintId}/status`, { status: newStatus.value });
        if (resolutionNote.value.trim()) {
            await api.post(`/api/complaints/${complaintId}/messages`, { message: `[Status Update → ${newStatus.value}]: ${resolutionNote.value}` });
        }
        toast.add({ severity: 'success', summary: 'Status Updated', detail: `Complaint status changed to "${newStatus.value}".`, life: 4000 });
        await loadComplaint();
        await loadMessages();
        resolutionNote.value = '';
        newStatus.value = null;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Status update failed.', life: 4000 });
    } finally {
        updatingStatus.value = false;
    }
};

const assignComplaint = async () => {
    if (!assignTo.value) { toast.add({ severity: 'warn', summary: 'Select Officer', detail: 'Please select an officer.', life: 3000 }); return; }
    assigning.value = true;
    try {
        await api.post(`/api/complaints/${complaintId}/assign`, { assigned_to: assignTo.value, note: assignNote.value || null });
        toast.add({ severity: 'success', summary: 'Complaint Assigned', detail: 'The complaint has been assigned successfully.', life: 4000 });
        assignTo.value = null;
        assignNote.value = '';
        await loadComplaint();
        await loadAssignmentHistory();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Assignment failed.', life: 4000 });
    } finally {
        assigning.value = false;
    }
};

onMounted(async () => {
    await loadComplaint();
    await Promise.all([loadMessages(), loadAttachments(), loadAssignmentHistory(), loadStaffList()]);
});
</script>
