<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Student Dashboard</h1>
                <p class="text-slate-500 text-sm mt-1 font-medium">Welcome back. Securely lodge and track your
                    complaints.</p>
            </div>
            <Button label="Submit Complaint" icon="pi pi-plus"
                class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2 sm:!py-2.5 !px-4 sm:!px-5"
                @click="goToSubmit" />
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="card in metricCards" :key="card.label"
                class="bg-white rounded-xl p-4 sm:p-6 shadow-card transition-shadow hover:shadow-lg relative overflow-hidden flex flex-col justify-between"
                style="border: 1px solid #E8EFE9;">
                <div class="absolute -right-4 -bottom-4 text-[60px] sm:text-[90px] opacity-5 font-black z-0"
                    :style="`color: ${card.baseColor}40;`">
                    <i :class="`pi ${card.icon}`"></i>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">{{
                    card.label }}</p>
                <div class="text-2xl sm:text-4xl font-semibold text-charcoal tracking-tight relative z-10">
                    {{ loading ? '—' : card.value }}
                </div>
                <p class="text-xs mt-3 font-medium relative z-10" :style="`color: ${card.baseColor}`">{{ card.sub }}</p>
            </div>
        </div>

        <!-- Recent Secure Complaints -->
        <Card class="shadow-xl ring-1 ring-slate-900/5">
            <template #title>
                <div class="flex justify-between items-center pb-4 mb-4"
                    style="border-bottom: 1px solid var(--color-border);">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-sage-600 text-lg"></i>
                        <span class="text-base lg:text-lg font-semibold text-charcoal">Recent Secure Complaints</span>
                    </div>
                    <span
                        class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2.5 py-1 rounded-full">AES-256
                        Decrypted View</span>
                </div>
            </template>
            <template #content>
                <div class="overflow-x-auto">
                <DataTable :value="store.complaints" :loading="store.loading" responsiveLayout="scroll"
                    class="p-datatable-sm" :rows="10">
                    <Column field="reference_no" header="Ref No" style="width: 130px">
                        <template #body="slotProps">
                            <span
                                class="font-mono text-xs font-medium text-slate-600 bg-sage-50 px-2 py-1 rounded-md">{{
                                    slotProps.data.reference_no }}</span>
                        </template>
                    </Column>
                    <Column field="category.name" header="Category" />
                    <Column field="title" header="Subject">
                        <template #body="slotProps">
                            <span class="text-sm font-medium line-clamp-1 text-charcoal">{{ slotProps.data.title
                            }}</span>
                        </template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <StatusBadge :status="slotProps.data.status" />
                        </template>
                    </Column>
                    <Column field="submitted_at" header="Date Submitted">
                        <template #body="slotProps">
                            <span class="text-sm text-slate-500">{{ new
                                Date(slotProps.data.submitted_at).toLocaleDateString() }}</span>
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 100px">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" text rounded class="w-10 h-10"
                                @click="viewComplaint(slotProps.data.id)" v-tooltip.bottom="'View Securely'" />
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-8 text-slate-400">
                            <i class="pi pi-inbox text-4xl block mb-3"></i>
                            <p class="text-sm">You have not submitted any complaints yet.</p>
                        </div>
                    </template>
                </DataTable>
                </div>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../stores/complaints';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import StatusBadge from '../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();
const loading = ref(true); // Added loading state for metric cards

const stats = computed(() => {
    const list = store.complaints || [];
    const total = list.length;
    const pending = list.filter(c => ['submitted', 'under_review', 'assigned', 'in_progress'].includes(c.status)).length;
    const resolved = list.filter(c => ['resolved', 'closed'].includes(c.status)).length;
    return { total, pending, resolved };
});

// Re-map stats to metricCards for consistent styling
const metricCards = computed(() => [
    { label: 'Total Submitted', value: stats.value.total ?? 0, icon: 'pi-folder', baseColor: '#6A9C5E', sub: 'All your complaint records' },
    { label: 'Under Investigation', value: stats.value.pending ?? 0, icon: 'pi-clock', baseColor: '#55834B', sub: 'Assigned & in progress' },
    { label: 'Resolved Cases', value: stats.value.resolved ?? 0, icon: 'pi-check-circle', baseColor: '#45693C', sub: 'Successfully closed cases' },
]);


const goToSubmit = () => router.push('/student/submit');
const viewComplaint = (id) => {
    console.log(id);
    router.push(`/student/complaints/${id}`);
};

onMounted(async () => {
    await store.fetchComplaints(1);
    loading.value = false; // Set loading to false after data is fetched
});
</script>
