<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-sage-50 via-white to-emerald-50 px-4 py-10">
        <div class="w-full max-w-md md:max-w-xl lg:max-w-2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-sage-600 text-white shadow-lg mb-4">
                    <i class="pi pi-lock text-2xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Change Your Password</h1>
                <p class="text-slate-500 text-sm mt-2">For your security, you must choose a new password before continuing.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-900/5 p-6 sm:p-8">
                <Message v-if="error" severity="error" :closable="false" class="!text-sm mb-4">{{ error }}</Message>
                <Message severity="info" :closable="false" class="!text-sm mb-4">
                    <div class="flex items-start gap-2">
                        <i class="pi pi-info-circle mt-0.5"></i>
                        <span>A one-time verification code will be emailed to you to confirm this change.</span>
                    </div>
                </Message>

                <form @submit.prevent="handleSubmit" class="space-y-5">
                    <div>
                        <label for="password" class="block text-sm font-medium text-charcoal/80 mb-2">New Password</label>
                        <Password v-model="form.password" id="password" toggleMask :feedback="false" placeholder="At least 8 characters with letters, numbers and a symbol" class="w-full" :class="{ 'p-invalid': errors.password }" :disabled="loading" @input="clearFieldError('password')" />
                        <PasswordRequirements v-if="form.password.length > 0" :password="form.password" />
                        <small v-if="errors.password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.password }}</small>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-charcoal/80 mb-2">Confirm New Password</label>
                        <Password v-model="form.password_confirmation" id="password_confirmation" toggleMask :feedback="false" placeholder="Re-enter new password" class="w-full" :class="{ 'p-invalid': errors.password_confirmation || confirmationMismatch }" :disabled="loading" @input="clearFieldError('password_confirmation')" />
                        <small v-if="confirmationMismatch" class="text-red-500 block mt-1.5 text-xs font-medium">Passwords do not match</small>
                        <small v-else-if="errors.password_confirmation" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.password_confirmation }}</small>
                    </div>

                    <Button type="submit" label="Change Password" icon="pi pi-check" :loading="loading" :disabled="loading || !canSubmit" class="w-full !bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-xl !py-3" />
                </form>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">
                <i class="pi pi-shield mr-1"></i> AES-256 Bit SSL Tunnel Active
            </p>
        </div>
    </div>
</template>

<script setup>
    import { computed, onMounted, reactive, ref } from 'vue';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from '../../stores/auth';
    import PasswordRequirements from '../../components/PasswordRequirements.vue';
    import { validatePassword } from '../../utils/passwordPolicy';
    import Button from 'primevue/button';
    import Message from 'primevue/message';
    import Password from 'primevue/password';

    const router = useRouter();
    const authStore = useAuthStore();

    const loading = ref(false);
    const error = ref('');

    const form = reactive({
        password: '',
        password_confirmation: ''
    });

    const errors = reactive({
        password: '',
        password_confirmation: ''
    });

    const getDashboardRoute = (roleSlug) => {
        switch (roleSlug) {
            case 'admin':
            case 'sub_admin':
                return '/admin';
            case 'staff': return '/staff';
            case 'security': return '/security';
            case 'student': return '/student';
            case 'complaint_officer': return '/officer';
            default: return '/login';
        }
    };

    const passwordChecks = computed(() => validatePassword(form.password));
    const confirmationMismatch = computed(() => form.password_confirmation.length > 0 && form.password !== form.password_confirmation);
    const canSubmit = computed(() => passwordChecks.value.isValid && form.password === form.password_confirmation);

    const validate = () => {
        let valid = true;
        errors.password = '';
        errors.password_confirmation = '';

        if (!form.password) {
            errors.password = 'Please enter a new password';
            valid = false;
        } else if (!passwordChecks.value.isValid) {
            errors.password = 'Password does not meet all requirements';
            valid = false;
        }

        if (!form.password_confirmation) {
            errors.password_confirmation = 'Please confirm your new password';
            valid = false;
        } else if (form.password !== form.password_confirmation) {
            errors.password_confirmation = 'Passwords do not match';
            valid = false;
        }

        return valid;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        loading.value = true;
        error.value = '';

        try {
            const message = await authStore.changePassword({
                password: form.password,
                password_confirmation: form.password_confirmation,
            });

            if (message) {
                router.push({ name: 'VerifyPasswordChange' });
            } else if (authStore.error) {
                error.value = authStore.error;
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'We couldn\'t complete your password change. Please try again.';
        } finally {
            loading.value = false;
        }
    };

    // If the account is no longer required to change its password (e.g. the
    // user navigated here via the browser back button after finishing), go to
    // the dashboard instead of showing a stale form.
    onMounted(() => {
        if (authStore.isAuthenticated && !authStore.mustChangePassword) {
            router.replace(getDashboardRoute(authStore.role));
        }
    });
</script>

<script>
export default {
    name: 'ChangePasswordPage',
};
</script>
