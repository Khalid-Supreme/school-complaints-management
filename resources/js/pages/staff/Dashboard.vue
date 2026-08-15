<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">Staff Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-xl ring-1 ring-slate-900/5">
                <div class="text-surface-500 dark:text-surface-400 font-medium mb-2">Active Assignments</div>
                <div class="text-3xl font-bold text-primary-500">{{ store.assignmentMeta?.total || 0 }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-xl ring-1 ring-slate-900/5">
            <h2 class="text-xl font-bold mb-4">My Assigned Complaints</h2>
            <DataTable :value="store.assignments" :loading="store.loading" responsiveLayout="scroll" :rows="5">
                <Column header="#">
                    <template #body="slotProps">
                        {{ slotProps.index + 1 }}
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
                    <div class="text-center p-4">No assignments found.</div>
                </template>
            </DataTable>
            <div class="mt-4 flex justify-end">
                <Button label="View All" link @click="router.push('/staff/complaints')" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../../stores/complaints';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import StatusBadge from '../../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();

onMounted(() => {
    store.fetchStaffAssignments(1);
});
</script>
