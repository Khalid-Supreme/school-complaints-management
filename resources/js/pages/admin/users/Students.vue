<template>
    <div class="space-y-8">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
            <router-link to="/admin/users" class="font-medium hover:text-sage-600 transition-colors">Admin</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <router-link to="/admin/users" class="font-medium hover:text-sage-600 transition-colors">User Management</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <span class="text-charcoal font-semibold">Students</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Student Management</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">Manage all registered student accounts</p>
            </div>
            <div class="flex gap-2">
                <Button label="New Student" icon="pi pi-plus" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2 sm:!py-2.5 !px-4 sm:!px-5" @click="openCreateDialog" />
                <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading" @click="fetchStudents" v-tooltip.bottom="'Refresh'" />
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 sm:p-6 shadow-xl ring-1 ring-slate-900/5">
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="pi pi-search text-sm"></i></span>
                    <InputText v-model="filters.search" placeholder="Search name, email or ID..." class="w-full !pl-10 !bg-white !border-sage-200/60 !rounded-lg !py-2.5" @input="onSearch" />
                </div>
                <Select v-model="filters.department_id" :options="departments" optionLabel="name" optionValue="id" placeholder="Department" class="!min-w-[180px]" @change="fetchStudents" showClear />
                <Select v-model="filters.gender" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Gender" class="!min-w-[130px]" @change="fetchStudents" showClear />
                <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Status" class="!min-w-[130px]" @change="fetchStudents" showClear />
            </div>

            <Message v-if="error" severity="error" :closable="true" @close="error = ''">{{ error }}</Message>

            <div class="overflow-x-auto">
            <DataTable :value="students" :loading="loading" paginator lazy :rows="meta?.per_page || 15" :totalRecords="meta?.total || 0" @page="onPage" responsiveLayout="scroll" dataKey="id" class="p-datatable-sm">
                    <Column field="institution_id" header="Matric No." style="width: 180px">
   
                    <template #body="slotProps">
                            <div class="flex items-center gap-1.5">
   
                                <span
                                    class="font-mono text-xs font-medium text-slate-600 bg-sage-50 px-2 py-1 rounded-md">{{
                                    slotProps.data.institution_id }}</span>
   
                                <Button icon="pi pi-copy" text rounded
                                    class="w-7 h-7 !text-slate-400 hover:!text-sage-600 hover:!bg-sage-50"
                                    v-tooltip.bottom="'Copy Matric No.'"
                                    @click="copyToClipboard(slotProps.data.institution_id)" />
                            </div>
   
                    </template>
                </Column>
                <Column field="name" header="Name" />
                <Column field="email" header="Email" />
                <Column field="gender" header="Gender" style="width: 90px">
                    <template #body="slotProps">
                        <span class="capitalize">{{ slotProps.data.gender }}</span>
                    </template>
                </Column>
                <Column field="department.name" header="Department" />
                <Column field="is_active" header="Status" style="width: 100px">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.is_active ? 'Active' : 'Inactive'" :severity="slotProps.data.is_active ? 'success' : 'danger'" class="text-xs font-medium" />
                    </template>
                </Column>
                <Column header="Actions" style="width: 120px">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" text rounded class="w-9 h-9 !text-blue-600 hover:!bg-blue-50" @click="openEditDialog(slotProps.data)" v-tooltip.bottom="'Edit'" />
                        <Button icon="pi pi-trash" text rounded class="w-9 h-9 !text-red-500 hover:!bg-red-50" @click="confirmDelete(slotProps.data)" v-tooltip.bottom="'Delete'" />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center py-8 text-slate-400">
                        <i class="pi pi-users text-4xl block mb-3"></i>
                        <p class="text-sm">No students found.</p>
                    </div>
                </template>
            </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="formDialogVisible" :header="isEditing ? 'Edit Student' : 'New Student'" modal :style="{ width: '28rem' }" :breakpoints="{ '640px': '95vw', '768px': '28rem' }" :closable="!formLoading" @after-hide="resetForm">
            <div class="space-y-4">
                <Message v-if="formError" severity="error" :closable="false" class="!text-sm">{{ formError }}</Message>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Full Name</label>
                    <InputText v-model="form.full_name" placeholder="e.g. John Doe" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :class="{ 'p-invalid': formErrors.full_name }" :disabled="formLoading" />
                    <small v-if="formErrors.full_name" class="text-red-500 block mt-1 text-xs">{{ formErrors.full_name[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Email Address</label>
                    <InputText v-model="form.email" type="email" placeholder="e.g. johndoe@example.com" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :class="{ 'p-invalid': formErrors.email }" :disabled="formLoading" />
                    <small v-if="formErrors.email" class="text-red-500 block mt-1 text-xs">{{ formErrors.email[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Gender</label>
                    <Select v-model="form.gender" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Select gender" class="w-full" :class="{ 'p-invalid': formErrors.gender }" :disabled="formLoading" />
                    <small v-if="formErrors.gender" class="text-red-500 block mt-1 text-xs">{{ formErrors.gender[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Department</label>
                    <Select v-model="form.department" :options="academicDepartments" optionLabel="name" optionValue="id" placeholder="Select academic department" class="w-full" :class="{ 'p-invalid': formErrors.department }" :disabled="formLoading" filter />
                    <small v-if="formErrors.department" class="text-red-500 block mt-1 text-xs">{{ formErrors.department[0] }}</small>
                </div>

                <div v-if="!isEditing">
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Password</label>
                    <InputText v-model="form.password" type="password" placeholder="Min. 8 characters" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :class="{ 'p-invalid': formErrors.password }" :disabled="formLoading" />
                    <small v-if="formErrors.password" class="text-red-500 block mt-1 text-xs">{{ formErrors.password[0] }}</small>
                </div>
                <div v-else>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Password <span class="text-slate-400 font-normal">(leave blank to keep current)</span></label>
                    <InputText v-model="form.password" type="password" placeholder="New password" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :class="{ 'p-invalid': formErrors.password }" :disabled="formLoading" />
                    <small v-if="formErrors.password" class="text-red-500 block mt-1 text-xs">{{ formErrors.password[0] }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50" :disabled="formLoading" @click="formDialogVisible = false" />
                <Button :label="isEditing ? 'Update' : 'Create Student'" :loading="formLoading" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2 sm:!py-2.5 !px-4 sm:!px-5" @click="handleSubmit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteDialogVisible" modal :style="{ width: '400px' }" :breakpoints="{ '640px': '95vw', '768px': '400px' }" header="Confirm Delete">
            <p class="text-sm text-slate-600">
                Are you sure you want to delete <span class="font-semibold text-charcoal">{{ userToDelete?.name }}</span>?
                This action cannot be undone.
            </p>
            <template #footer>
                <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50" :disabled="deleteLoading" @click="deleteDialogVisible = false" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" :loading="deleteLoading" class="!rounded-lg !font-semibold" @click="handleDelete" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import api from '../../../services/api';
import userService from '../../../services/userService';
import { hashPassword } from '../../../utils/crypto';

const toast = useToast();

const students = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref('');

const departments = ref([]);
const allDepartments = ref([]);

const genderOptions = [
    { label: 'Male', value: 'male' },
    { label: 'Female', value: 'female' },
];

const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
];

const filters = reactive({
    search: '',
    department_id: null,
    gender: null,
    status: null,
});

let searchTimeout = null;

const academicDepartments = computed(() =>
    allDepartments.value.filter(d => d.type === 'academic')
);

onMounted(async () => {
    await fetchDepartments();
    await fetchStudents();
});

const fetchDepartments = async () => {
    try {
        const res = await api.get('/api/departments');
        allDepartments.value = res.data.data;
        departments.value = res.data.data;
    } catch {
        // silent
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        toast.add({ severity: 'success', summary: 'Copied', detail: 'ID copied to clipboard.', life: 2000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Failed', detail: 'Could not copy ID.', life: 2000 });
    }
};

const fetchStudents = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = { page: meta.value?.current_page || 1, ...filters };
        if (!params.search) delete params.search;
        if (!params.department_id) delete params.department_id;
        if (!params.gender) delete params.gender;
        if (!params.status) delete params.status;
        const res = await userService.getStudents(params);
        students.value = res.data.data;
        meta.value = res.data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load students.';
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    meta.value = { ...meta.value, current_page: event.page + 1 };
    fetchStudents();
};

const onSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        meta.value = { ...meta.value, current_page: 1 };
        fetchStudents();
    }, 400);
};

const formDialogVisible = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const formLoading = ref(false);
const formError = ref('');
const formErrors = ref({});
const form = reactive({
    full_name: '',
    email: '',
    gender: null,
    department: null,
    password: '',
});

const resetForm = () => {
    form.full_name = '';
    form.email = '';
    form.gender = null;
    form.department = null;
    form.password = '';
    formError.value = '';
    formErrors.value = {};
    isEditing.value = false;
    editingId.value = null;
};

const openCreateDialog = () => {
    resetForm();
    formDialogVisible.value = true;
};

const openEditDialog = async (user) => {
    resetForm();
    isEditing.value = true;
    editingId.value = user.id;
    form.full_name = user.name;
    form.email = user.email;
    form.gender = user.gender;
    form.department = user.department_id;
    formDialogVisible.value = true;
};

const handleSubmit = async () => {
    formLoading.value = true;
    formError.value = '';
    formErrors.value = {};

    if (form.password && form.password.length < 8) {
        formErrors.value = { password: ['Password must be at least 8 characters.'] };
        formLoading.value = false;
        return;
    }

    try {
        if (isEditing.value) {
            const payload = {
                full_name: form.full_name.trim(),
                email: form.email.trim(),
                gender: form.gender,
                department: form.department,
            };
            if (form.password) {
                payload.password = await hashPassword(form.password);
            }
            await userService.update(editingId.value, payload);
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Student updated successfully.', life: 3000 });
        } else {
            await userService.create({
                role: 'student',
                full_name: form.full_name.trim(),
                email: form.email.trim(),
                gender: form.gender,
                department: form.department,
                password: await hashPassword(form.password),
            });
            toast.add({ severity: 'success', summary: 'Created', detail: 'Student created successfully.', life: 3000 });
        }
        formDialogVisible.value = false;
        await fetchStudents();
    } catch (e) {
        if (e.response?.data?.errors) {
            formErrors.value = e.response.data.errors;
        }
        formError.value = e.response?.data?.message || 'An error occurred.';
    } finally {
        formLoading.value = false;
    }
};

const deleteDialogVisible = ref(false);
const deleteLoading = ref(false);
const userToDelete = ref(null);

const confirmDelete = (user) => {
    userToDelete.value = user;
    deleteDialogVisible.value = true;
};

const handleDelete = async () => {
    deleteLoading.value = true;
    try {
        await userService.destroy(userToDelete.value.id);
        toast.add({ severity: 'success', summary: 'Deleted', detail: 'Student deleted successfully.', life: 3000 });
        deleteDialogVisible.value = false;
        userToDelete.value = null;
        await fetchStudents();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to delete student.', life: 3000 });
    } finally {
        deleteLoading.value = false;
    }
};
</script>
