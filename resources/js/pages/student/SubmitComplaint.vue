<template>
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center gap-3">
            <Button icon="pi pi-arrow-left" text rounded class="text-surface-500 hover:bg-surface-100" @click="$router.back()" />
            <div>
                <h1 class="text-3xl font-extrabold text-surface-900 dark:text-surface-0 tracking-tight">Submit Complaint</h1>
                <p class="text-surface-500 text-sm mt-0.5">All data is encrypted with AES-256 before transmission.</p>
            </div>
        </div>

        <!-- Encryption notice banner -->
        <div class="flex items-start gap-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <i class="pi pi-lock text-blue-600 text-xl mt-0.5 shrink-0"></i>
            <div class="text-sm">
                <p class="font-bold text-blue-800 dark:text-blue-300 mb-0.5">End-to-End Encryption Active</p>
                <p class="text-blue-700 dark:text-blue-400 leading-relaxed">Your complaint content (subject and description) will be encrypted using <strong>AES-256-CBC</strong> before it is stored in the database. Only authorized personnel with the encryption key can decrypt this information.</p>
            </div>
        </div>

        <Card class="border border-surface-200 dark:border-surface-800 shadow-sm">
            <template #content>
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

                    <!-- Category -->
                    <div class="space-y-1.5">
                        <label for="category" class="block text-sm font-bold text-surface-700 dark:text-surface-300">
                            Complaint Category <span class="text-red-500">*</span>
                        </label>
                        <Select
                            id="category"
                            v-model="form.category_id"
                            :options="store.categories"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select a category"
                            class="w-full"
                            :class="{ 'p-invalid': errors.category_id }"
                        />
                        <small v-if="errors.category_id" class="text-red-500 text-xs">{{ errors.category_id }}</small>
                    </div>

                    <!-- Subject/Title -->
                    <div class="space-y-1.5">
                        <label for="title" class="block text-sm font-bold text-surface-700 dark:text-surface-300">
                            Subject / Title <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            id="title"
                            v-model="form.title"
                            placeholder="Brief summary of your complaint"
                            class="w-full"
                            :class="{ 'p-invalid': errors.title }"
                        />
                        <small v-if="errors.title" class="text-red-500 text-xs">{{ errors.title }}</small>
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5">
                        <label for="description" class="block text-sm font-bold text-surface-700 dark:text-surface-300">
                            Detailed Description <span class="text-red-500">*</span>
                        </label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="6"
                            class="w-full"
                            :class="{ 'p-invalid': errors.description }"
                            placeholder="Provide a clear and detailed description of the incident. Include dates, locations, and any relevant context..."
                        />
                        <small v-if="errors.description" class="text-red-500 text-xs">{{ errors.description }}</small>
                    </div>

                    <!-- Attachment -->
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-surface-700 dark:text-surface-300">
                            Supporting Evidence (Optional)
                        </label>
                        <div
                            class="border-2 border-dashed border-surface-300 dark:border-surface-700 rounded-xl p-6 text-center transition-colors"
                            :class="{ 'border-blue-400 bg-blue-50 dark:bg-blue-950/20': dragOver }"
                            @dragover.prevent="dragOver = true"
                            @dragleave="dragOver = false"
                            @drop.prevent="onDrop"
                        >
                            <i class="pi pi-cloud-upload text-3xl text-surface-400 dark:text-surface-500 mb-2 block"></i>
                            <p class="text-sm text-surface-600 dark:text-surface-400 mb-3">Drag & drop a file here, or</p>
                            <label class="cursor-pointer">
                                <span class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Browse File</span>
                                <input type="file" class="hidden" @change="onFileChange" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.mp4" />
                            </label>
                            <p class="text-xs text-surface-400 dark:text-surface-500 mt-3">PDF, Word, Images, or Video up to 10MB</p>
                        </div>
                        <div v-if="form.file" class="flex items-center gap-3 mt-3 bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg px-4 py-2.5">
                            <i class="pi pi-paperclip text-green-600"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-green-800 dark:text-green-300 truncate">{{ form.file.name }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400">{{ (form.file.size / 1024).toFixed(1) }} KB</p>
                            </div>
                            <Button icon="pi pi-times" text rounded class="text-green-600 hover:bg-green-100 p-1 w-8 h-8" @click="form.file = null" />
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3 pt-2">
                        <Button label="Cancel" severity="secondary" @click="$router.back()" />
                        <Button
                            type="submit"
                            :label="store.loading ? 'Encrypting & Submitting...' : 'Submit Securely'"
                            icon="pi pi-shield"
                            :loading="store.loading"
                            class="bg-blue-600 hover:bg-blue-700 border-none text-white font-bold"
                        />
                    </div>
                </form>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useComplaintsStore } from '../../stores/complaints';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Message from 'primevue/message';

const router = useRouter();
const route = useRoute();
const toast = useToast();
const store = useComplaintsStore();
const authStore = useAuthStore();
const dragOver = ref(false);

// Determine correct redirect based on role/type
const basePath = authStore.user?.institution_id?.startsWith('STD-') ? '/student' : '/staff';

const form = reactive({
    category_id: null,
    title: '',
    description: '',
    file: null,
});

const errors = reactive({
    category_id: '',
    title: '',
    description: '',
});

onMounted(() => store.fetchCategories());

const validate = () => {
    let valid = true;
    errors.category_id = '';
    errors.title = '';
    errors.description = '';

    if (!form.category_id) { errors.category_id = 'Category is required'; valid = false; }
    if (!form.title.trim()) { errors.title = 'Subject is required'; valid = false; }
    if (form.description.trim().length < 20) { errors.description = 'Description must be at least 20 characters'; valid = false; }

    return valid;
};

const onFileChange = (e) => { form.file = e.target.files[0] || null; };
const onDrop = (e) => { form.file = e.dataTransfer.files[0] || null; dragOver.value = false; };

const handleSubmit = async () => {
    if (!validate()) return;

    const success = await store.submitComplaint({
        category_id: form.category_id,
        title: form.title,
        description: form.description,
    });

    if (success) {
        // Upload attachment if provided
        if (form.file && store.lastComplaintId) {
            const formData = new FormData();
            formData.append('attachment', form.file);
            try {
                await api.post(`/api/complaints/${store.lastComplaintId}/attachments`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            } catch (err) {
                console.warn('Attachment upload failed', err);
            }
        }

        toast.add({
            severity: 'success',
            summary: 'Complaint Submitted',
            detail: `Your complaint has been securely encrypted and submitted. Reference: ${store.lastReferenceNo}`,
            life: 5000,
        });
        router.push(basePath);
    }
};
</script>
