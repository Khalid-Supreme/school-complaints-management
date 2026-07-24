<template>
    <div class="space-y-8">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
            <router-link to="/admin/users" class="font-medium hover:text-sage-600 transition-colors">Admin</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <router-link to="/admin/users" class="font-medium hover:text-sage-600 transition-colors">User Management</router-link>
            <i class="pi pi-chevron-right text-xs"></i>
            <span class="text-charcoal font-semibold">Staff</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-charcoal tracking-tight">Staff Management</h1>
                <p class="text-slate-400 text-sm mt-1 font-medium">Manage staff and complaint officer accounts</p>
            </div>
            <div class="flex gap-2">
                <Button label="New Staff" icon="pi pi-plus" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2.5 !px-5" @click="openCreateDialog" />
                <Button icon="pi pi-refresh" text rounded class="!text-slate-500 hover:!bg-sage-50" :loading="loading" @click="fetchStaff" v-tooltip.bottom="'Refresh'" />
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-xl ring-1 ring-slate-900/5">
            <div class="flex flex-col sm:flex-row gap-3 mb-5">
                <div class="relative flex-1">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="pi pi-search text-sm"></i></span>
                    <InputText v-model="filters.search" placeholder="Search name, email or ID..." class="w-full !pl-10 !bg-white !border-sage-200/60 !rounded-lg !py-2.5" @input="onSearch" />
                </div>
                <Select v-model="filters.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Role" class="!min-w-[170px]" @change="fetchStaff" showClear />
                <Select v-model="filters.department_id" :options="departments" optionLabel="name" optionValue="id" placeholder="Department" class="!min-w-[180px]" @change="fetchStaff" showClear />
                <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Status" class="!min-w-[130px]" @change="fetchStaff" showClear />
            </div>

            <Message v-if="error" severity="error" :closable="true" @close="error = ''">{{ error }}</Message>

            <DataTable :value="staff" :loading="loading" paginator lazy :rows="meta?.per_page || 15" :totalRecords="meta?.total || 0" @page="onPage" responsiveLayout="scroll" dataKey="id" class="p-datatable-sm">
                <Column field="institution_id" header="ID" style="width: 140px">
                    <template #body="slotProps">
                        <span class="font-mono text-xs font-medium text-slate-600 bg-sage-50 px-2 py-1 rounded-md">{{ slotProps.data.institution_id }}</span>
                    </template>
                </Column>
                <Column field="name" header="Name" />
                <Column field="email" header="Email" />
                <Column field="role.name" header="Role">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.role.name" :severity="slotProps.data.role.slug === 'complaint_officer' ? 'info' : 'warn'" class="text-xs font-medium" />
                    </template>
                </Column>
                <Column field="title" header="Title" style="width: 80px" />
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
                <Column header="Actions" style="width: 160px">
                    <template #body="slotProps">
                        <Button icon="pi pi-users" text rounded class="w-9 h-9 !text-sage-600 hover:!bg-sage-50" @click="openRoleDialog(slotProps.data)" v-tooltip.bottom="'Change Role'" />
                        <Button icon="pi pi-pencil" text rounded class="w-9 h-9 !text-blue-600 hover:!bg-blue-50" @click="openEditDialog(slotProps.data)" v-tooltip.bottom="'Edit'" />
                        <Button icon="pi pi-trash" text rounded class="w-9 h-9 !text-red-500 hover:!bg-red-50" @click="confirmDelete(slotProps.data)" v-tooltip.bottom="'Delete'" />
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center py-8 text-slate-400">
                        <i class="pi pi-briefcase text-4xl block mb-3"></i>
                        <p class="text-sm">No staff found.</p>
                    </div>
                </template>
            </DataTable>
        </div>

        <Dialog v-model:visible="formDialogVisible" :header="isEditing ? 'Edit Staff' : 'New Staff'" modal :style="{ width: '28rem' }" :closable="!formLoading" @after-hide="resetForm">
            <div class="space-y-4">
                <Message v-if="formError" severity="error" :closable="false" class="!text-sm">{{ formError }}</Message>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Full Name</label>
                    <InputText v-model="form.full_name" placeholder="e.g. John Doe" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :class="{ 'p-invalid': formErrors.full_name }" :disabled="formLoading" />
                    <small v-if="formErrors.full_name" class="text-red-500 block mt-1 text-xs">{{ formErrors.full_name[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Role</label>
                    <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" placeholder="Select role" class="w-full" :class="{ 'p-invalid': formErrors.role }" :disabled="formLoading" />
                    <small v-if="formErrors.role" class="text-red-500 block mt-1 text-xs">{{ formErrors.role[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Email Address</label>
                    <InputText v-model="form.email" type="email" placeholder="e.g. johndoe@example.com" class="w-full !bg-white !border-sage-200/60 !rounded-lg !py-2.5 !px-4" :class="{ 'p-invalid': formErrors.email }" :disabled="formLoading" />
                    <small v-if="formErrors.email" class="text-red-500 block mt-1 text-xs">{{ formErrors.email[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Title</label>
                    <Select v-model="form.title" :options="titleOptions" optionLabel="label" optionValue="value" placeholder="Select title" class="w-full" :class="{ 'p-invalid': formErrors.title }" :disabled="formLoading" />
                    <small v-if="formErrors.title" class="text-red-500 block mt-1 text-xs">{{ formErrors.title[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Gender</label>
                    <Select v-model="form.gender" :options="genderOptions" optionLabel="label" optionValue="value" placeholder="Select gender" class="w-full" :class="{ 'p-invalid': formErrors.gender }" :disabled="formLoading" />
                    <small v-if="formErrors.gender" class="text-red-500 block mt-1 text-xs">{{ formErrors.gender[0] }}</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">Department</label>
                    <Select v-model="form.department" :options="departments" optionLabel="name" optionValue="id" placeholder="Select department" class="w-full" :class="{ 'p-invalid': formErrors.department }" :disabled="formLoading" filter />
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
                <Button :label="isEditing ? 'Update' : 'Create Staff'" :loading="formLoading" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2.5 !px-5" @click="handleSubmit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="roleDialogVisible" header="Change Role" modal :style="{ width: '400px' }">
            <div class="space-y-4">
                <p class="text-sm text-slate-600">
                    Change role for <span class="font-semibold text-charcoal">{{ userToChangeRole?.name }}</span>.
                </p>
                <div>
                    <label class="block text-sm font-medium text-charcoal/80 mb-2">New Role</label>
                    <Select v-model="roleForm.role" :options="assignableRoles" optionLabel="label" optionValue="value" placeholder="Select role" class="w-full" :disabled="roleLoading" />
                    <small v-if="roleError" class="text-red-500 block mt-1 text-xs">{{ roleError }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50" :disabled="roleLoading" @click="roleDialogVisible = false" />
                <Button label="Update Role" :loading="roleLoading" class="!bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-lg !py-2.5 !px-5" @click="handleRoleChange" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteDialogVisible" modal :style="{ width: '400px' }" header="Confirm Delete">
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

const staff = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref('');

const departments = ref([]);

const roleOptions = [
    { label: 'Staff', value: 'staff' },
    { label: 'Complaint Officer', value: 'complaint_officer' },
];

const assignableRoles = [
    { label: 'Staff', value: 'staff' },
    { label: 'Complaint Officer', value: 'complaint_officer' },
];

const titleOptions = [
    { label: 'Mr.', value: 'Mr.' },
    { label: 'Mrs.', value: 'Mrs.' },
    { label: 'Miss', value: 'Miss' },
    { label: 'Dr.', value: 'Dr.' },
    { label: 'Prof.', value: 'Prof.' },
];

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
    role: null,
    department_id: null,
    status: null,
});

let searchTimeout = null;

onMounted(async () => {
    await fetchDepartments();
    await fetchStaff();
});

const fetchDepartments = async () => {
    try {
        const res = await api.get('/api/departments');
        departments.value = res.data.data;
    } catch {
        // silent
    }
};

const fetchStaff = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = { page: meta.value?.current_page || 1, ...filters };
        if (!params.search) delete params.search;
        if (!params.role) delete params.role;
        if (!params.department_id) delete params.department_id;
        if (!params.status) delete params.status;
        const res = await userService.getStaff(params);
        staff.value = res.data.data;
        meta.value = res.data.meta;
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to load staff.';
    } finally {
        loading.value = false;
    }
};

const onPage = (event) => {
    meta.value = { ...meta.value, current_page: event.page + 1 };
    fetchStaff();
};

const onSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        meta.value = { ...meta.value, current_page: 1 };
        fetchStaff();
    }, 400);
};

const formDialogVisible = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const formLoading = ref(false);
const formError = ref('');
const formErrors = ref({});
const form = reactive({
    role: null,
    full_name: '',
    email: '',
    title: null,
    gender: null,
    department: null,
    password: '',
});

const resetForm = () => {
    form.role = null;
    form.full_name = '';
    form.email = '';
    form.title = null;
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
    form.role = user.role.slug;
    form.full_name = user.name;
    form.email = user.email;
    form.title = user.title;
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
                title: form.title,
                gender: form.gender,
                department: form.department,
            };
            if (form.password) {
                payload.password = await hashPassword(form.password);
            }
            await userService.update(editingId.value, payload);
            toast.add({ severity: 'success', summary: 'Updated', detail: 'Staff updated successfully.', life: 3000 });
        } else {
            await userService.create({
                role: form.role,
                full_name: form.full_name.trim(),
                email: form.email.trim(),
                title: form.title,
                gender: form.gender,
                department: form.department,
                password: await hashPassword(form.password),
            });
            toast.add({ severity: 'success', summary: 'Created', detail: 'Staff created successfully.', life: 3000 });
        }
        formDialogVisible.value = false;
        await fetchStaff();
    } catch (e) {
        if (e.response?.data?.errors) {
            formErrors.value = e.response.data.errors;
        }
        formError.value = e.response?.data?.message || 'An error occurred.';
    } finally {
        formLoading.value = false;
    }
};

const roleDialogVisible = ref(false);
const roleLoading = ref(false);
const roleError = ref('');
const userToChangeRole = ref(null);
const roleForm = reactive({ role: null });

const openRoleDialog = (user) => {
    userToChangeRole.value = user;
    roleForm.role = user.role.slug;
    roleError.value = '';
    roleDialogVisible.value = true;
};

const handleRoleChange = async () => {
    if (!roleForm.role) return;
    roleLoading.value = true;
    roleError.value = '';
    try {
        await userService.updateRole(userToChangeRole.value.id, roleForm.role);
        toast.add({ severity: 'success', summary: 'Role Updated', detail: 'Staff role changed successfully.', life: 3000 });
        roleDialogVisible.value = false;
        await fetchStaff();
    } catch (e) {
        roleError.value = e.response?.data?.errors?.role?.[0] || 'Failed to update role.';
    } finally {
        roleLoading.value = false;
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
        toast.add({ severity: 'success', summary: 'Deleted', detail: 'Staff deleted successfully.', life: 3000 });
        deleteDialogVisible.value = false;
        userToDelete.value = null;
        await fetchStaff();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.response?.data?.message || 'Failed to delete staff.', life: 3000 });
    } finally {
        deleteLoading.value = false;
    }
};
</script>
