<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-[12px] bg-sage-100 border border-sage-200/50 items-center justify-center text-sage-600 mb-5"
                style="box-shadow: 0 4px 20px rgba(106, 156, 94, 0.08);">
                <i class="pi pi-lock text-xl"></i>
            </div>
            <h1 class="text-[1.6rem] font-semibold text-charcoal tracking-tight">Sign In</h1>
            <p class="text-[0.8rem] text-slate-400 mt-1.5 font-medium leading-relaxed">
                Enter your credentials to access the secure complaints terminal.
            </p>
        </div>

        <!-- Error Message -->
        <Message v-if="authStore.error" severity="error" :closable="false"
            class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm">
            {{ authStore.error }}
        </Message>
        
<!-- Form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-medium text-charcoal/80 mb-2">
                    Institution ID or Email
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 group-focus-within:text-sage-500 transition-colors duration-200">
                        <i class="pi pi-user text-sm"></i>
                    </span>
                    <InputText id="username" v-model="form.username" type="text"
                        placeholder="e.g. STD-2026-001 or staff@example.com" required autofocus
                        class="w-full !pl-10 !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.username }" />
                </div>
                <small v-if="errors.username" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.username
                    }}</small>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-charcoal/80 mb-2">
                    Security Password
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 group-focus-within:text-sage-500 transition-colors duration-200">
                        <i class="pi pi-key text-sm"></i>
                    </span>
                    <InputText id="password" v-model="form.password" type="password" placeholder="••••••••" required
                        class="w-full !pl-10 !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.password }" />
                </div>
                <small v-if="errors.password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.password
                    }}</small>
            </div>

            <Button type="submit" label="Authenticate Identity" icon="pi pi-shield" :loading="authStore.loading"
                class="w-full !mt-7 !py-3 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.9rem] !transition-all !duration-200 hover:-translate-y-[1px]"
                :style="{ boxShadow: '0 2px 12px rgba(106, 156, 94, 0.2)' }" />
        </form>

        <!-- Security Footer -->
        <div class="flex items-center justify-center gap-2 pt-2">
            <i class="pi pi-shield text-sage-400 text-xs"></i>
            <span class="text-[0.7rem] text-slate-400 font-medium tracking-wide">AES-256 Bit SSL Tunnel Active</span>
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
