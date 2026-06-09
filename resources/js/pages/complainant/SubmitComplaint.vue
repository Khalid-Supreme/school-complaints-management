<template>
    <div class="max-w-3xl mx-auto space-y-6">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">Submit New Complaint</h1>
        
        <div class="bg-surface-0 dark:bg-surface-800 p-6 rounded-lg shadow">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <Message v-if="store.error" severity="error" :closable="false">{{ store.error }}</Message>

                <div class="field">
                    <label for="category" class="block font-medium mb-2">Category</label>
                    <Dropdown id="category" v-model="form.category_id" :options="store.categories" optionLabel="name" optionValue="id" placeholder="Select a Category" class="w-full" :class="{'p-invalid': errors.category_id}" />
                    <small v-if="errors.category_id" class="p-error block mt-1">{{ errors.category_id }}</small>
                </div>

                <div class="field">
                    <label for="title" class="block font-medium mb-2">Title</label>
                    <InputText id="title" v-model="form.title" class="w-full" :class="{'p-invalid': errors.title}" placeholder="Brief summary of your complaint" />
                    <small v-if="errors.title" class="p-error block mt-1">{{ errors.title }}</small>
                </div>

                <div class="field">
                    <label for="description" class="block font-medium mb-2">Description</label>
                    <Textarea id="description" v-model="form.description" rows="5" class="w-full" :class="{'p-invalid': errors.description}" placeholder="Provide detailed information..." />
                    <small v-if="errors.description" class="p-error block mt-1">{{ errors.description }}</small>
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" label="Cancel" severity="secondary" @click="router.push('/complainant')" />
                    <Button type="submit" label="Submit" :loading="store.loading" />
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useComplaintsStore } from '../../../stores/complaints';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';

const router = useRouter();
const store = useComplaintsStore();

const form = reactive({
    category_id: null,
    title: '',
    description: ''
});

const errors = reactive({
    category_id: '',
    title: '',
    description: ''
});

onMounted(() => {
    store.fetchCategories();
});

const validate = () => {
    let valid = true;
    errors.category_id = '';
    errors.title = '';
    errors.description = '';

    if (!form.category_id) {
        errors.category_id = 'Category is required';
        valid = false;
    }
    if (!form.title.trim()) {
        errors.title = 'Title is required';
        valid = false;
    }
    if (!form.description.trim()) {
        errors.description = 'Description is required';
        valid = false;
    }

    return valid;
};

const handleSubmit = async () => {
    if (!validate()) return;
    
    const success = await store.submitComplaint(form);
    if (success) {
        router.push('/complainant/complaints');
    }
};
</script>
