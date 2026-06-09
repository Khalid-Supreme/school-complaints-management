<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">All Complaints</h1>

        <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-lg shadow">
            <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

            <DataTable 
                :value="store.complaints" 
                :loading="store.loading"
                paginator 
                :rows="store.meta?.per_page || 15"
                :totalRecords="store.meta?.total || 0"
                lazy
                @page="onPage"
                responsiveLayout="scroll"
                :selection="selectedComplaint"
                selectionMode="single"
                @rowSelect="onRowSelect"
                dataKey="id"
            >
                <Column field="reference_no" header="Reference No"></Column>
                <Column field="category.name" header="Category"></Column>
                <Column field="title" header="Title"></Column>
                <Column field="priority" header="Priority">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.priority" :severity="prioritySeverity(slotProps.data.priority)" rounded />
                    </template>
                </Column>
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <StatusBadge :status="slotProps.data.status" />
                    </template>
                </Column>
                <Column field="submitted_at" header="Submitted">
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.submitted_at).toLocaleDateString() }}
                    </template>
                </Column>
                <Column header="Actions">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-user-plus" 
                            label="Assign" 
                            text 
                            rounded 
                            size="small"
                            @click="openAssignDialog(slotProps.data)" 
                        />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center p-4">No complaints found.</div>
                </template>
            </DataTable>
        </div>

        <!-- Assign Dialog -->
        <Dialog v-model:visible="assignDialogVisible" header="Assign Complaint" :style="{ width: '450px' }" modal>
            <div v-if="complaintToAssign" class="space-y-4">
                <p class="text-surface-600 dark:text-surface-400">
                    Assigning complaint <strong>{{ complaintToAssign.reference_no }}</strong>
                </p>

                <div class="field">
                    <label for="assignTo" class="block font-medium mb-2">Assign To</label>
                    <Dropdown 
                        id="assignTo"
                        v-model="assignForm.assigned_to" 
                        :options="store.staffList" 
                        optionLabel="name" 
                        optionValue="id" 
                        placeholder="Select Staff Member" 
                        class="w-full"
                    >
                        <template #option="slotProps">
                            <div>
                                <span class="font-medium">{{ slotProps.option.name }}</span>
                                <span class="text-surface-400 ml-2">({{ slotProps.option.department || 'N/A' }})</span>
                            </div>
                        </template>
                    </Dropdown>
                </div>

                <div class="field">
                    <label for="assignNote" class="block font-medium mb-2">Note (optional)</label>
                    <Textarea id="assignNote" v-model="assignForm.note" rows="3" class="w-full" placeholder="Add assignment instructions..." />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" severity="secondary" @click="assignDialogVisible = false" />
                <Button label="Assign" :loading="store.loading" @click="handleAssign" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useComplaintsStore } from '../../../stores/complaints';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import StatusBadge from '../../../components/StatusBadge.vue';

const store = useComplaintsStore();
const selectedComplaint = ref(null);
const assignDialogVisible = ref(false);
const complaintToAssign = ref(null);
const assignForm = reactive({ assigned_to: null, note: '' });

onMounted(() => {
    store.fetchComplaints(1);
    store.fetchStaffList();
});

const onPage = (event) => {
    store.fetchComplaints(event.page + 1);
};

const onRowSelect = (event) => {
    selectedComplaint.value = event.data;
};

const openAssignDialog = (complaint) => {
    complaintToAssign.value = complaint;
    assignForm.assigned_to = null;
    assignForm.note = '';
    assignDialogVisible.value = true;
};

const handleAssign = async () => {
    if (!assignForm.assigned_to) return;
    
    const success = await store.assignComplaint(
        complaintToAssign.value.id,
        assignForm.assigned_to,
        assignForm.note || null
    );
    
    if (success) {
        assignDialogVisible.value = false;
        store.fetchComplaints(store.meta?.current_page || 1);
    }
};

const prioritySeverity = (priority) => {
    switch (priority) {
        case 'low': return 'info';
        case 'medium': return 'warning';
        case 'high': return 'danger';
        case 'critical': return 'danger';
        default: return 'info';
    }
};
</script>
