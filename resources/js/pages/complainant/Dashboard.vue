<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">Complainant Dashboard</h1>
            <Button label="Submit New Complaint" icon="pi pi-plus" @click="router.push('/complainant/complaints/create')" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-lg shadow">
                <div class="text-surface-500 dark:text-surface-400 font-medium mb-2">Total Complaints</div>
                <div class="text-3xl font-bold text-primary-500">{{ store.meta?.total || 0 }}</div>
            </div>
            <!-- More metrics could go here -->
        </div>

        <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Recent Complaints</h2>
            <DataTable :value="store.complaints" :loading="store.loading" responsiveLayout="scroll" :rows="5">
                <Column field="reference_no" header="Reference No"></Column>
                <Column field="category.name" header="Category"></Column>
                <Column field="title" header="Title"></Column>
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <StatusBadge :status="slotProps.data.status" />
                    </template>
                </Column>
                <Column field="submitted_at" header="Date Submitted">
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.submitted_at).toLocaleDateString() }}
                    </template>
                </Column>
            </DataTable>
            <div class="mt-4 flex justify-end">
                <Button label="View All" link @click="router.push('/complainant/complaints')" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../../stores/complaints';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import StatusBadge from '../../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();

onMounted(() => {
    store.fetchComplaints(1);
});
</script>
