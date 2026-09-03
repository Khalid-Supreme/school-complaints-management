<template>
    <div class="space-y-8">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
            <router-link to="/security" class="font-medium hover:text-sage-600 transition-colors">Security</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <span class="text-charcoal font-semibold">Blocked Users</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Blocked User Accounts</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">All user accounts currently blocked by the hybrid IPS (24h). Search and unblock directly from the table.</p>
            </div>
            <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading" @click="fetchData" v-tooltip.bottom="'Refresh'" />
        </div>

        <div class="bg-white rounded-xl p-4 sm:p-6 shadow-xl ring-1 ring-slate-900/5">
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="pi pi-search text-sm"></i></span>
                    <InputText v-model="search" placeholder="Search by email, ID or reason..." class="w-full !pl-10 !bg-white !border-sage-200/60 !rounded-lg !py-2.5" @input="onSearch" />
                </div>
                <Button v-if="search" label="Clear" icon="pi pi-filter-slash" text class="!text-slate-500 hover:!bg-sage-50" @click="clearSearch" />
            </div>

            <Message v-if="error" severity="error" :closable="true" @close="error = ''">{{ error }}</Message>

            <DataTable :value="items" :loading="loading" paginator lazy :rows="meta?.per_page || 15" :totalRecords="meta?.total || 0" :first="((meta?.current_page || 1) - 1) * (meta?.per_page || 15)" @page="onPage" responsiveLayout="scroll" dataKey="user_id" class="p-datatable-sm">
                <Column header="#" headerStyle="width: 3.5rem">
                    <template #body="slotProps">
                        <span class="text-xs text-slate-500">{{ ((meta?.current_page || 1) - 1) * (meta?.per_page || 15) + slotProps.index + 1 }}</span>
                    </template>
                </Column>
                <Column field="user_id" header="User" style="min-width: 200px">
                    <template #body="slotProps">
                        <div class="flex flex-col">
                            <span class="font-mono text-xs font-medium text-slate-700">#{{ slotProps.data.user_id }}</span>
                            <span class="text-sm text-charcoal">{{ slotProps.data.email || '—' }}</span>
                            <span v-if="slotProps.data.institution_id" class="text-xs text-slate-400">{{ slotProps.data.institution_id }}</span>
                        </div>
                    </template>
                </Column>
                <Column field="ip" header="IP at block" style="min-width: 180px">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-1">
                            <span class="font-mono text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded-md w-fit">{{ slotProps.data.ip || '—' }}</span>
                            <span v-if="slotProps.data.x_forwarded_for" class="font-mono text-[11px] text-amber-700 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded truncate max-w-[170px]" v-tooltip.bottom="slotProps.data.x_forwarded_for">XFF: {{ slotProps.data.x_forwarded_for }}</span>
                            <span v-else class="text-[11px] text-slate-300">XFF: —</span>
                        </div>
                    </template>
                </Column>
                <Column field="reason" header="Reason" style="min-width: 240px">
                    <template #body="slotProps">
                        <span class="text-sm text-slate-600">{{ slotProps.data.reason || 'Suspicious activity' }}</span>
                    </template>
                </Column>
                <Column field="blocked_at" header="Blocked At" style="min-width: 180px">
                    <template #body="slotProps">
                        <span class="text-xs text-slate-500">{{ slotProps.data.blocked_at ? new Date(slotProps.data.blocked_at).toLocaleString() : '—' }}</span>
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 120px">
                    <template #body="slotProps">
                        <Button label="Unblock" icon="pi pi-unlock" size="small" severity="danger" outlined :loading="unblockingUser === slotProps.data.user_id" @click="confirmUnblock(slotProps.data.user_id)" />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center py-10 text-slate-400">
                        <i class="pi pi-user text-4xl block mb-3 text-sage-300"></i>
                        <p class="text-sm font-medium">No blocked users found.</p>
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
const unblockingUser = ref(null);

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
        const res = await api.get(`/api/security/blocked-users?${params.toString()}`);
        items.value = res.data.data;
        meta.value = res.data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load blocked users.';
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

const confirmUnblock = (userId) => {
    if (!window.confirm(`Unblock user #${userId}? The account will be allowed to make requests again immediately.`)) return;
    unblockUser(userId);
};

const unblockUser = async (userId) => {
    unblockingUser.value = userId;
    try {
        await api.post('/api/security/users/unblock', { user_id: userId });
        toast.add({ severity: 'success', summary: 'Unblocked', detail: `User #${userId} has been unblocked.`, life: 3000 });
        await fetchData();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to unblock user.', life: 3000 });
    } finally {
        unblockingUser.value = null;
    }
};

onMounted(() => fetchData(1));
</script>
