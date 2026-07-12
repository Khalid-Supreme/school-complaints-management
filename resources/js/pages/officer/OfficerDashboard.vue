<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-charcoal tracking-tight">Officer Dashboard</h1>
                <p class="text-slate-500 text-sm mt-1 font-medium">Review and resolve assigned complaints securely.</p>
            </div>
            <div class="flex gap-2">
                <Badge :value="`${stats.active} Active`" severity="warn" class="text-xs font-bold" />
                <Badge :value="`${stats.resolved} Resolved`" severity="success" class="text-xs font-bold" />
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="stat in statCards" :key="stat.label"
                class="bg-white rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg relative overflow-hidden flex flex-col justify-between"
                style="border: 1px solid #E8EFE9;">
                <div class="absolute -right-4 -bottom-4 text-[90px] opacity-5 font-black z-0"
                    :style="`color: ${stat.baseColor}40;`">
                    <i :class="`pi ${stat.icon}`"></i>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">{{
                    stat.label }}</p>
                <div class="text-4xl font-semibold text-charcoal tracking-tight relative z-10">
                    {{ loading ? '—' : stat.value }}
                </div>
                <p class="text-xs mt-3 font-medium relative z-10" :style="`color: ${stat.baseColor}`">{{ stat.sub }}</p>
            </div>
        </div>

        <!-- Assignments Table -->
        <Card class="shadow-card">
            <template #title>
                <div class="flex justify-between items-center pb-4 mb-4"
                    style="border-bottom: 1px solid var(--color-border);">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-sage-600 text-lg"></i>
                        <span class="text-lg font-semibold text-charcoal">Assigned Complaints</span>
                    </div>
                    <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50"
                        :loading="store.loading" @click="store.fetchStaffAssignments(1)"
                        v-tooltip.bottom="'Refresh Assignments'" />
                </div>
            </template>
            <template #content>
                <DataTable :value="assignments" :loading="store.loading" responsiveLayout="scroll"
                    class="p-datatable-sm" dataKey="id" :rows="10">
                    <Column field="complaint.reference_no" header="Ref No" style="width: 130px">
                        <template #body="slotProps">
                            <span class="font-mono text-xs font-medium text-slate-600 bg-sage-50 px-2 py-1 rounded-md">
                                {{ slotProps.data.complaint?.reference_no }}
                            </span>
                        </template>
                    </Column>
                    <Column field="complaint.title" header="Subject">
                        <template #body="slotProps">
                            <span class="text-sm font-medium line-clamp-1 text-charcoal">{{
                                slotProps.data.complaint?.title }}</span>
                        </template>
                    </Column>
                    <Column field="complaint.category.name" header="Category" />
                    <Column field="complaint.priority" header="Priority">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.complaint?.priority"
                                :severity="prioritySeverity(slotProps.data.complaint?.priority)"
                                class="capitalize text-xs font-medium" />
                        </template>
                    </Column>
                    <Column field="complaint.status" header="Status">
                        <template #body="slotProps">
                            <StatusBadge :status="slotProps.data.complaint?.status" />
                        </template>
                    </Column>
                    <Column field="assigned_at" header="Assigned">
                        <template #body="slotProps">
                            <span class="text-sm text-slate-500">{{ new
                                Date(slotProps.data.assigned_at).toLocaleDateString() }}</span>
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 100px">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" text rounded class="w-10 h-10"
                                @click="viewComplaint(slotProps.data.complaint?.id)"
                                v-tooltip.bottom="'View Details'" />
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-8 text-slate-400">
                            <i class="pi pi-inbox text-4xl block mb-3"></i>
                            <p class="text-sm">No assignments found.</p>
                        </div>
                    </template>
                </DataTable>
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
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import StatusBadge from '../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();
const loading = ref(true); // Added loading state for metric cards

const assignments = computed(() => store.assignments || []);

const stats = computed(() => {
    const list = assignments.value;
    return {
        total: list.length,
        active: list.filter(a => ['assigned', 'in_progress'].includes(a.complaint?.status)).length,
        resolved: list.filter(a => ['resolved', 'closed'].includes(a.complaint?.status)).length,
        high: list.filter(a => ['high', 'critical'].includes(a.complaint?.priority)).length,
    };
});

// Re-map stats to metricCards for consistent styling
const statCards = computed(() => [
    { label: 'Total Assigned', value: stats.value.total ?? 0, icon: 'pi-briefcase', baseColor: '#6A9C5E', sub: 'All your assigned cases' },
    { label: 'Active Cases', value: stats.value.active ?? 0, icon: 'pi-clock', baseColor: '#55834B', sub: 'Currently in progress' },
    { label: 'High Priority', value: stats.value.high ?? 0, icon: 'pi-exclamation-triangle', baseColor: '#B91C1C', sub: 'Urgent attention required' },
    { label: 'Resolved', value: stats.value.resolved ?? 0, icon: 'pi-check-circle', baseColor: '#2F855A', sub: 'Successfully closed' },
]);


const prioritySeverity = (p) => ({ high: 'danger', critical: 'danger', medium: 'warn', low: 'info' }[p] ?? 'secondary');
const viewComplaint = (id) => { if (id) router.push(`/officer/complaints/${id}`); };

onMounted(async () => {
    await store.fetchStaffAssignments(1);
    loading.value = false; // Set loading to false after data is fetched
});
</script>
