<template>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Security Dashboard</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">Intrusion Prevention System & Application Security
                    Monitor</p>
            </div>
            <div class="flex gap-2">
                <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading"
                    @click="loadData" v-tooltip.bottom="'Refresh Data'" />
                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 px-4 py-2 rounded-lg">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-emerald-700">IPS ACTIVE</span>
                </div>
            </div>
        </div>

        <!-- System Security Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="card in securityCards" :key="card.label"
                class="bg-white rounded-xl p-4 sm:p-6 shadow-card transition-shadow hover:shadow-lg relative overflow-hidden flex flex-col justify-between"
                :style="`border: 1px solid ${card.danger ? '#FEE2E2' : '#E8EFE9'}`">
                <div class="absolute -right-4 -bottom-4 text-[60px] sm:text-[90px] opacity-5 font-black z-0"
                    :style="`color: ${card.color}40;`">
                    <i :class="`pi ${card.icon}`"></i>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">{{
                    card.label }}</p>
                <div class="text-2xl sm:text-4xl font-semibold text-charcoal tracking-tight relative z-10"
                    :class="card.danger && card.value > 0 ? 'text-red-600' : 'text-charcoal'">
                    {{ loading ? '—' : card.value }}
                </div>
                <p class="text-xs mt-3 font-medium relative z-10" :style="`color: ${card.color}`">{{ card.sub }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Security Events -->
            <Card class="lg:col-span-2 shadow-xl ring-1 ring-slate-900/5">
                <template #title>
                    <div class="flex justify-between items-center pb-4 mb-4"
                        style="border-bottom: 1px solid var(--color-border);">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle text-amber-500 text-lg"></i>
                            <span class="text-base lg:text-lg font-semibold text-charcoal">Recent Security Events</span>
                        </div>
                        <Badge :value="`${events.length} ${events.length === 1 ? 'Event' : 'Events'}`"
                            :severity="events.length > 0 ? 'danger' : 'success'" class="text-xs font-bold" />
                    </div>
                </template>
                <template #content>
                    <div class="overflow-x-auto">
                    <DataTable :value="events" :loading="loading" responsiveLayout="scroll" class="p-datatable-sm"
                        :rows="8">
                        <Column header="#" style="width: 50px">
                            <template #body="slotProps">
                                <span class="text-xs text-slate-500">{{ slotProps.index + 1 }}</span>
                            </template>
                        </Column>
                        <Column field="type" header="Attack Type" style="width: 140px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full"
                                        :class="slotProps.data.type?.includes('sqli') ? 'bg-red-500' : 'bg-amber-500'"></span>
                                    <span class="font-bold text-xs uppercase tracking-wider"
                                        :class="slotProps.data.type?.includes('sqli') ? 'text-red-600' : 'text-amber-600'">{{
                                            formatType(slotProps.data.type) }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="ip" header="IP Address" style="width: 130px">
                            <template #body="slotProps">
                                <span
                                    class="font-mono text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded-md">{{
                                        slotProps.data.ip }}</span>
                            </template>
                        </Column>
                        <Column field="user_id" header="User" style="width: 140px">
                            <template #body="slotProps">
                                <div class="text-xs">
                                    <span v-if="slotProps.data.user_id" class="font-mono font-medium text-slate-700">#{{ slotProps.data.user_id }}</span>
                                    <span v-else class="text-slate-400 italic">Guest</span>
                                    <div v-if="slotProps.data.user_email" class="text-[11px] text-slate-400 truncate max-w-[130px]">{{ slotProps.data.user_email }}</div>
                                </div>
                            </template>
                        </Column>
                        <Column field="reason" header="Rule / Reason" />
                        <Column field="payload_snippet" header="Payload">
                            <template #body="slotProps">
                                <code
                                    class="text-xs font-mono bg-slate-100 text-red-600 px-2 py-1 rounded max-w-40 inline-block truncate">{{
                                        slotProps.data.payload_snippet || (slotProps.data.payload ? String(slotProps.data.payload).slice(0, 220) : '—') }}</code>
                            </template>
                        </Column>
                        <Column field="detected_at" header="Detected" style="width: 150px">
                            <template #body="slotProps">
                                <span class="text-xs text-slate-500">{{ (slotProps.data.detected_at || slotProps.data.created_at) ? new
                                    Date(slotProps.data.detected_at || slotProps.data.created_at).toLocaleString() : '—' }}</span>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-10 text-slate-400">
                                <i class="pi pi-shield text-4xl block mb-3 text-emerald-400"></i>
                                <p class="font-semibold text-emerald-700">No intrusion events detected.</p>
                                <p class="text-sm text-slate-400">All requests are being monitored in real time.</p>
                            </div>
                        </template>
                    </DataTable>
                    </div>
                </template>
            </Card>

            <!-- Quick Security Status -->
            <div class="space-y-6">
                <!-- Blocked IPs -->
                <Card class="shadow-card">
                    <template #title>
                        <div class="flex items-center gap-2 text-base lg:text-lg font-semibold text-charcoal pb-4 mb-4"
                            style="border-bottom: 1px solid var(--color-border);">
                            <i class="pi pi-ban text-red-600"></i>
                            Blocked IPs (24h)
                            <Badge :value="blockedIps.length" severity="danger" class="text-xs font-bold ml-auto" />
                            <Button label="View All" icon="pi pi-external-link" text size="small" class="!text-sage-600 hover:!bg-sage-50 !text-xs" @click="$router.push('/security/blocked-ips')" />
                        </div>
                    </template>
                    <template #content>
                        <div v-if="blockedIps.length === 0" class="text-center py-6">
                            <i class="pi pi-check-circle text-3xl text-emerald-500 block mb-2"></i>
                            <p class="text-sm font-medium text-emerald-700">No IPs blocked</p>
                        </div>
                        <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                            <div v-for="item in blockedIps" :key="item.ip"
                                class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                                <i class="pi pi-ban text-red-500 text-sm"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="font-mono text-sm font-medium text-red-700">{{ item.ip }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ item.reason || 'Suspicious activity' }}</p>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        <span v-if="item.user_id">#{{ item.user_id }}<span v-if="item.user_email"> ({{ item.user_email }})</span> • </span>{{ item.blocked_at ? new Date(item.blocked_at).toLocaleString() : '' }}
                                    </p>
                                </div>
                                <Button icon="pi pi-unlock" text rounded class="w-8 h-8 !text-red-500 hover:!bg-red-100"
                                    :loading="unblockingIp === item.ip" v-tooltip.bottom="'Unblock this IP'"
                                    @click="unblockIp(item.ip)" />
                            </div>
                        </div>
                        <div v-if="blockedIps.length > 0" class="text-center mt-3">
                            <Button label="View full paginated table with search" icon="pi pi-table" text size="small" class="!text-sage-600 hover:!bg-sage-50" @click="$router.push('/security/blocked-ips')" />
                        </div>
                    </template>
                </Card>

                <!-- Blocked Users (Hybrid) -->
                <Card class="shadow-card">
                    <template #title>
                        <div class="flex items-center gap-2 text-base lg:text-lg font-semibold text-charcoal pb-4 mb-4"
                            style="border-bottom: 1px solid var(--color-border);">
                            <i class="pi pi-user text-red-600"></i>
                            Blocked Users (24h)
                            <Badge :value="blockedUsers.length" severity="danger" class="text-xs font-bold ml-auto" />
                            <Button label="View All" icon="pi pi-external-link" text size="small" class="!text-sage-600 hover:!bg-sage-50 !text-xs" @click="$router.push('/security/blocked-users')" />
                        </div>
                    </template>
                    <template #content>
                        <div v-if="blockedUsers.length === 0" class="text-center py-6">
                            <i class="pi pi-check-circle text-3xl text-emerald-500 block mb-2"></i>
                            <p class="text-sm font-medium text-emerald-700">No users blocked</p>
                        </div>
                        <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                            <div v-for="item in blockedUsers" :key="item.user_id"
                                class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                                <i class="pi pi-user text-red-500 text-sm"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="font-mono text-sm font-medium text-red-700">#{{ item.user_id }} <span v-if="item.email" class="font-normal text-xs">({{ item.email }})</span></p>
                                    <p class="text-xs text-slate-500 truncate">{{ item.reason || 'Suspicious activity' }}</p>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        <span v-if="item.institution_id">{{ item.institution_id }} • </span>{{ item.ip || '' }} • {{ item.blocked_at ? new Date(item.blocked_at).toLocaleString() : '' }}
                                    </p>
                                </div>
                                <Button icon="pi pi-unlock" text rounded class="w-8 h-8 !text-red-500 hover:!bg-red-100"
                                    :loading="unblockingUser === item.user_id" v-tooltip.bottom="'Unblock this user'"
                                    @click="unblockUser(item.user_id)" />
                            </div>
                        </div>
                        <div v-if="blockedUsers.length > 0" class="text-center mt-3">
                            <Button label="View full paginated table with search" icon="pi pi-table" text size="small" class="!text-sage-600 hover:!bg-sage-50" @click="$router.push('/security/blocked-users')" />
                        </div>
                    </template>
                </Card>

                <!-- Security Features -->
                <Card class="shadow-card" style="border: 1px solid #E0F2FE; background: #F8FEFF;">
                    <template #title>
                        <div class="flex items-center gap-2 text-base lg:text-lg font-semibold text-charcoal pb-4 mb-4"
                            style="border-bottom: 1px solid #E0F2F7;">
                            <i class="pi pi-shield text-blue-600"></i>
                            Security Features
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-2">
                            <div v-for="feat in securityFeatures" :key="feat.name"
                                class="flex items-center gap-3 p-3 rounded-lg"
                                :class="feat.active ? 'bg-emerald-50 border border-emerald-100' : 'bg-red-50 border border-red-100'">
                                <i
                                    :class="`pi ${feat.icon} text-sm ${feat.active ? 'text-emerald-600' : 'text-red-500'}`"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-charcoal">{{ feat.name }}</p>
                                    <p class="text-xs text-slate-400">{{ feat.desc }}</p>
                                </div>
                                <Badge :value="feat.active ? 'ON' : 'OFF'"
                                    :severity="feat.active ? 'success' : 'danger'" class="text-xs font-bold" />
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
import api from '../../services/api';
import { useToast } from 'primevue/usetoast';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const toast = useToast();
const loading = ref(true);
const data = ref(null);
const unblockingIp = ref(null);
const unblockingUser = ref(null);

const events = computed(() => data.value?.recent_events || []);
const blockedIps = computed(() => data.value?.blocked_ips || []);
const blockedUsers = computed(() => data.value?.blocked_users || []);

const securityCards = computed(() => [
    { label: 'SQLi Detections', value: data.value?.sqli_count ?? data.value?.intrusion_detections?.sqli_count ?? 0, icon: 'pi-database', color: '#ef4444', sub: 'SQL Injection attempts', danger: true },
    { label: 'XSS Detections', value: data.value?.xss_count ?? data.value?.intrusion_detections?.xss_count ?? 0, icon: 'pi-code', color: '#f97316', sub: 'Cross-Site Scripting attempts', danger: true },
    { label: 'IPs Blocked', value: blockedIps.value.length, icon: 'pi-ban', color: '#dc2626', sub: 'Currently blocked (24h)', danger: true },
    { label: 'Users Blocked', value: blockedUsers.value.length, icon: 'pi-user', color: '#991b1b', sub: 'Accounts blocked (24h)', danger: true },
    { label: 'Total Events', value: data.value?.total_events ?? data.value?.intrusion_detections?.total_events ?? (data.value?.recent_events?.length ?? 0), icon: 'pi-exclamation-circle', color: '#8b5cf6', sub: 'All security detections', danger: false },
]);

const securityFeatures = [
    { name: 'AES-256 Encryption', icon: 'pi-lock', desc: 'Laravel Crypt (AES-256-CBC)', active: true },
    { name: 'IPS Middleware', icon: 'pi-shield', desc: 'SQL Injection & XSS detection', active: true },
    { name: 'Rate Limiting', icon: 'pi-clock', desc: 'API request throttling (60/min)', active: true },
    { name: 'Bearer Token Auth', icon: 'pi-key', desc: 'Laravel Sanctum token authentication', active: true },
    { name: 'IP Auto-Block', icon: 'pi-ban', desc: '24h automatic IP blocking on detection', active: true },
    { name: 'CSRF Protection', icon: 'pi-verified', desc: 'Laravel CSRF middleware', active: true },
];

const formatType = (t) => {
    if (!t) return 'Unknown';
    return t.replace('_', ' ').replace('sqli', 'SQL Injection').replace('xss', 'XSS');
};

const loadData = async () => {
    loading.value = true;
    try {
        const res = await api.get('/api/security/dashboard');
        data.value = res.data;
    } catch (e) {
        console.warn('Security dashboard load failed', e);
    } finally {
        loading.value = false;
    }
};

const unblockIp = async (ip) => {
    unblockingIp.value = ip;
    try {
        await api.post('/api/security/ips/unblock', { ip });
        toast.add({ severity: 'success', summary: 'Unblocked', detail: `IP ${ip} has been unblocked.`, life: 3000 });
        await loadData();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to unblock IP.', life: 3000 });
    } finally {
        unblockingIp.value = null;
    }
};

const unblockUser = async (userId) => {
    unblockingUser.value = userId;
    try {
        await api.post('/api/security/users/unblock', { user_id: userId });
        toast.add({ severity: 'success', summary: 'Unblocked', detail: `User #${userId} has been unblocked.`, life: 3000 });
        await loadData();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to unblock user.', life: 3000 });
    } finally {
        unblockingUser.value = null;
    }
};

onMounted(loadData);
</script>
