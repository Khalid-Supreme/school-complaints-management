<template>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-charcoal tracking-tight">System Administration Dashboard</h1>
                <!-- <p class="text-slate-400 text-sm mt-1 font-medium">Al-Hikmah University — Secure Complaint Management
                    Console</p> -->
            </div>
            <div class="flex gap-2">
                <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading"
                    @click="loadData" v-tooltip.bottom="'Refresh Data'" />
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="card in metricCards" :key="card.label"
                class="bg-white rounded-xl p-6 shadow-card transition-shadow hover:shadow-lg relative overflow-hidden flex flex-col justify-between"
                style="border: 1px solid #E8EFE9;">
                <div class="absolute -right-4 -bottom-4 text-[90px] opacity-5 font-black z-0"
                    :style="`color: ${card.baseColor}40;`">
                    <i :class="`pi ${card.icon}`"></i>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">{{
                    card.label }}</p>
                <div class="text-4xl font-semibold text-charcoal tracking-tight relative z-10">
                    {{ loading ? '—' : card.value }}
                </div>
                <p class="text-xs mt-3 font-medium relative z-10" :style="`color: ${card.baseColor}`">{{ card.sub }}</p>
            </div>
        </div>

        <!-- Recent Complaints & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <Card class="lg:col-span-2 shadow-xl ring-1 ring-slate-900/5">
                <template #title>
                    <div class="flex justify-between items-center pb-4 mb-4"
                        style="border-bottom: 1px solid var(--color-border);">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-list text-sage-600 text-lg"></i>
                            <span class="text-lg font-semibold text-charcoal">Recent Complaints</span>
                        </div>
                        <Button label="View All" icon="pi pi-arrow-right" icon-pos="right" text
                            class="!text-sage-600 hover:!bg-sage-50 !font-semibold"
                            @click="router.push('/admin/complaints')" />
                    </div>
                </template>
                <template #content>
                    <DataTable :value="recentComplaints" :loading="loading" responsiveLayout="scroll"
                        class="p-datatable-sm" :rows="5">
                        <Column field="reference_no" header="Ref No" style="width: 130px">
                            <template #body="slotProps">
                                <span
                                    class="font-mono text-xs font-medium text-slate-600 bg-sage-50 px-2 py-1 rounded-md">{{
                                        slotProps.data.reference_no }}</span>
                            </template>
                        </Column>
                        <Column field="complainant.name" header="Complainant" />
                        <Column field="category" header="Category" />
                        <Column field="title" header="Subject">
                            <template #body="slotProps">
                                <span class="text-sm font-medium line-clamp-1 text-charcoal">{{ slotProps.data.title
                                    }}</span>
                            </template>
                        </Column>
                        <Column field="priority" header="Priority">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.priority"
                                    :severity="prioritySeverity(slotProps.data.priority)"
                                    class="capitalize text-xs font-medium" />
                            </template>
                        </Column>
                        <Column field="status" header="Status">
                            <template #body="slotProps">
                                <StatusBadge :status="slotProps.data.status" />
                            </template>
                        </Column>
                        <Column header="Action" style="width: 100px">
                            <template #body="slotProps">
                                <Button icon="pi pi-eye" text rounded class="w-10 h-10"
                                    @click="router.push(`/admin/complaints/${slotProps.data.id}`)"
                                    v-tooltip.bottom="'View Complaint'" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-8 text-slate-400">
                                <i class="pi pi-inbox text-4xl block mb-3"></i>
                                <p class="text-sm">No complaints in the system yet.</p>
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>

            <!-- Quick Actions & Security Status -->
            <div class="space-y-6">
                <Card class="shadow-card">
                    <template #title>
                        <div class="flex items-center gap-2 text-lg font-semibold text-charcoal pb-4 mb-4"
                            style="border-bottom: 1px solid var(--color-border);">
                            <i class="pi pi-bolt text-amber-500"></i>
                            Quick Actions
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <Button label="All Complaints" icon="pi pi-bars"
                                class="w-full !justify-start !text-left !border !border-sage-100 !text-charcoal hover:!bg-sage-50"
                                @click="router.push('/admin/complaints')" />
                            <Button label="Security Dashboard" icon="pi pi-shield"
                                class="w-full !justify-start !text-left !bg-white !border !border-sage-100 !text-charcoal hover:!bg-sage-50"
                                @click="router.push('/security')" />
                        </div>
                    </template>
                </Card>

                <Card class="shadow-card" style="border: 1px solid #ECFDF5; background: #F6FEF9;">
                    <template #title>
                        <div class="flex items-center gap-2 text-lg font-semibold text-charcoal pb-4 mb-4"
                            style="border-bottom: 1px solid #E0F2F7;">
                            <i class="pi pi-shield text-sage-600"></i>
                            Security Status
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <div
                                class="flex items-center justify-between p-3 rounded-lg border border-emerald-100 bg-emerald-50">
                                <span class="text-sm font-medium text-emerald-800">AES-256 Encryption</span>
                                <Badge value="ACTIVE" severity="success" class="text-xs font-bold" />
                            </div>
                            <div
                                class="flex items-center justify-between p-3 rounded-lg border border-emerald-100 bg-emerald-50">
                                <span class="text-sm font-medium text-emerald-800">IPS Firewall</span>
                                <Badge value="ACTIVE" severity="success" class="text-xs font-bold" />
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg border"
                                :class="metrics?.security_events > 0 ? 'border-red-100 bg-red-50' : 'border-slate-100 bg-slate-50'">
                                <span class="text-sm font-medium"
                                    :class="metrics?.security_events > 0 ? 'text-red-700' : 'text-slate-700'">
                                    Security Events (Total)
                                </span>
                                <Badge :value="metrics?.security_events || '0'"
                                    :severity="metrics?.security_events > 0 ? 'danger' : 'secondary'"
                                    class="text-xs font-bold" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
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
    { label: 'Total Complaints', value: metrics.value?.total_complaints ?? 0, icon: 'pi-folder', baseColor: '#6A9C5E', sub: 'All complaint records' },
    { label: 'Total Users', value: metrics.value?.total_users ?? 0, icon: 'pi-users', baseColor: '#45693C', sub: 'Registered accounts' },
    { label: 'Active Cases', value: metrics.value?.assigned_complaints ?? 0, icon: 'pi-clock', baseColor: '#55834B', sub: 'Assigned & in progress' },
    { label: 'Security Alerts', value: metrics.value?.security_events ?? 0, icon: 'pi-exclamation-triangle', baseColor: '#B91C1C', sub: 'SQLi & XSS detections' },
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
