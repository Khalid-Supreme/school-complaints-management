<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-surface-900 dark:text-surface-0 tracking-tight">Security Dashboard</h1>
                <p class="text-surface-500 text-sm">Intrusion Prevention System & Application Security Monitor</p>
            </div>
            <div class="flex gap-2">
                <Button icon="pi pi-refresh" text rounded class="text-surface-500 hover:bg-surface-100" :loading="loading" @click="loadData" />
                <div class="flex items-center gap-2 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 px-3 py-1.5 rounded-lg">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-ping absolute"></span>
                    <span class="w-2 h-2 rounded-full bg-green-500 relative"></span>
                    <span class="text-xs font-bold text-green-700 dark:text-green-400">IPS ACTIVE</span>
                </div>
            </div>
        </div>

        <!-- System Security Overview -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="card in securityCards" :key="card.label"
                class="relative overflow-hidden rounded-2xl border p-5 shadow-sm"
                :class="card.danger ? 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-900' : 'bg-surface-0 dark:bg-surface-900 border-surface-200 dark:border-surface-800'">
                <div class="absolute -right-2 -bottom-2 opacity-[0.07]">
                    <i :class="`pi ${card.icon} text-[72px]`" :style="`color: ${card.color}`"></i>
                </div>
                <p class="text-xs font-bold uppercase tracking-wider mb-2" :class="card.danger ? 'text-red-500' : 'text-surface-500'">{{ card.label }}</p>
                <div class="text-4xl font-black tracking-tight" :class="card.danger && card.value > 0 ? 'text-red-700 dark:text-red-400' : 'text-surface-900 dark:text-surface-0'">
                    {{ loading ? '—' : card.value }}
                </div>
                <p class="text-xs mt-2" :style="`color: ${card.color}`">{{ card.sub }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Threat Detection log -->
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm md:col-span-2">
                <template #title>
                    <div class="flex justify-between items-center border-b border-surface-100 dark:border-surface-800 pb-3">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle text-amber-500"></i>
                            <span class="text-xl font-bold">Recent Security Events</span>
                        </div>
                        <Badge :value="`${events.length} Events`" :severity="events.length > 0 ? 'danger' : 'success'" class="text-xs font-bold" />
                    </div>
                </template>
                <template #content>
                    <DataTable :value="events" :loading="loading" responsiveLayout="scroll" class="p-datatable-sm" :rows="10">
                        <Column field="type" header="Attack Type" style="width: 160px">
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="slotProps.data.type?.includes('sqli') ? 'bg-red-500' : 'bg-amber-500'"></span>
                                    <span class="font-bold text-xs uppercase tracking-wider" :class="slotProps.data.type?.includes('sqli') ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'">{{ formatType(slotProps.data.type) }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="ip" header="IP Address" style="width: 140px">
                            <template #body="slotProps">
                                <span class="font-mono text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-1.5 py-0.5 rounded">{{ slotProps.data.ip }}</span>
                            </template>
                        </Column>
                        <Column field="reason" header="Reason / Rule Matched">
                            <template #body="slotProps">
                                <span class="text-sm text-surface-700 dark:text-surface-300">{{ slotProps.data.reason }}</span>
                            </template>
                        </Column>
                        <Column field="payload_snippet" header="Payload Snippet">
                            <template #body="slotProps">
                                <code class="text-xs font-mono bg-slate-100 dark:bg-slate-900 text-red-600 dark:text-red-400 px-2 py-0.5 rounded max-w-48 block truncate">{{ slotProps.data.payload_snippet }}</code>
                            </template>
                        </Column>
                        <Column field="detected_at" header="Detected At" style="width: 130px">
                            <template #body="slotProps">
                                <span class="text-xs text-surface-500">{{ slotProps.data.detected_at ? new Date(slotProps.data.detected_at).toLocaleString() : '—' }}</span>
                            </template>
                        </Column>
                        <Column field="blocked" header="Action" style="width: 90px">
                            <template #body="slotProps">
                                <Tag value="BLOCKED" severity="danger" class="text-xs font-bold" />
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center py-10 text-surface-500">
                                <i class="pi pi-shield text-4xl block mb-3 text-green-400"></i>
                                <p class="font-semibold text-green-700 dark:text-green-400">No intrusion events detected.</p>
                                <p class="text-sm text-surface-400">All requests are being monitored in real time.</p>
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>

            <!-- Blocked IPs -->
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-surface-100 dark:border-surface-800">
                        <i class="pi pi-ban text-red-600"></i>
                        <span>Blocked IPs (24h)</span>
                        <Badge :value="blockedIps.length" severity="danger" class="text-xs font-bold ml-auto" />
                    </div>
                </template>
                <template #content>
                    <div v-if="blockedIps.length === 0" class="text-center py-6 text-sm text-surface-500">
                        <i class="pi pi-check-circle text-2xl text-green-500 block mb-2"></i>
                        No IPs currently blocked.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="ip in blockedIps" :key="ip" class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded-lg">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-ban text-red-500 text-sm"></i>
                                <span class="font-mono text-sm font-bold text-red-700 dark:text-red-400">{{ ip }}</span>
                            </div>
                            <Tag value="BLOCKED" severity="danger" class="text-xs" />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Encryption Status -->
            <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2 text-base font-bold pb-3 border-b border-surface-100 dark:border-surface-800">
                        <i class="pi pi-lock text-blue-600"></i>
                        Encryption & Security Features
                    </div>
                </template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="feat in securityFeatures" :key="feat.name"
                            class="flex items-center justify-between p-3 rounded-lg border transition-colors"
                            :class="feat.active ? 'bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-900' : 'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-900'">
                            <div class="flex items-center gap-2.5">
                                <i :class="`pi ${feat.icon} text-sm ${feat.active ? 'text-green-600' : 'text-red-500'}`"></i>
                                <div>
                                    <p class="text-sm font-bold" :class="feat.active ? 'text-green-800 dark:text-green-300' : 'text-red-700 dark:text-red-400'">{{ feat.name }}</p>
                                    <p class="text-xs text-surface-400">{{ feat.desc }}</p>
                                </div>
                            </div>
                            <Badge :value="feat.active ? 'ACTIVE' : 'DISABLED'" :severity="feat.active ? 'success' : 'danger'" class="text-xs font-bold" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const loading = ref(true);
const data = ref(null);

const events = computed(() => data.value?.recent_events || []);
const blockedIps = computed(() => data.value?.blocked_ips || []);

const securityCards = computed(() => [
    { label: 'SQLi Detections', value: data.value?.sqli_count ?? 0, icon: 'pi-database', color: '#ef4444', sub: 'SQL Injection attempts', danger: true },
    { label: 'XSS Detections', value: data.value?.xss_count ?? 0, icon: 'pi-code', color: '#f97316', sub: 'Cross-Site Scripting attempts', danger: true },
    { label: 'IPs Blocked', value: blockedIps.value.length, icon: 'pi-ban', color: '#dc2626', sub: 'Currently blocked (24h)', danger: true },
    { label: 'Total Events', value: data.value?.total_events ?? 0, icon: 'pi-exclamation-circle', color: '#8b5cf6', sub: 'All security detections', danger: false },
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

onMounted(loadData);
</script>
