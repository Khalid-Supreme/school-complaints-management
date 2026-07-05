<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-surface-900 dark:text-surface-0 tracking-tight">System Administration</h1>
                <p class="text-surface-500 text-sm">Al-Hikmah University — Secure Complaint Management Console</p>
            </div>
            <div class="flex gap-2">
                <Button icon="pi pi-refresh" text rounded class="text-surface-500 hover:bg-surface-100" :loading="loading" @click="loadData" />
                <Button label="All Complaints" icon="pi pi-list" class="bg-emerald-600 hover:bg-emerald-700 border-none text-white font-bold" @click="router.push('/admin/complaints')" />
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="card in metricCards" :key="card.label"
                class="bg-surface-0 dark:bg-surface-900 border border-surface-200 dark:border-surface-800 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                <div class="absolute -right-3 -bottom-3 text-[80px] opacity-5 font-extrabold" :style="`color: ${card.color}`">
                    <i :class="`pi ${card.icon}`"></i>
                </div>
                <p class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-2">{{ card.label }}</p>
                <div class="text-4xl font-black text-surface-900 dark:text-surface-0 tracking-tight">{{ loading ? '—' : card.value }}</div>
                <p class="text-xs mt-2 font-semibold" :style="`color: ${card.color}`">{{ card.sub }}</p>
            </div>
        </div>

        <!-- Recent Complaints -->
        <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
            <template #title>
                <div class="flex justify-between items-center border-b border-surface-100 dark:border-surface-800 pb-3">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-list text-emerald-600"></i>
                        <span class="text-xl font-bold">Recent Complaints</span>
                    </div>
                    <Button label="View All" icon="pi pi-arrow-right" icon-pos="right" text class="text-emerald-600 hover:bg-emerald-500/10 font-semibold" @click="router.push('/admin/complaints')" />
                </div>
            </template>
            <template #content>
                <DataTable :value="recentComplaints" :loading="loading" responsiveLayout="scroll" class="p-datatable-sm" :rows="5">
                    <Column field="reference_no" header="Ref No" style="width: 130px">
                        <template #body="slotProps">
                            <span class="font-mono text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ slotProps.data.reference_no }}</span>
                        </template>
                    </Column>
                    <Column field="complainant.name" header="Complainant" />
                    <Column field="category.name" header="Category" />
                    <Column field="title" header="Subject">
                        <template #body="slotProps">
                            <span class="text-sm font-medium line-clamp-1">{{ slotProps.data.title }}</span>
                        </template>
                    </Column>
                    <Column field="priority" header="Priority">
                        <template #body="slotProps">
                            <Tag :value="slotProps.data.priority" :severity="prioritySeverity(slotProps.data.priority)" class="capitalize text-xs font-bold" />
                        </template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <StatusBadge :status="slotProps.data.status" />
                        </template>
                    </Column>
                    <Column header="Action" style="width: 100px">
                        <template #body="slotProps">
                            <Button icon="pi pi-eye" text rounded class="text-emerald-600 hover:bg-emerald-500/10 w-9 h-9" @click="router.push(`/admin/complaints/${slotProps.data.id}`)" />
                        </template>
                    </Column>
                    <template #empty>
                        <div class="text-center py-8 text-surface-500">
                            <i class="pi pi-inbox text-3xl block mb-2"></i>
                            No complaints in the system yet.
                        </div>
                    </template>
                </DataTable>
            </template>
        </Card>

        <!-- Quick Actions & System Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-surface-100 dark:border-surface-800">
                        <i class="pi pi-bolt text-amber-500"></i>
                        Quick Actions
                    </div>
                </template>
                <template #content>
                    <div class="space-y-2">
                        <Button label="All Complaints" icon="pi pi-list" class="w-full justify-start text-left bg-transparent border border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-300 hover:bg-surface-50 dark:hover:bg-surface-800" @click="router.push('/admin/complaints')" />
                        <Button label="Security Dashboard" icon="pi pi-shield" class="w-full justify-start text-left bg-transparent border border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-300 hover:bg-surface-50 dark:hover:bg-surface-800" @click="router.push('/security')" />
                    </div>
                </template>
            </Card>

            <Card class="border border-emerald-200 dark:border-emerald-900 bg-emerald-50/30 dark:bg-emerald-950/10 shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-emerald-100 dark:border-emerald-900">
                        <i class="pi pi-shield text-emerald-600 animate-pulse"></i>
                        Security Status
                    </div>
                </template>
                <template #content>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-950/30 rounded-lg border border-green-100 dark:border-green-900">
                            <span class="text-sm font-semibold text-green-800 dark:text-green-300">AES-256 Encryption</span>
                            <Badge value="ACTIVE" severity="success" class="text-xs font-bold" />
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-950/30 rounded-lg border border-green-100 dark:border-green-900">
                            <span class="text-sm font-semibold text-green-800 dark:text-green-300">IPS Firewall</span>
                            <Badge value="ACTIVE" severity="success" class="text-xs font-bold" />
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg border border-surface-200 dark:border-surface-700" :class="metrics?.security_events > 0 ? 'bg-red-50 dark:bg-red-950/30 border-red-100 dark:border-red-900' : 'bg-surface-50 dark:bg-surface-900'">
                            <span class="text-sm font-semibold" :class="metrics?.security_events > 0 ? 'text-red-700 dark:text-red-300' : 'text-surface-700 dark:text-surface-300'">
                                Security Events (Total)
                            </span>
                            <Badge :value="metrics?.security_events || '0'" :severity="metrics?.security_events > 0 ? 'danger' : 'secondary'" class="text-xs font-bold" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import StatusBadge from '../../components/StatusBadge.vue';

const router = useRouter();
const loading = ref(true);
const metrics = ref(null);
const recentComplaints = ref([]);

const metricCards = computed(() => [
    { label: 'Total Complaints', value: metrics.value?.total_complaints ?? 0, icon: 'pi-folder', color: '#3b82f6', sub: 'All complaint records' },
    { label: 'Total Users', value: metrics.value?.total_users ?? 0, icon: 'pi-users', color: '#8b5cf6', sub: 'Registered accounts' },
    { label: 'Active Cases', value: metrics.value?.assigned_complaints ?? 0, icon: 'pi-clock', color: '#f59e0b', sub: 'Assigned & in progress' },
    { label: 'Security Alerts', value: metrics.value?.security_events ?? 0, icon: 'pi-exclamation-triangle', color: '#ef4444', sub: 'SQLi & XSS detections' },
]);

const prioritySeverity = (p) => ({ high: 'danger', critical: 'danger', medium: 'warn', low: 'info' }[p] ?? 'secondary');

const loadData = async () => {
    loading.value = true;
    try {
        const res = await api.get('/api/admin/dashboard');
        metrics.value = res.data;
        recentComplaints.value = res.data.recent_complaints || [];
    } catch (e) {
        console.warn('Admin dashboard load failed', e);
    } finally {
        loading.value = false;
    }
};

onMounted(loadData);
</script>
