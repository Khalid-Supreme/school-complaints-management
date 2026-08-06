<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-[1.25rem] sm:text-[1.6rem] font-semibold text-charcoal tracking-tight">Reset Your Password</h1>
            <p class="text-[0.8rem] text-slate-400 mt-1.5 font-medium leading-relaxed">
                Enter your new password below.
            </p>
        </div>

        <!-- Error Message -->
        <Message v-if="error" severity="error" :closable="false"
            class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm">
            {{ error }}
        </Message>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-5">
            <div>
                <label for="password" class="block text-sm font-medium text-charcoal/80 mb-2">
                    New Password
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 group-focus-within:text-sage-500 transition-colors duration-200">
                        <i class="pi pi-lock text-sm"></i>
                    </span>
                    <InputText id="password" v-model="form.password" type="password"
                        placeholder="••••••••" required autocomplete="new-password"
                        class="w-full !pl-11 !pr-3 sm:!pr-4 !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-2.5 sm:!py-3 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.password }" />
                </div>
                <small v-if="errors.password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.password }}</small>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-charcoal/80 mb-2">
                    Confirm New Password
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 group-focus-within:text-sage-500 transition-colors duration-200">
                        <i class="pi pi-lock text-sm"></i>
                    </span>
                    <InputText id="confirm_password" v-model="form.confirm_password" type="password"
                        placeholder="••••••••" required autocomplete="new-password"
                        class="w-full !pl-11 !pr-3 sm:!pr-4 !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-2.5 sm:!py-3 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.confirm_password }" />
                </div>
                <small v-if="errors.confirm_password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.confirm_password }}</small>
            </div>

            <Button type="submit" label="Reset Password" icon="pi pi-check-circle" :loading="loading"
                class="w-full !mt-7 !py-2 sm:!py-3 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.85rem] sm:!text-[0.9rem] !transition-all !duration-200 hover:-translate-y-[1px]"
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
    import { reactive, ref, onMounted } from 'vue';
    import { useRouter, useRoute } from 'vue-router';
    import { useAuthStore } from '../../stores/auth';
    import { hashPassword } from '../../utils/crypto';
    import InputText from 'primevue/inputtext';
    import Button from 'primevue/button';
    import Message from 'primevue/message';

    const router = useRouter();
    const route = useRoute();
    const authStore = useAuthStore();

    const loading = ref(false);
    const error = ref('');

    const form = reactive({
        password: '',
        confirm_password: ''
    });

    const errors = reactive({
        password: '',
        confirm_password: ''
    });

    const validate = () => {
        let valid = true;
        errors.password = '';
        errors.confirm_password = '';

        if (!form.password) {
            errors.password = 'Password is required';
            valid = false;
        } else if (form.password.length < 8) {
            errors.password = 'Password must be at least 8 characters';
            valid = false;
        }

        if (!form.confirm_password) {
            errors.confirm_password = 'Please confirm your password';
            valid = false;
        } else if (form.password !== form.confirm_password) {
            errors.confirm_password = 'Passwords do not match';
            valid = false;
        }

        return valid;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        loading.value = true;
        error.value = '';

        try {
            const token = route.query.token;
            const email = route.query.email;

            if (!token || !email) {
                throw new Error('Invalid or expired reset link. Please request a new one.');
            }

            // Hash password client-side before sending (matching login flow)
            const hashedPassword = await hashPassword(form.password);

            const message = await authStore.resetPassword({
                token,
                email,
                password: hashedPassword,
                password_confirmation: hashedPassword
            });

            if (message) {
                router.push({ path: '/login', query: { reset: 'success' } });
            } else if (authStore.error) {
                error.value = authStore.error;
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to reset password. The link may have expired.';
        } finally {
            loading.value = false;
        }
    };

    onMounted(() => {
        if (!route.query.token || !route.query.email) {
            error.value = 'Invalid or expired reset link. Please request a new password reset link.';
        }
    });
</script>