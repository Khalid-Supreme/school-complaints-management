<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-surface-900 dark:text-surface-0 tracking-tight">Officer Dashboard</h1>
                <p class="text-surface-500 text-sm">Review and resolve assigned complaints securely.</p>
            </div>
            <div class="flex items-center gap-3">
                <Badge :value="`${stats.active} Active`" severity="warn" class="text-xs font-bold" />
                <Badge :value="`${stats.resolved} Resolved`" severity="success" class="text-xs font-bold" />
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="stat in statCards" :key="stat.label" class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-800 rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-surface-500 uppercase tracking-wider">{{ stat.label }}</span>
                    <i :class="`pi ${stat.icon} text-lg`" :style="`color: ${stat.color}`"></i>
                </div>
                <div class="text-3xl font-extrabold text-surface-900 dark:text-surface-0">{{ stat.value }}</div>
            </div>
        </div>

        <!-- Assignments Table -->
        <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
            <template #title>
                <div class="flex justify-between items-center border-b border-surface-100 dark:border-surface-800 pb-3">
                    <span class="text-xl font-bold">Assigned Complaints</span>
                    <Button icon="pi pi-refresh" text rounded class="text-surface-500 hover:bg-surface-100" :loading="store.loading" @click="store.fetchStaffAssignments(1)" />
                </div>
            </template>
            <template #content>
                <DataTable
                    :value="assignments"
                    :loading="store.loading"
                    responsiveLayout="scroll"
                    class="p-datatable-sm"
                    dataKey="id"
                >
                    <Column header="Ref No" style="width: 130px">
                        <template #body="slotProps">
                            <span class="font-mono text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                {{ slotProps.data.complaint?.reference_no }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Subject">
                        <template #body="slotProps">
                            <span class="font-medium text-sm">{{ slotProps.data.complaint?.title }}</span>
                        </template>
                    </Column>
                    <Column header="Category">
                        <template #body="slotProps">
                            <span class="text-sm">{{ slotProps.data.complaint?.category?.name }}</span>
                        </template>
                    </Column>
                    <Column header="Priority" style="width: 110px">
                        <template #body="slotProps">
                            <Tag
                                :value="slotProps.data.complaint?.priority"
                                :severity="prioritySeverity(slotProps.data.complaint?.priority)"
                                class="capitalize text-xs font-bold"
                            />
                        </template>
                    </Column>
                    <Column header="Status" style="width: 130px">
                        <template #body="slotProps">
                            <StatusBadge :status="slotProps.data.complaint?.status" />
                        </template>
                    </Column>
                    <Column header="Assigned" style="width: 110px">
                        <template #body="slotProps">
                            <span class="text-xs text-surface-500">{{ new Date(slotProps.data.assigned_at).toLocaleDateString() }}</span>
                        </template>
                    </Column>
                    <Column header="Actions" style="width: 130px">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-folder-open"
                                label="Process"
                                text
                                class="text-purple-600 hover:bg-purple-500/10 p-button-sm"
                                @click="viewComplaint(slotProps.data.complaint?.id)"
                            />
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-10 text-surface-500">
                            <i class="pi pi-inbox text-4xl block mb-3 text-slate-300"></i>
                            <p class="font-semibold">No assignments found.</p>
                            <p class="text-sm text-surface-400">You have no active complaint assignments.</p>
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
import Tag from 'primevue/tag';
import Badge from 'primevue/badge';
import StatusBadge from '../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();

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

const statCards = computed(() => [
    { label: 'Total Assigned', value: stats.value.total, icon: 'pi-briefcase', color: '#8b5cf6' },
    { label: 'Active Cases', value: stats.value.active, icon: 'pi-clock', color: '#f59e0b' },
    { label: 'High Priority', value: stats.value.high, icon: 'pi-exclamation-triangle', color: '#ef4444' },
    { label: 'Resolved', value: stats.value.resolved, icon: 'pi-check-circle', color: '#22c55e' },
]);

const prioritySeverity = (p) => ({ high: 'danger', critical: 'danger', medium: 'warn', low: 'info' }[p] ?? 'secondary');
const viewComplaint = (id) => { if (id) router.push(`/officer/complaints/${id}`); };

onMounted(() => store.fetchStaffAssignments(1));
</script>
