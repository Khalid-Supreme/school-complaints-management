<template>
    <div class="space-y-8">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
            <router-link to="/security" class="font-medium hover:text-sage-600 transition-colors">Security</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <span class="text-charcoal font-semibold">Blocked IPs</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Blocked IP Addresses</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">All IPs currently blocked by the IPS (24h). Search and unblock directly from the table.</p>
            </div>
            <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading" @click="fetchData" v-tooltip.bottom="'Refresh'" />
        </div>

        <div class="bg-white rounded-xl p-4 sm:p-6 shadow-xl ring-1 ring-slate-900/5">
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="pi pi-search text-sm"></i></span>
                    <InputText v-model="search" placeholder="Search by IP, reason or email..." class="w-full !pl-10 !bg-white !border-sage-200/60 !rounded-lg !py-2.5" @input="onSearch" />
                </div>
                <Button v-if="search" label="Clear" icon="pi pi-filter-slash" text class="!text-slate-500 hover:!bg-sage-50" @click="clearSearch" />
            </div>

            <Message v-if="error" severity="error" :closable="true" @close="error = ''">{{ error }}</Message>

            <DataTable :value="items" :loading="loading" paginator lazy :rows="meta?.per_page || 15" :totalRecords="meta?.total || 0" :first="((meta?.current_page || 1) - 1) * (meta?.per_page || 15)" @page="onPage" responsiveLayout="scroll" dataKey="ip" class="p-datatable-sm">
                <Column header="#" headerStyle="width: 3.5rem">
                    <template #body="slotProps">
                        <span class="text-xs text-slate-500">{{ ((meta?.current_page || 1) - 1) * (meta?.per_page || 15) + slotProps.index + 1 }}</span>
                    </template>
                </Column>
                <Column field="ip" header="IP Address" style="min-width: 160px">
                    <template #body="slotProps">
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-xs font-medium text-slate-700 bg-slate-100 px-2 py-1 rounded-md">{{ slotProps.data.ip }}</span>
                            <Button icon="pi pi-copy" text rounded class="w-7 h-7 !text-slate-400 hover:!text-sage-600 hover:!bg-sage-50" v-tooltip.bottom="'Copy IP'" @click="copyIp(slotProps.data.ip)" />
                        </div>
                    </template>
                </Column>
                <Column field="reason" header="Reason" style="min-width: 240px">
                    <template #body="slotProps">
                        <span class="text-sm text-slate-600">{{ slotProps.data.reason || 'Suspicious activity' }}</span>
                    </template>
                </Column>
                <Column header="User" style="min-width: 180px">
                    <template #body="slotProps">
                        <span v-if="slotProps.data.user_email" class="text-sm text-charcoal">{{ slotProps.data.user_email }} <span class="text-xs text-slate-400">#{{ slotProps.data.user_id }}</span></span>
                        <span v-else-if="slotProps.data.user_id" class="text-sm text-charcoal">#{{ slotProps.data.user_id }}</span>
                        <span v-else class="text-xs italic text-slate-400">Guest / Unknown</span>
                    </template>
                </Column>
                <Column field="blocked_at" header="Blocked At" style="min-width: 180px">
                    <template #body="slotProps">
                        <span class="text-xs text-slate-500">{{ slotProps.data.blocked_at ? new Date(slotProps.data.blocked_at).toLocaleString() : '—' }}</span>
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 120px">
                    <template #body="slotProps">
                        <Button label="Unblock" icon="pi pi-unlock" size="small" severity="danger" outlined :loading="unblockingIp === slotProps.data.ip" @click="confirmUnblock(slotProps.data.ip)" />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center py-10 text-slate-400">
                        <i class="pi pi-ban text-4xl block mb-3 text-sage-300"></i>
                        <p class="text-sm font-medium">No blocked IPs found.</p>
                        <p class="text-xs text-slate-400 mt-1">Try a different search or check back later.</p>
                    </div>
                </template>
            </DataTable>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import api from '../../services/api';

const toast = useToast();

const items = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref('');
const search = ref('');
const unblockingIp = ref(null);

let searchTimeout = null;

const fetchData = async (page = null) => {
    loading.value = true;
    error.value = '';
    try {
        const params = new URLSearchParams();
        const currentPage = page ?? meta.value?.current_page ?? 1;
        params.set('page', currentPage);
        params.set('per_page', meta.value?.per_page ?? 15);
        if (search.value.trim()) params.set('search', search.value.trim());
        const res = await api.get(`/api/security/blocked-ips?${params.toString()}`);
        items.value = res.data.data;
        meta.value = res.data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load blocked IPs.';
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    const newPage = event.page + 1;
    fetchData(newPage);
};

const onSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        meta.value = { ...meta.value, current_page: 1 };
        fetchData(1);
    }, 350);
};

const clearSearch = () => {
    search.value = '';
    meta.value = { ...meta.value, current_page: 1 };
    fetchData(1);
};

const copyIp = async (ip) => {
    try {
        await navigator.clipboard.writeText(ip);
        toast.add({ severity: 'success', summary: 'Copied', detail: 'IP copied to clipboard.', life: 2000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Failed', detail: 'Could not copy IP.', life: 2000 });
    }
};

const confirmUnblock = (ip) => {
    if (!window.confirm(`Unblock IP ${ip}? The address will be allowed to make requests again immediately.`)) return;
    unblockIp(ip);
};

const unblockIp = async (ip) => {
    unblockingIp.value = ip;
    try {
        await api.post('/api/security/ips/unblock', { ip });
        toast.add({ severity: 'success', summary: 'Unblocked', detail: `IP ${ip} has been unblocked.`, life: 3000 });
        await fetchData();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to unblock IP.', life: 3000 });
    } finally {
        unblockingIp.value = null;
    }
};

onMounted(() => fetchData(1));
</script>
