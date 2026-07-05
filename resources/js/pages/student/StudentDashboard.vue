<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-surface-900 dark:text-surface-0 tracking-tight">Student Dashboard</h1>
                <p class="text-surface-500 text-sm">Welcome back. Securely lodge and track your complaints.</p>
            </div>
            <Button label="Submit Complaint" icon="pi pi-plus" class="bg-blue-600 hover:bg-blue-700 border-none rounded-lg text-white font-bold" @click="goToSubmit" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                <template #title>
                    <div class="flex justify-between items-center text-surface-500 dark:text-surface-400 font-bold text-sm tracking-wider uppercase">
                        <span>Total Submitted</span>
                        <i class="pi pi-file text-blue-500 text-lg"></i>
                    </div>
                </template>
                <template #content>
                    <div class="text-4xl font-extrabold mt-1 text-surface-900 dark:text-surface-0">{{ stats.total }}</div>
                </template>
            </Card>
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                <template #title>
                    <div class="flex justify-between items-center text-surface-500 dark:text-surface-400 font-bold text-sm tracking-wider uppercase">
                        <span>Under Investigation</span>
                        <i class="pi pi-clock text-amber-500 text-lg animate-spin" style="animation-duration: 3s"></i>
                    </div>
                </template>
                <template #content>
                    <div class="text-4xl font-extrabold mt-1 text-surface-900 dark:text-surface-0">{{ stats.pending }}</div>
                </template>
            </Card>
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                <template #title>
                    <div class="flex justify-between items-center text-surface-500 dark:text-surface-400 font-bold text-sm tracking-wider uppercase">
                        <span>Resolved Cases</span>
                        <i class="pi pi-check-circle text-green-500 text-lg"></i>
                    </div>
                </template>
                <template #content>
                    <div class="text-4xl font-extrabold mt-1 text-surface-900 dark:text-surface-0">{{ stats.resolved }}</div>
                </template>
            </Card>
        </div>

        <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
            <template #title>
                <div class="flex justify-between items-center border-b border-surface-100 dark:border-surface-800 pb-3">
                    <span class="text-xl font-bold">Recent Secure Complaints</span>
                    <span class="text-xs text-green-600 dark:text-green-400 font-semibold bg-green-50 dark:bg-green-950/30 px-2.5 py-1 rounded-full border border-green-200/30">AES-256 Decrypted View</span>
                </div>
            </template>
            <template #content>
                <DataTable :value="store.complaints" :loading="store.loading" responsiveLayout="scroll" class="p-datatable-sm" :rows="10">
                    <Column field="reference_no" header="Ref No" sortable>
                        <template #body="slotProps">
                            <span class="font-mono text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ slotProps.data.reference_no }}</span>
                        </template>
                    </Column>
                    <Column field="category.name" header="Category"></Column>
                    <Column field="title" header="Subject"></Column>
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
                    <Column header="Actions">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" label="View Securely" class="p-button-text p-button-sm text-blue-600 hover:bg-blue-500/10" @click="viewComplaint(slotProps.data.id)" />
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-6 text-surface-500 dark:text-surface-400">
                            <i class="pi pi-folder-open text-3xl block mb-2 text-slate-300"></i>
                            <span>You have not submitted any complaints yet.</span>
                        </div>
                    </template>
                </DataTable>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../stores/complaints';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import StatusBadge from '../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();

const stats = computed(() => {
    const list = store.complaints || [];
    const total = list.length;
    const pending = list.filter(c => ['submitted', 'under_review', 'assigned', 'in_progress'].includes(c.status)).length;
    const resolved = list.filter(c => ['resolved', 'closed'].includes(c.status)).length;
    return { total, pending, resolved };
});

const goToSubmit = () => router.push('/student/submit');
const viewComplaint = (id) => router.push(`/student/complaints/${id}`);

onMounted(async () => {
    await store.fetchComplaints(1);
});
</script>
