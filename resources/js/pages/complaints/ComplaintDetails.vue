<template>
    <div class="max-w-6xl mx-auto space-y-8">
        <div class="flex items-center gap-3">
            <Button icon="pi pi-arrow-left" text rounded class="!text-slate-500 hover:!bg-sage-50"
                @click="$router.back()" />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-3xl font-semibold text-charcoal tracking-tight">Complaint Details</h1>
                    <span class="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">{{
                        complaint?.reference_no }}</span>
                    <StatusBadge v-if="complaint?.status" :status="complaint.status" />
                    <Tag v-if="complaint?.priority" :value="complaint?.priority"
                        :severity="prioritySeverity(complaint?.priority)" class="capitalize text-xs font-bold" />
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Category: <strong class="text-charcoal">{{ complaint?.category }}</strong>
                    <span class="mx-2 text-slate-300">•</span>
                    Submitted: {{ submittedDate }}
                </p>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-20">
            <i class="pi pi-spin pi-spinner text-3xl text-sage-600"></i>
        </div>

        <div v-else-if="!complaint" class="text-center py-20 text-slate-500">
            <i class="pi pi-exclamation-circle text-4xl block mb-3 text-sage-500"></i>
            <p class="font-semibold text-charcoal">Complaint not found or access denied.</p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <Card class="shadow-card border border-sage-100">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                            <i class="pi pi-lock text-sage-600"></i>
                            <span class="text-charcoal">Decrypted Complaint Content</span>
                            <span
                                class="ml-auto text-xs font-semibold text-sage-700 bg-sage-50 border border-sage-100 px-2 py-0.5 rounded-full">AES-256
                                Decrypted</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-5 text-charcoal">
                            <div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-[0.18em] mb-2">Subject</p>
                                <p class="text-xl font-semibold text-charcoal leading-tight">{{ complaint.title }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-[0.18em] mb-2">Description
                                </p>
                                <div
                                    class="bg-slate-50 border border-sage-100 rounded-xl p-5 text-sm text-slate-700 leading-7 whitespace-pre-wrap">
                                    {{ complaint.description }}</div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-card border border-sage-100">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                            <i class="pi pi-paperclip text-sage-500"></i>
                            <span class="text-charcoal">Attached Evidence ({{ attachments.length }})</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="attachments.length === 0" class="text-center py-8 text-slate-400 text-sm">
                            <i class="pi pi-folder-open text-2xl block mb-2 text-sage-400"></i>
                            No attachments uploaded.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="att in attachments" :key="att.id"
                                class="flex items-center gap-3 p-3 rounded-xl border border-sage-100 bg-white hover:bg-sage-50 transition-colors">
                                <div
                                    class="w-10 h-10 rounded-lg bg-sage-50 flex items-center justify-center text-sage-600 shrink-0">
                                    <i :class="`pi ${fileIcon(att.mime_type)} text-sm`"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-charcoal truncate">{{ att.original_filename }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ (att.file_size / 1024).toFixed(1) }} KB • {{
                                        att.mime_type }}</p>
                                </div>
                                <Button icon="pi pi-download" label="Download" size="small" text
                                    :loading="downloadingId === att.id" class="!text-sage-600 hover:!bg-sage-50"
                                    @click="downloadAttachment(att)" />
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-card border border-sage-100">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                            <i class="pi pi-comments text-indigo-500"></i>
                            <span class="text-charcoal">Secure Message Thread</span>
                        </div>
                    </template>
                    <template #content>
                        <div ref="messagesContainer" class="space-y-4 max-h-96 overflow-y-auto pr-1 mb-4">
                            <div v-if="messages.length === 0" class="text-center py-10 text-slate-400 text-sm">
                                <i class="pi pi-comments text-2xl block mb-2 text-sage-400"></i>
                                No messages yet. Start the conversation.
                            </div>
                            <div v-for="msg in messages" :key="msg.id" class="flex gap-3"
                                :class="msg.user_id === authStore.user?.id ? 'flex-row-reverse' : 'flex-row'">
                                <div
                                    class="w-9 h-9 rounded-full bg-sage-50 border border-sage-100 flex items-center justify-center text-sage-700 font-bold text-xs shrink-0">
                                    {{ msg.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                                </div>
                                <div class="flex flex-col gap-1 max-w-[78%]"
                                    :class="msg.user_id === authStore.user?.id ? 'items-end' : 'items-start'">
                                    <span class="text-xs text-slate-500 font-medium">{{ msg.user?.name }} • {{ new
                                        Date(msg.created_at).toLocaleTimeString([], {
                                            hour: '2-digit', minute: '2-digit'
                                        }) }}</span>
                                    <div class="rounded-2xl px-4 py-3 text-sm leading-6 border"
                                        :class="msg.user_id === authStore.user?.id ? 'bg-sage-600 text-white border-sage-600' : 'bg-slate-50 text-slate-700 border-sage-100'">
                                        {{ msg.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-4 border-t border-sage-100">
                            <InputText v-model="newMessage" placeholder="Type a secure message..." class="flex-1" @keyup.enter="sendMessage" />
                            <Button icon="pi pi-send" :loading="sendingMessage"
                                class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white" @click="sendMessage" />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="space-y-6">
                <Card v-if="authStore.isAdmin || authStore.isOfficer"
                    class="shadow-card border border-sage-100 bg-white">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                            <i class="pi pi-info-circle text-sage-600"></i>
                            <span class="text-charcoal">Current Assignment</span>
                        </div>
                    </template>
                    <template #content>
                        <div v-if="currentAssignment" class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Assigned To</span>
                                <span class="font-semibold text-charcoal text-right">{{ currentAssignment.assigned_to
                                    }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Assigned By</span>
                                <span class="font-semibold text-charcoal text-right">{{ currentAssignment.assigned_by
                                    }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Assigned At</span>
                                <span class="font-semibold text-charcoal text-right">{{ new
                                    Date(currentAssignment.assigned_at).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="currentAssignment.assignment_note"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 mb-2">Assignment
                                    Note</p>
                                <p class="text-sm leading-6 text-slate-700 whitespace-pre-wrap">{{
                                    currentAssignment.assignment_note }}</p>
                            </div>
                        </div>
                        <div v-else class="text-sm text-slate-400 py-2">
                            No active assignment.
                        </div>
                    </template>
                </Card>

                <Card v-if="authStore.isOfficer" class="shadow-card border border-sage-100 bg-white">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                            <i class="pi pi-pencil text-purple-600"></i>
                            <span class="text-charcoal">Update Resolution Status</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <p class="text-xs text-slate-500">Current:
                                <StatusBadge :status="complaint.status" />
                            </p>
                            <Select v-model="newStatus" :options="officerStatusOptions" optionLabel="label" optionValue="value" placeholder="Select new status" class="w-full" />
                            <Textarea v-model="resolutionNote" placeholder="Add a resolution note or update..." rows="4"
                                class="w-full" />
                            <Button label="Update Status" icon="pi pi-check" :loading="updatingStatus"
                                class="w-full !bg-purple-600 hover:!bg-purple-700 !border-none !text-white !font-semibold"
                                @click="updateStatus" />
                        </div>
                    </template>
                </Card>

                <Card v-if="authStore.hasRole('admin')" class="shadow-card border border-sage-100 bg-white">
                    <template #title>
                        <div class="flex items-center gap-2 text-base font-semibold pb-4 mb-4 border-b border-sage-100">
                            <i class="pi pi-user-plus text-sage-600"></i>
                            <span class="text-charcoal">Assign to Officer</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <p v-if="complaint.currentAssignment" class="text-xs text-slate-500">
                                Currently assigned to:
                                <strong class="text-charcoal">{{ complaint.currentAssignment?.assigned_to?.name
                                }}</strong>
                            </p>
                            <div v-if="currentAssignment?.assignment_note"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 mb-2">Assignment
                                    Note</p>
                                <p class="text-sm leading-6 text-slate-700 whitespace-pre-wrap">{{
                                    currentAssignment.assignment_note }}</p>
                            </div>
                            <Select v-model="assignTo" :options="staffList" optionLabel="name" optionValue="id" placeholder="Select an officer" class="w-full">
                                <template #option="slotProps">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-charcoal">{{ slotProps.option.name }}</span>
                                        <span class="text-slate-400 text-xs">({{ slotProps.option.department || 'N/A'
                                        }})</span>
                                    </div>
                                </template>
                            </Select>
                            <Textarea v-model="assignNote" placeholder="Assignment instructions (optional)..." rows="3"
                                class="w-full" />
                            <Button label="Assign Complaint" icon="pi pi-send" :loading="assigning"
                                class="w-full !bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold"
                                @click="assignComplaint" />
                        </div>
                    </template>
                </Card>

                <Card class="shadow-card border border-sage-100">
                    <template #title>
                        <div class="text-sm font-semibold pb-4 mb-4 border-b border-sage-100 text-charcoal">Complaint
                            Information</div>
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Complainant</span>
                                <span class="font-semibold text-charcoal text-right">{{ complaint.complainant?.name ||
                                    'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Department</span>
                                <span class="font-semibold text-charcoal text-right">{{
                                    complaint.complainant?.department || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Department</span>
                                <span class="font-semibold text-charcoal text-right">{{
                                    complaint.complainant?.email || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-slate-500">Institution ID</span>
                                <span class="font-semibold text-charcoal text-right capitalize">{{
                                    complaint.complainant?.institution_id || 'N/A' }}</span>
                            </div>
                            <div v-if="complaint.submitted_at" class="flex justify-between gap-4">
                                <span class="text-slate-500">Submitted On</span>
                                <span class="font-semibold text-charcoal text-right">{{ new
                                    Date(complaint.submitted_at).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="complaint.assigned_at" class="flex justify-between gap-4">
                                <span class="text-slate-500">Assigned At</span>
                                <span class="font-semibold text-charcoal text-right">{{ new
                                    Date(complaint.assigned_at).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="complaint.resolved_at" class="flex justify-between gap-4">
                                <span class="text-slate-500">Resolved At</span>
                                <span class="font-semibold text-emerald-700 text-right">{{ new
                                    Date(complaint.resolved_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card v-if="authStore.isAdmin || authStore.isOfficer" class="shadow-card border border-sage-100">
                    <template #title>
                        <div class="text-sm font-semibold pb-4 mb-4 border-b border-sage-100 text-charcoal">Assignment
                            History</div>
                    </template>
                    <template #content>
                        <div v-if="assignmentHistory.length === 0" class="text-center text-xs text-slate-400 py-3">No
                            assignment history.</div>
                        <div v-else class="space-y-2">
                            <div v-for="h in assignmentHistory" :key="h.id" class="flex items-start gap-2 text-xs">
                                <div class="w-1.5 h-1.5 rounded-full bg-sage-400 mt-1.5 shrink-0"></div>
                                <div class="space-y-1">
                                    <span class="font-semibold text-charcoal">{{ h.assigned_to }}</span>
                                    <span class="text-slate-400"> by {{ h.assigned_by }} • {{ new
                                        Date(h.assigned_at).toLocaleDateString() }}</span>
                                    <span v-if="h.is_current" class="ml-1 text-sage-700 font-bold">(Current)</span>
                                    <div v-if="h.assignment_note"
                                        class="mt-1 rounded-lg border border-sage-100 bg-slate-50 px-3 py-2 text-slate-600 whitespace-pre-wrap leading-5">
                                        {{ h.assignment_note }}
                                    </div>
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
const currentAssignment = computed(() => assignmentHistory.value.find((item) => item.is_current) || null);

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
