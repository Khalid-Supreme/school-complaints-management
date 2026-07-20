<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">My Complaints</h1>
            <Button label="Submit New" icon="pi pi-plus" @click="router.push('/complainant/complaints/create')" />
        </div>

        <div class="bg-white rounded-xl p-6 shadow-xl ring-1 ring-slate-900/5">
            <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

            <DataTable 
                :value="store.complaints" 
                :loading="store.loading"
                paginator 
                :rows="store.meta?.per_page || 15"
                :totalRecords="store.meta?.total || 0"
                lazy
                @page="onPage"
                responsiveLayout="scroll"
            >
                <Column field="reference_no" header="Reference No"></Column>
                <Column field="category.name" header="Category"></Column>
                <Column field="title" header="Title"></Column>
                <Column field="status" header="Status">
                    <template #body="slotProps">
                        <StatusBadge :status="slotProps.data.status" />
                    </template>
                </Column>
                <Column field="submitted_at" header="Date Submitted">
                    <template #body="slotProps">
                        {{ new Date(slotProps.data.submitted_at).toLocaleDateString() }}
                    </template>
                </Column>
                <Column header="Actions">
                    <template #body="slotProps">
                        <Button icon="pi pi-eye" text rounded aria-label="View" @click="router.push(`/complainant/complaints/${slotProps.data.id}`)" />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center p-4">No complaints found.</div>
                </template>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../../stores/complaints';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Message from 'primevue/message';
import StatusBadge from '../../../components/StatusBadge.vue';

const router = useRouter();
const store = useComplaintsStore();

onMounted(() => {
    store.fetchComplaints(1);
});

const onPage = (event) => {
    // event.page is 0-indexed in PrimeVue, API is 1-indexed
    store.fetchComplaints(event.page + 1);
};
</script>
