<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-sage-50 via-white to-emerald-50 px-4 py-10">
        <div class="w-full max-w-md md:max-w-xl lg:max-w-2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-sage-600 text-white shadow-lg mb-4">
                    <i class="pi pi-envelope text-2xl"></i>
                </div>
                <h1 class="text-2xl lg:text-3xl font-semibold text-charcoal tracking-tight">Verify Your Password Change</h1>
                <p class="text-slate-500 text-sm mt-2">
                    Enter the 6-digit code we sent to <span class="font-medium text-slate-700">{{ maskedEmail }}</span> to finish securing your account.
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-900/5 p-6 sm:p-8">
                <Message v-if="error" severity="error" :closable="false" class="!text-sm mb-4">{{ error }}</Message>
                <Message v-if="notice" severity="info" :closable="false" class="!text-sm mb-4">{{ notice }}</Message>
                <Message v-if="needsResend" severity="warn" :closable="false" class="!text-sm mb-4">
                    {{ needsResend }}
                </Message>

                <form v-if="canAttempt" @submit.prevent="handleSubmit" class="space-y-5">
                    <div>
                        <label for="code" class="block text-sm font-medium text-charcoal/80 mb-2">Verification Code</label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="000000"
                            class="mx-auto block w-full max-w-xs text-center text-2xl tracking-[0.5em] rounded-xl border border-slate-300 px-3 py-3 focus:outline-none focus:ring-2 focus:ring-sage-600 disabled:opacity-60"
                            :class="{ 'border-red-500': errors.code }"
                            :disabled="loading"
                        />
                        <small v-if="errors.code" class="text-red-500 block mt-1.5 text-xs font-medium">{{ errors.code }}</small>
                    </div>

                    <Button type="submit" label="Verify & Continue" icon="pi pi-check" :loading="loading" class="w-full !bg-sage-600 hover:!bg-sage-700 !border-none !text-white !font-semibold !rounded-xl !py-3" />
                </form>

                <div v-else class="py-4 text-center">
                    <i class="pi pi-info-circle text-2xl text-slate-300 mb-2 block"></i>
                    <p class="text-sm text-slate-500">Request a new code to continue verifying your password change.</p>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-100">
                    <div class="text-center mb-3">
                        <p class="text-sm text-slate-500">Didn't get the code? Check your spam folder.</p>
                    </div>
                    <div class="flex items-center justify-center gap-2">
                        <Button
                            label="Resend Code"
                            icon="pi pi-refresh"
                            outlined
                            size="small"
                            :loading="resending"
                            :disabled="resending || resendCooldown > 0"
                            @click="handleResend"
                            class="!rounded-xl"
                        />
                    </div>
                    <small v-if="resendCooldown > 0" class="block mt-2 text-center text-xs text-slate-400">
                        You can resend again in {{ resendCooldown }}s
                    </small>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                    <button type="button" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-charcoal transition-colors" @click="handleSignOut">
                        <i class="pi pi-arrow-left text-xs"></i> Back to Sign In
                    </button>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">
                <i class="pi pi-shield mr-1"></i> AES-256 Bit SSL Tunnel Active
            </p>
        </div>
    </div>
</template>

<script setup>
    import { reactive, ref, computed, onMounted, onBeforeUnmount } from 'vue';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from '../../stores/auth';
    import Button from 'primevue/button';
    import Message from 'primevue/message';

    const router = useRouter();
    const authStore = useAuthStore();

    const loading = ref(false);
    const resending = ref(false);
    const error = ref('');
    const notice = ref('');
    const resendCooldown = ref(0);
    const status = ref(null);
    let cooldownTimer = null;

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

    const form = reactive({
        code: '',
    });

    const errors = reactive({
        code: '',
    });

    const maskedEmail = computed(() => status.value?.masked_email || 'your email');

    // The backend is authoritative. Once the flow is no longer pending the
    // challenge is unusable (already used / cleared), so further attempts make
    // no sense — the user should resend to get a fresh challenge.
    const canAttempt = computed(() => !!status.value?.pending && !!status.value?.has_challenge);

    const needsResend = computed(() => {
        if (!status.value?.pending) return '';
        if (!status.value?.has_challenge) return 'Your verification session has expired. Please request a new code.';
        if (status.value?.exhausted) return 'Too many incorrect attempts. Please request a new code.';
        if (status.value?.expired) return 'This code has expired. Please request a new one.';
        if (status.value?.used) return 'This code has already been used. Please request a new one.';
        return '';
    });

    const startCooldown = (seconds = 30) => {
        resendCooldown.value = seconds;
        if (cooldownTimer) clearInterval(cooldownTimer);
        cooldownTimer = setInterval(() => {
            resendCooldown.value -= 1;
            if (resendCooldown.value <= 0) {
                clearInterval(cooldownTimer);
                cooldownTimer = null;
            }
        }, 1000);
    };

    const loadStatus = async () => {
        const result = await authStore.fetchPasswordChangeStatus();

        if (!result) {
            error.value = 'We couldn\'t load your verification status. Please try again.';
            return;
        }

        status.value = result;

        // No longer pending (e.g. already verified elsewhere): send the user
        // to their dashboard instead of trapping them on this page.
        if (!result.pending) {
            if (authStore.isAuthenticated) {
                router.replace(getDashboardRoute(authStore.role));
            }
            return;
        }

        error.value = '';
    };

    const validate = () => {
        let valid = true;
        errors.code = '';

        if (!/^\d{6}$/.test(form.code)) {
            errors.code = 'Enter the 6-digit code sent to your email';
            valid = false;
        }

        return valid;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        loading.value = true;
        error.value = '';
        errors.code = '';

        try {
            const message = await authStore.verifyPasswordChange(form.code);

            if (message) {
                const dashboard = getDashboardRoute(authStore.role);
                router.push({ path: dashboard, query: { password_changed: 'success' } });
                return;
            }

            // Surface the server's authoritative message (e.g. remaining
            // attempts, expired, exhausted) instead of a generic failure.
            error.value = authStore.error;
            form.code = '';
            await loadStatus();
        } catch (err) {
            error.value = err.response?.data?.message || 'Verification failed. Please try again.';
            await loadStatus();
        } finally {
            loading.value = false;
        }
    };

    const handleResend = async () => {
        resending.value = true;
        error.value = '';
        notice.value = '';
        errors.code = '';

        try {
            const message = await authStore.resendPasswordChange();

            if (message) {
                notice.value = message;
                startCooldown();
                form.code = '';
                await loadStatus();
            } else if (authStore.error) {
                error.value = authStore.error;
            }
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to resend the code.';
        } finally {
            resending.value = false;
        }
    };

    const handleSignOut = async () => {
        await authStore.logout();
        router.push({ name: 'Login' });
    };

    onMounted(async () => {
        form.code = '';
        await loadStatus();
    });

    onBeforeUnmount(() => {
        if (cooldownTimer) clearInterval(cooldownTimer);
    });
</script>

<script>
export default {
    name: 'VerifyPasswordChangePage',
};
</script>
