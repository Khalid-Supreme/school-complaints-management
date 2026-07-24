<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-[12px] bg-sage-100 border border-sage-200/50 items-center justify-center text-sage-600 mb-5"
                style="box-shadow: 0 4px 20px rgba(106, 156, 94, 0.08);">
                <i class="pi pi-key text-xl"></i>
            </div>
            <h1 class="text-[1.6rem] font-semibold text-charcoal tracking-tight">Forgot Password?</h1>
            <p class="text-[0.8rem] text-slate-400 mt-1.5 font-medium leading-relaxed">
                Enter your email and we'll send you a secure link to reset your password.
            </p>
        </div>

        <!-- Error Message -->
        <Message v-if="error" severity="error" :closable="false"
            class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm">
            {{ error }}
        </Message>

        <!-- Success Message -->
        <Message v-if="success" severity="success" :closable="false"
            class="!bg-green-50/50 !border-green-100/50 !text-green-700 !rounded-xl !text-sm">
            {{ success }}
        </Message>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-charcoal/80 mb-2">
                    Email Address
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 group-focus-within:text-sage-500 transition-colors duration-200">
                        <i class="pi pi-envelope text-sm"></i>
                    </span>
                    <InputText id="email" v-model="form.email" type="email" 
                        placeholder="you@example.com" required autocomplete="email"
                        class="w-full !pl-10 !bg-white !border-sage-200/60 !text-charcoal !text-[0.92rem] !rounded-lg !py-3 !px-4 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.email }" />
                </div>
                <small v-if="errors.email" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.email }}</small>
            </div>

            <Button type="submit" label="Send Password Reset Link" icon="pi pi-paper-plane" :loading="loading"
                class="w-full !mt-7 !py-3 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.9rem] !transition-all !duration-200 hover:-translate-y-[1px]"
                :style="{ boxShadow: '0 2px 12px rgba(106, 156, 94, 0.2)' }" />
        </form>

        <div class="flex justify-center pt-1">
            <p class="text-sm text-slate-400 font-medium">
                Remember your password?
                <router-link to="/login" class="text-sage-600 hover:text-sage-700 font-semibold cursor-pointer underline-offset-2 hover:underline">
                    Sign In
                </router-link>
            </p>
        </div>

        <!-- Security Footer -->
        <div class="flex items-center justify-center gap-2 pt-2">
            <i class="pi pi-shield text-sage-400 text-xs"></i>
            <span class="text-[0.7rem] text-slate-400 font-medium tracking-wide">AES-256 Bit SSL Tunnel Active</span>
        </div>
    </div>
</template>

<script setup>
    import { reactive, ref } from 'vue';
    import { useRouter, useRoute } from 'vue-router';
    import { useAuthStore } from '../../stores/auth';
    import InputText from 'primevue/inputtext';
    import Button from 'primevue/button';
    import Message from 'primevue/message';

    const router = useRouter();
    const route = useRoute();
    const authStore = useAuthStore();

    const loading = ref(false);
    const error = ref('');
    const success = ref('');

    const form = reactive({
        email: route.query.email || ''
    });

    const errors = reactive({
        email: ''
    });

    const validate = () => {
        let valid = true;
        errors.email = '';

        if (!form.email) {
            errors.email = 'Email is required';
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
            errors.email = 'Please enter a valid email address';
            valid = false;
        }

        return valid;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        loading.value = true;
        error.value = '';
        success.value = '';

        try {
            const message = await authStore.forgotPassword(form.email);
            if (message) {
                success.value = message;
                form.email = '';
            } else {
                error.value = authStore.error || 'Failed to send reset link';
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'An unexpected error occurred';
        } finally {
            loading.value = false;
        }
    };
</script>