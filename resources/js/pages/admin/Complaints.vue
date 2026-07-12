<template>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-charcoal tracking-tight">Complaint Management</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">Overview of all complaints in the system</p>
            </div>
            <div class="flex gap-2">
                <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50"
                    :loading="store.loading" @click="store.fetchComplaints(store.meta?.current_page || 1)"
                    v-tooltip.bottom="'Refresh Data'" />
            </div>
        </div>

        <div class="card p-6">
            <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

            <DataTable :value="store.complaints"
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
                class="p-datatable-sm"
            >
                <Column field="reference_no" header="Ref No" style="width: 130px">
                    <template #body="slotProps">
                        <span class="font-mono text-xs font-medium text-slate-600 bg-sage-50 px-2 py-1 rounded-md">{{
                            slotProps.data.reference_no }}</span>
                    </template>
                </Column>
                <Column field="category" header="Category" />
                <Column field="title" header="Subject">
                    <template #body="slotProps">
                        <span class="text-sm font-medium line-clamp-1 text-charcoal">{{ slotProps.data.title
                        }}</span>
                    </template>
                </Column>
                <Column field="priority" header="Priority">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.priority" :severity="prioritySeverity(slotProps.data.priority)"
                            class="capitalize text-xs font-medium" />
                    </template>
                </Column>
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <StatusBadge :status="slotProps.data.status" />
                    </template>
                </Column>
                <Column field="submitted_at" header="Submitted">
                    <template #body="slotProps">
                        <span class="text-sm text-slate-500">{{ new
                            Date(slotProps.data.submitted_at).toLocaleDateString() }}</span>
                    </template>
                </Column>
                <Column header="Actions" style="width: 120px; text-align: center;">
                    <template #body="slotProps">
                        <Button icon="pi pi-user-plus" text rounded class="w-10 h-10"
                            @click="openAssignDialog(slotProps.data)" v-tooltip.bottom="'Assign Complaint'" />
                        <Button icon="pi pi-eye" text rounded class="w-10 h-10"
                            @click="router.push(`/admin/complaints/${slotProps.data.id}`)"
                            v-tooltip.bottom="'View Details'" />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center py-8 text-slate-400">
                        <i class="pi pi-inbox text-4xl block mb-3"></i>
                        <p class="text-sm">No complaints found.</p>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Assign Dialog -->
        <Dialog v-model:visible="assignDialogVisible" modal :style="{ width: '450px' }" class="complaint-assign-dialog">
            <template #header>
                <div class="text-xl font-semibold text-charcoal">Assign Complaint</div>
            </template>
            <div class="space-y-4">
                <p class="text-sm text-slate-600">
                    Assigning complaint <span class="font-semibold text-charcoal">{{ complaintToAssign?.reference_no
                    }}</span>.
                </p>

                <div class="field">
                    <label for="assignTo" class="block font-medium text-charcoal mb-2">Assign To</label>
                    <Dropdown id="assignTo" v-model="assignForm.assigned_to" :options="store.staffList"
                        optionLabel="name" optionValue="id" placeholder="Select Staff Member" class="w-full"
                        inputClass="!py-2.5 !px-4 !rounded-lg" panelClass="!rounded-lg">
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-user text-slate-400"></i>
                                <span class="font-medium text-charcoal">{{ slotProps.option.name }}</span>
                                <span class="text-slate-400 text-sm ml-auto">({{ slotProps.option.department || 'N/A'
                                    }})</span>
                            </div>
                        </template>
                    </Dropdown>
                </div>

                <div class="field">
                    <label for="assignNote" class="block font-medium text-charcoal mb-2">Note (optional)</label>
                    <Textarea id="assignNote" v-model="assignForm.note" rows="3" class="w-full"
                        placeholder="Add assignment instructions..." />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50"
                    @click="assignDialogVisible = false" />
                <Button label="Assign" icon="pi pi-check" :loading="store.loading"
                    class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2.5 !px-5"
                    @click="handleAssign" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../stores/complaints';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import StatusBadge from '../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();
const selectedComplaint = ref(null);
const assignDialogVisible = ref(false);
const complaintToAssign = ref(null);
const assignForm = reactive({ assigned_to: null, note: '' });

onMounted(() => {
    store.fetchComplaints(1);
    store.fetchOfficersList();
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
    if (!assignForm.assigned_to) {
        // Show error message or handle the case where no staff member is selected
        return;
    }

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
