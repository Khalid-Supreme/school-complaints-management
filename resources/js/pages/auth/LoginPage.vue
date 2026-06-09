<template>
    <form @submit.prevent="handleLogin" class="space-y-6">
        <Message v-if="authStore.error" severity="error" :closable="false" class="mb-4">
            {{ authStore.error }}
        </Message>
        
        <div>
            <label for="email" class="block text-sm font-medium text-surface-700 dark:text-surface-300">
                Email address
            </label>
            <div class="mt-1">
                <InputText id="email" v-model="form.email" type="email" required autofocus class="w-full" :class="{ 'p-invalid': errors.email }" />
                <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
            </div>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-surface-700 dark:text-surface-300">
                Password
            </label>
            <div class="mt-1">
                <InputText id="password" v-model="form.password" type="password" required class="w-full" :class="{ 'p-invalid': errors.password }" />
                <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
            </div>
        </div>

        <div>
            <Button type="submit" label="Sign in" :loading="authStore.loading" class="w-full" />
        </div>
    </form>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = reactive({
    email: '',
    password: ''
});

const errors = reactive({
    email: '',
    password: ''
});

const validate = () => {
    let valid = true;
    errors.email = '';
    errors.password = '';
    
    if (!form.email) {
        errors.email = 'Email is required';
        valid = false;
    }
    if (!form.password) {
        errors.password = 'Password is required';
        valid = false;
    }
    return valid;
};

const handleLogin = async () => {
    if (!validate()) return;
    
    const success = await authStore.login(form);
    if (success) {
        const redirect = route.query.redirect || getDashboardRoute(authStore.role);
        router.push(redirect);
    }
};

const getDashboardRoute = (roleSlug) => {
    switch(roleSlug) {
        case 'admin': return '/admin';
        case 'staff': return '/staff';
        case 'security': return '/security';
        default: return '/complainant';
    }
};
</script>
