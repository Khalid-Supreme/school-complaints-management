<template>
    <div class="space-y-8">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
            <router-link to="/admin" class="font-medium hover:text-sage-600 transition-colors">Admin</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <span class="text-charcoal font-semibold">Audit Logs</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Security Audit Log</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">Immutable record of security-sensitive actions across the system</p>
            </div>
            <div class="flex gap-2">
                <Button label="Export" icon="pi pi-download" text class="!text-sage-700 hover:!bg-sage-50" @click="exportLogs" :disabled="loading || logs.length === 0" />
                <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading" @click="fetchLogs" v-tooltip.bottom="'Refresh'" />
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 sm:p-6 shadow-xl ring-1 ring-slate-900/5">
            <div class="flex flex-col lg:flex-row gap-3 mb-5">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="pi pi-search text-sm"></i></span>
                    <InputText v-model="filters.search" placeholder="Search description, metadata or user agent..." class="w-full !pl-10 !bg-white !border-sage-200/60 !rounded-lg !py-2.5" @input="onSearch" />
                </div>
                <Select v-model="filters.action" :options="actionOptions" optionLabel="label" optionValue="value" placeholder="All actions" class="!min-w-[190px]" @change="onFilterChange" showClear />
                <InputText v-model="filters.date_from" type="date" class="!bg-white !border-sage-200/60 !rounded-lg !py-2.5 !min-w-[150px]" aria-label="From date" @change="onFilterChange" />
                <InputText v-model="filters.date_to" type="date" class="!bg-white !border-sage-200/60 !rounded-lg !py-2.5 !min-w-[150px]" aria-label="To date" @change="onFilterChange" />
                <Button v-if="hasActiveFilters" label="Clear" icon="pi pi-filter-slash" text class="!text-slate-500 hover:!bg-sage-50" @click="clearFilters" />
            </div>

            <Message v-if="error" severity="error" :closable="true" @close="error = ''">{{ error }}</Message>

            <div class="overflow-x-auto">
                <DataTable :value="logs" :loading="loading" paginator lazy :rows="meta?.per_page || 20" :totalRecords="meta?.total || 0" :first="((meta?.current_page || 1) - 1) * (meta?.per_page || 20)" @page="onPage" responsiveLayout="scroll" dataKey="id" class="p-datatable-sm" v-model:expandedRows="expandedRows">
                    <Column :expander="true" headerStyle="width: 3rem" />
                    <Column header="#" headerStyle="width: 3.5rem">
                        <template #body="slotProps">
                            <span class="text-xs text-slate-500">{{ ((meta?.current_page || 1) - 1) * (meta?.per_page || 20) + slotProps.index + 1 }}</span>
                        </template>
                    </Column>
                    <Column field="action" header="Action" style="min-width: 220px">
                        <template #body="slotProps">
                            <div class="flex items-center gap-2">
                                <i :class="`pi ${actionMeta(slotProps.data.action).icon} text-xs`" :style="{ color: actionMeta(slotProps.data.action).color }"></i>
                                <span class="text-sm font-medium text-charcoal">{{ slotProps.data.action_label || slotProps.data.action }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="created_at" header="Timestamp" style="min-width: 170px">
                        <template #body="slotProps">
                            <span class="text-xs text-slate-500">{{ slotProps.data.created_at ? formatDate(slotProps.data.created_at) : '—' }}</span>
                        </template>
                    </Column>
                    <Column field="user.full_name" header="Actor" style="min-width: 170px">
                        <template #body="slotProps">
                            <template v-if="slotProps.data.user">
                                <p class="text-sm font-medium text-charcoal">{{ slotProps.data.user.full_name || slotProps.data.user.email }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ slotProps.data.user.email }}<span v-if="slotProps.data.user.role"> · {{ roleLabel(slotProps.data.user.role.slug) }}</span></p>
                            </template>
                            <span v-else class="text-xs italic text-slate-400">Unauthenticated</span>
                        </template>
                    </Column>
                    <Column field="description" header="Details" style="min-width: 260px">
                        <template #body="slotProps">
                            <p class="text-sm text-slate-600 leading-snug">{{ slotProps.data.description || '—' }}</p>
                            <p v-if="slotProps.data.subject" class="text-xs text-slate-400 mt-1">{{ subjectLabel(slotProps.data.subject) }}</p>
                        </template>
                    </Column>
                    <Column field="ip_address" header="IP Address" style="min-width: 150px">
                        <template #body="slotProps">
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono text-xs font-medium text-slate-600 bg-slate-100 px-2 py-1 rounded-md">{{ slotProps.data.ip_address || '—' }}</span>
                                <Button v-if="slotProps.data.ip_address" icon="pi pi-copy" text rounded class="w-7 h-7 !text-slate-400 hover:!text-sage-600 hover:!bg-sage-50" v-tooltip.bottom="'Copy IP'" @click="copyIp(slotProps.data.ip_address)" />
                            </div>
                        </template>
                    </Column>
                    <template #expansion="slotProps">
                        <div class="p-4 sm:px-8 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Subject</p>
                                    <p class="text-sm text-charcoal font-medium">{{ slotProps.data.subject ? subjectLabel(slotProps.data.subject) : '—' }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">User Agent</p>
                                    <p class="text-sm text-charcoal font-medium break-all">{{ slotProps.data.user_agent || '—' }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-4 py-3 md:col-span-1">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Audit ID</p>
                                    <p class="text-sm text-charcoal font-mono">#{{ slotProps.data.id }}</p>
                                </div>
                            </div>

                            <!-- Network Context — forensic IP / proxy / request correlation -->
                            <div class="rounded-lg bg-white border border-sage-200/60 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-sage-700 mb-3 flex items-center gap-1.5">
                                    <i class="pi pi-sitemap text-sage-600"></i> Network Context
                                    <span class="ml-auto font-normal normal-case tracking-normal text-slate-400 text-[11px]">Trusted vs raw — see docs</span>
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                                    <div class="rounded-md bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Client IP <span class="normal-case font-normal">(trusted)</span></p>
                                        <p class="font-mono text-charcoal font-medium flex items-center gap-1.5">{{ slotProps.data.ip_address || slotProps.data.metadata?.client_ip || '—' }}
                                            <Button v-if="slotProps.data.ip_address" icon="pi pi-copy" text rounded class="w-6 h-6 !text-slate-400" @click="copyIp(slotProps.data.ip_address)" v-tooltip.bottom="'Copy'" />
                                        </p>
                                        <p class="text-[11px] text-slate-400">Laravel $request->ip()</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Peer IP <span class="normal-case font-normal">(direct)</span></p>
                                        <p class="font-mono text-charcoal font-medium flex items-center gap-1.5">{{ slotProps.data.peer_ip || slotProps.data.metadata?.peer_ip || '—' }}
                                            <Button v-if="slotProps.data.peer_ip || slotProps.data.metadata?.peer_ip" icon="pi pi-copy" text rounded class="w-6 h-6 !text-slate-400" @click="copyIp(slotProps.data.peer_ip || slotProps.data.metadata?.peer_ip)" v-tooltip.bottom="'Copy'" />
                                        </p>
                                        <p class="text-[11px] text-slate-400">REMOTE_ADDR — Heroku router on Heroku</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">IP Used For Decision</p>
                                        <p class="font-mono text-red-600 font-medium">{{ slotProps.data.metadata?.ip_used_for_security_decision || slotProps.data.metadata?.blocked_ip || '—' }}</p>
                                        <p class="text-[11px] text-slate-400">IPS blocking key</p>
                                    </div>
                                    <div class="rounded-md bg-amber-50 border border-amber-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">X-Forwarded-For <span class="normal-case font-normal">(raw)</span></p>
                                        <p class="font-mono text-charcoal text-xs break-all">{{ slotProps.data.x_forwarded_for || slotProps.data.metadata?.x_forwarded_for || '—' }}</p>
                                        <p class="text-[11px] text-amber-700/70">Untrusted unless proxy trusted</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">X-Real-IP / Forwarded</p>
                                        <p class="font-mono text-charcoal text-xs break-all">{{ slotProps.data.metadata?.x_real_ip || slotProps.data.metadata?.forwarded || '—' }}</p>
                                    </div>
                                    <div class="rounded-md bg-emerald-50 border border-emerald-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Request ID</p>
                                        <p class="font-mono text-charcoal font-medium flex items-center gap-1.5">{{ slotProps.data.request_id || slotProps.data.metadata?.request_id || '—' }}
                                            <Button v-if="slotProps.data.request_id || slotProps.data.metadata?.request_id" icon="pi pi-copy" text rounded class="w-6 h-6 !text-slate-400" @click="copyIp(slotProps.data.request_id || slotProps.data.metadata?.request_id)" v-tooltip.bottom="'Copy Request ID'" />
                                        </p>
                                        <p class="text-[11px] text-emerald-700/70">Heroku X-Request-ID for correlation</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Method / Route</p>
                                        <p class="font-mono text-charcoal text-xs">{{ slotProps.data.metadata?.method || '—' }} {{ slotProps.data.metadata?.route || '' }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ slotProps.data.metadata?.path || slotProps.data.metadata?.url || '' }}</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 border border-slate-100 px-3 py-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Host</p>
                                        <p class="font-mono text-charcoal text-xs break-all">{{ slotProps.data.metadata?.host || '—' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="metadataEntries(slotProps.data.metadata).length > 0" class="rounded-lg bg-slate-50 border border-slate-100 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Metadata</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="entry in metadataEntries(slotProps.data.metadata)" :key="entry.key" class="inline-flex items-center gap-1.5 bg-white border border-slate-200 rounded-md px-2.5 py-1">
                                        <span class="text-xs font-medium text-slate-500">{{ entry.key }}</span>
                                        <span class="text-xs font-mono font-medium text-charcoal">{{ entry.value }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="text-center py-10 text-slate-400">
                            <i class="pi pi-shield text-4xl block mb-3 text-sage-300"></i>
                            <p class="text-sm">No audit records found.</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import auditService from '../../services/auditService';

const toast = useToast();

const logs = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref('');
const expandedRows = ref([]);

const filters = reactive({
    search: '',
    action: null,
    date_from: null,
    date_to: null,
});

let searchTimeout = null;

const actionOptions = [
    { label: 'Login', value: 'login' },
    { label: 'Failed login', value: 'login.failed' },
    { label: 'Logout', value: 'logout' },
    { label: 'Password reset link requested', value: 'password.reset.link' },
    { label: 'Password reset', value: 'password.reset' },
    { label: 'Password changed', value: 'password.change' },
    { label: 'Email verified', value: 'email.verified' },
    { label: 'User registered', value: 'user.registered' },
    { label: 'User created', value: 'user.created' },
    { label: 'User updated', value: 'user.updated' },
    { label: 'User deleted', value: 'user.deleted' },
    { label: 'Role changed', value: 'role.changed' },
    { label: 'Complaint created', value: 'complaint.created' },
    { label: 'Complaint assigned', value: 'complaint.assigned' },
    { label: 'Complaint updated', value: 'complaint.updated' },
    { label: 'Complaint closed', value: 'complaint.closed' },
    { label: 'Complaint reopened', value: 'complaint.reopened' },
    { label: 'Complaint status changed', value: 'complaint.status.changed' },
    { label: 'Application settings updated', value: 'settings.updated' },
    { label: 'Category created', value: 'category.created' },
    { label: 'Category updated', value: 'category.updated' },
    { label: 'Category deleted', value: 'category.deleted' },
    { label: 'Department created', value: 'department.created' },
    { label: 'Department updated', value: 'department.updated' },
    { label: 'Department deleted', value: 'department.deleted' },
    { label: 'SQL injection detected', value: 'intrusion.sqli' },
    { label: 'XSS detected', value: 'intrusion.xss' },
    { label: 'IP address blocked', value: 'ip.blocked' },
    { label: 'Rate limit exceeded', value: 'rate.limit.exceeded' },
    { label: 'IP address unblocked', value: 'ip.unblocked' },
];

const actionMetaMap = {
    'login': { icon: 'pi-sign-in', color: '#047857' },
    'login.failed': { icon: 'pi-times-circle', color: '#dc2626' },
    'logout': { icon: 'pi-sign-out', color: '#64748b' },
    'password.reset.link': { icon: 'pi-key', color: '#2563eb' },
    'password.reset': { icon: 'pi-key', color: '#2563eb' },
    'password.change': { icon: 'pi-lock', color: '#2563eb' },
    'email.verified': { icon: 'pi-check-circle', color: '#047857' },
    'user.registered': { icon: 'pi-user-plus', color: '#047857' },
    'user.created': { icon: 'pi-user-plus', color: '#2563eb' },
    'user.updated': { icon: 'pi-user-edit', color: '#2563eb' },
    'user.deleted': { icon: 'pi-user-minus', color: '#dc2626' },
    'role.changed': { icon: 'pi-shield', color: '#b45309' },
    'complaint.created': { icon: 'pi-pencil', color: '#047857' },
    'complaint.assigned': { icon: 'pi-user', color: '#2563eb' },
    'complaint.updated': { icon: 'pi-pencil', color: '#64748b' },
    'complaint.closed': { icon: 'pi-check', color: '#047857' },
    'complaint.reopened': { icon: 'pi-undo', color: '#b45309' },
    'complaint.status.changed': { icon: 'pi-arrow-right', color: '#64748b' },
    'settings.updated': { icon: 'pi-cog', color: '#b45309' },
    'category.created': { icon: 'pi-plus', color: '#7e22ce' },
    'category.updated': { icon: 'pi-pencil', color: '#7e22ce' },
    'category.deleted': { icon: 'pi-trash', color: '#dc2626' },
    'department.created': { icon: 'pi-plus', color: '#7e22ce' },
    'department.updated': { icon: 'pi-pencil', color: '#7e22ce' },
    'department.deleted': { icon: 'pi-trash', color: '#dc2626' },
    'intrusion.sqli': { icon: 'pi-database', color: '#dc2626' },
    'intrusion.xss': { icon: 'pi-code', color: '#ea580c' },
    'ip.blocked': { icon: 'pi-ban', color: '#dc2626' },
    'rate.limit.exceeded': { icon: 'pi-clock', color: '#b45309' },
    'ip.unblocked': { icon: 'pi-check-circle', color: '#047857' },
};

const hasActiveFilters = computed(() =>
    filters.search || filters.action || filters.date_from || filters.date_to
);

const roleLabels = {
    admin: 'Admin',
    sub_admin: 'Sub Admin',
    complaint_officer: 'Complaint Officer',
    security: 'Security',
    student: 'Student',
    staff: 'Staff',
};

const roleLabel = (slug) => roleLabels[slug] || slug;

const actionMeta = (action) => actionMetaMap[action] || { icon: 'pi-circle', color: '#64748b' };

const subjectLabel = (subject) => {
    if (!subject?.subject_type) return '—';
    const parts = subject.subject_type.split('\\');
    const type = parts[parts.length - 1];
    return `${type} #${subject.subject_id}`;
};

const metadataEntries = (metadata) => {
    if (!metadata || typeof metadata !== 'object') return [];
    return Object.entries(metadata).map(([key, value]) => ({
        key,
        value: typeof value === 'object' ? JSON.stringify(value) : String(value ?? ''),
    }));
};

const formatDate = (value) => {
    const date = new Date(value);
    return isNaN(date) ? value : date.toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true,
    });
};

const buildParams = () => {
    const params = { page: meta.value?.current_page || 1, ...filters };
    if (!params.search) delete params.search;
    if (!params.action) delete params.action;
    if (!params.date_from) delete params.date_from;
    if (params.date_to) {
        params.date_to = `${params.date_to} 23:59:59`;
    } else {
        delete params.date_to;
    }
    return params;
};

const fetchLogs = async () => {
    loading.value = true;
    error.value = '';
    try {
        const res = await auditService.getLogs(buildParams());
        logs.value = res.data.data;
        meta.value = res.data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load audit logs.';
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    meta.value = { ...meta.value, current_page: event.page + 1 };
    fetchLogs();
};

const onSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        meta.value = { ...meta.value, current_page: 1 };
        fetchLogs();
    }, 400);
};

const onFilterChange = () => {
    meta.value = { ...meta.value, current_page: 1 };
    fetchLogs();
};

const clearFilters = () => {
    filters.search = '';
    filters.action = null;
    filters.date_from = null;
    filters.date_to = null;
    meta.value = { ...meta.value, current_page: 1 };
    fetchLogs();
};

const copyIp = async (ip) => {
    try {
        await navigator.clipboard.writeText(ip);
        toast.add({ severity: 'success', summary: 'Copied', detail: 'IP address copied to clipboard.', life: 2000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Failed', detail: 'Could not copy IP address.', life: 2000 });
    }
};

const exportLogs = () => {
    const rows = [['Timestamp', 'Action', 'Actor', 'Description', 'IP Address', 'User Agent']];
    logs.value.forEach((log) => {
        rows.push([
            formatDate(log.created_at),
            log.action_label || log.action,
            log.user ? log.user.full_name || log.user.email : 'Unauthenticated',
            log.description || '',
            log.ip_address || '',
            log.user_agent || '',
        ]);
    });
    const csv = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `audit-log-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

onMounted(fetchLogs);
</script>