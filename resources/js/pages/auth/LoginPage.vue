<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="text-center">
            <h3 class="text-[1.1rem] sm:text-[1.25rem] font-semibold text-charcoal tracking-tight">Sign In</h3>
        </div>

        <!-- Success Messages -->
        <Message v-if="route.query.verified" severity="success" :closable="false"
            class="!bg-green-50/50 !border-green-100/50 !text-green-700 !rounded-xl !text-sm">
            Email verified successfully. Your {{ loginIdLabel }} has been filled in below.
        </Message>

        <Message v-if="route.query.reset === 'success'" severity="success" :closable="false"
            class="!bg-green-50/50 !border-green-100/50 !text-green-700 !rounded-xl !text-sm">
            Password has been reset successfully. You can now sign in with your new password.
        </Message>

        <!-- Error Message -->
        <Message v-if="authStore.error" severity="error" :closable="false"
            class="!bg-red-50/50 !border-red-100/50 !text-red-600 !rounded-xl !text-sm">
            {{ authStore.error }}
            <template #icon>
                <Button v-if="isEmailNotVerifiedError" icon="pi pi-refresh" v-tooltip="'Resend Verification Email'" text
                    severity="secondary" size="small" :loading="authStore.loading" @click="handleResendVerification"
                    class="mt-2" />
            </template>
        </Message>
        
<!-- Form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-medium text-charcoal/80 mb-2">
                    {{ loginIdLabel }}
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 transition-colors duration-200">
                        <i class="pi pi-user text-sm"></i>
                    </span>
                    <InputText id="username" v-model="form.username" type="text" required autofocus
                        @focus="usernameFocused = true" @blur="usernameFocused = false"
                        class="w-full !pl-11 !pr-3 sm:!pr-4 !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-2.5 sm:!py-3 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.username }" />
                </div>
                <small v-if="errors.username" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.username
                    }}</small>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-charcoal/80 mb-2">
                    Password
                </label>
                <div class="relative group">
                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sage-400 transition-colors duration-200">
                        <i class="pi pi-key text-sm"></i>
                    </span>
                    <InputText id="password" v-model="form.password" type="password" required
                        @focus="passwordFocused = true" @blur="passwordFocused = false"
                        class="w-full !pl-11 !pr-3 sm:!pr-4 !bg-white !border-sage-200/60 !text-charcoal !text-[0.85rem] sm:!text-[0.92rem] !rounded-lg !py-2.5 sm:!py-3 transition-all duration-200 focus:!border-sage-400 focus:!ring-2 focus:!ring-sage-100 hover:!border-sage-300"
                        :class="{ 'p-invalid': errors.password }" />
                </div>
                <small v-if="errors.password" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.password
                    }}</small>
            </div>

            <!-- Forgot Password Link -->
            <div class="text-right mt-1">
                <router-link to="/forgot-password"
                    class="text-sm text-sage-600 hover:text-sage-700 font-medium underline-offset-2 hover:underline">
                    Forgot Password?
                </router-link>
            </div>

            <Button type="submit" label="Authenticate" icon="pi pi-shield" :loading="authStore.loading"
                class="w-full !mt-7 !py-2 sm:!py-3 !bg-sage-600 hover:!bg-sage-700 !border-none !rounded-lg !text-white !font-semibold !text-[0.85rem] sm:!text-[0.9rem] !transition-all !duration-200 hover:-translate-y-[1px]"
                :style="{ boxShadow: '0 2px 12px rgba(106, 156, 94, 0.2)' }" />
        </form>

            <div class="flex justify-center pt-1">
                <p class="text-sm text-slate-400 font-medium">
                    New user?
                    <a class="text-sage-600 hover:text-sage-700 font-semibold cursor-pointer underline-offset-2 hover:underline" @click="showRegister = true">Create an account</a>
                </p>
            </div>

        <!-- Security Footer
        <div class="flex items-center justify-center gap-2 pt-2">
            <i class="pi pi-shield text-sage-400 text-xs"></i>
            <span class="text-[0.7rem] text-slate-400 font-medium tracking-wide">AES-256 Bit SSL Tunnel Active</span>
        </div> -->

            <RegistrationModal v-model:visible="showRegister" @success="handleRegistrationSuccess" />
    </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import RegistrationModal from '../../components/RegistrationModal.vue';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const toast = useToast();
const showRegister = ref(false);
const usernameFocused = ref(false);
const passwordFocused = ref(false);

const form = reactive({
    username: route.query.institution_id || '',
    password: ''
});

const errors = reactive({
    username: '',
    password: ''
});

const isPostRegistrationFlow = computed(() =>
    Boolean(route.query.verified || route.query.registered)
);

const loginIdLabel = computed(() => {
    if (!isPostRegistrationFlow.value) {
        return 'Staff No. or Matric No.';
    }

    const id = String(form.username || route.query.institution_id || '').toUpperCase();
    return id.startsWith('STF') ? 'Staff No.' : 'Matric No.';
});

const validate = () => {
    let valid = true;
    errors.username = '';
    errors.password = '';
    
    if (!form.username) {
        errors.username = `${loginIdLabel.value} is required`;
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

const handleRegistrationSuccess = ({ email, institution_id }) => {
    router.push({ path: '/email-verification', query: { email, institution_id, registered: '1' } });
};

const getDashboardRoute = (roleSlug, user) => {
    switch(roleSlug) {
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

const isEmailNotVerifiedError = computed(() =>
    authStore.error && authStore.error.toLowerCase().includes('verify')
);

const handleResendVerification = async () => {
    if (!form.username) return;
    const message = await authStore.resendVerification(form.username);
    if (message) {
        toast.add({ severity: 'success', summary: 'Sent', detail: message, life: 3000 });
    }
};
</script>
