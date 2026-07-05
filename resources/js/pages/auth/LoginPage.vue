<template>
    <div class="space-y-6">
        <div class="text-center mb-6">
            <div class="inline-flex w-16 h-16 rounded-full bg-blue-500/10 border border-blue-500/20 items-center justify-center text-blue-500 mb-4 shadow-inner">
                <i class="pi pi-lock text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Sign In</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Enter your credentials to access the secure complaints terminal.</p>
        </div>

        <Message v-if="authStore.error" severity="error" :closable="false" class="mb-4">
            {{ authStore.error }}
        </Message>
        
        <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Institution ID or Email
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="pi pi-user text-sm"></i>
                    </span>
                    <InputText id="username" v-model="form.username" type="text" placeholder="e.g. STD-2026-001 or staff@example.com" required autofocus class="w-full pl-10" :class="{ 'p-invalid': errors.username }" />
                </div>
                <small v-if="errors.username" class="p-error block mt-1">{{ errors.username }}</small>
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                    Security Password
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <i class="pi pi-key text-sm"></i>
                    </span>
                    <InputText id="password" v-model="form.password" type="password" placeholder="••••••••" required class="w-full pl-10" :class="{ 'p-invalid': errors.password }" />
                </div>
                <small v-if="errors.password" class="p-error block mt-1">{{ errors.password }}</small>
            </div>

            <Button type="submit" label="Authenticate Identity" icon="pi pi-shield" :loading="authStore.loading" class="w-full mt-6 py-2.5 bg-blue-600 hover:bg-blue-700 border-none rounded-lg text-white font-bold transition-all shadow-md shadow-blue-500/20" />
        </form>

        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 text-center">
            <span class="text-xs text-slate-400 font-medium">Encryption Status: AES-256 Bit SSL Tunnel</span>
        </div>
    </div>
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
    username: '',
    password: ''
});

const errors = reactive({
    username: '',
    password: ''
});

const validate = () => {
    let valid = true;
    errors.username = '';
    errors.password = '';
    
    if (!form.username) {
        errors.username = 'Institution ID or Email is required';
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
        const redirect = route.query.redirect || getDashboardRoute(authStore.role, authStore.user);
        router.push(redirect);
    }
};

const getDashboardRoute = (roleSlug, user) => {
    switch(roleSlug) {
        case 'admin': return '/admin';
        case 'staff': return '/staff';
        case 'security': return '/security';
        case 'student': return '/student';
        case 'complaint_officer': return '/officer';
        default: return '/login';
    }
};
</script>
