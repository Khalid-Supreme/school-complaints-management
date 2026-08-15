<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">Assigned Complaints</h1>

        <div class="bg-white rounded-xl p-6 shadow-xl ring-1 ring-slate-900/5">
            <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

            <DataTable 
                :value="store.assignments" 
                :loading="store.loading"
                paginator 
                :rows="store.assignmentMeta?.per_page || 15"
                :totalRecords="store.assignmentMeta?.total || 0"
                lazy
                @page="onPage"
                responsiveLayout="scroll"
            >
                <Column header="#">
                    <template #body="slotProps">
                        {{ ((store.assignmentMeta?.current_page || 1) - 1) * (store.assignmentMeta?.per_page || 15) + slotProps.index + 1 }}
                    </template>
                </Column>
                <Column header="Reference No">
                    <template #body="slotProps">
                        {{ slotProps.data.complaint?.reference_no }}
                    </template>
                </Column>
                <Column header="Category">
                    <template #body="slotProps">
                        {{ slotProps.data.complaint?.category?.name }}
                    </template>
                </Column>
                <Column header="Priority">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.complaint?.priority" :severity="prioritySeverity(slotProps.data.complaint?.priority)" rounded />
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="slotProps">
                        <StatusBadge :status="slotProps.data.complaint?.status" />
                    </template>
                </Column>
                <Column header="Assigned At">
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.assigned_at).toLocaleDateString() }}
                    </template>
                </Column>
                <Column header="Assigned By" field="assigned_by"></Column>
                <template #empty>
                    <div class="text-center p-4">No assigned complaints found.</div>
                </template>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useComplaintsStore } from '../../../stores/complaints';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import StatusBadge from '../../../components/StatusBadge.vue';

const store = useComplaintsStore();

onMounted(() => {
    store.fetchStaffAssignments(1);
});

const onPage = (event) => {
    store.fetchStaffAssignments(event.page + 1);
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
