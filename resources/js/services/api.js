import axios from '../bootstrap';

export async function initializeCsrf() {
    await axios.get('/sanctum/csrf-cookie');
}

async function showSecurityToast(code, retryAfter) {
    try {
        const { getSecurityMessage, getThrottleMessage, isSecurityCode } = await import('../utils/securityMessages');
        const { useToast } = await import('primevue/usetoast');
        const toast = useToast();
        let msg = null;
        if (isSecurityCode(code)) {
            msg = getSecurityMessage(code, retryAfter);
        } else if (code === 'RATE_LIMITED') {
            msg = getThrottleMessage(retryAfter);
        }
        if (msg) {
            toast.add({
                severity: msg.severity,
                summary: msg.summary,
                detail: msg.detail,
                life: msg.life,
            });
        }
    } catch {
        // Toast infra not ready (e.g. before app mount) — silently ignore
    }
}

axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const body = error.response?.data;
        const headers = error.response?.headers || {};

        // --- Security-aware toast (IPS / rate limit) ---
        // Preserve backend security decisions but map to safe human messages.
        // Never renders raw `error` / `reason` internals — only allowlisted codes.
        const retryAfterHeader = headers['retry-after'] || headers['Retry-After'] || body?.retry_after || null;
        const code = body?.code ? String(body.code).toUpperCase() : null;

        const securityCodes = ['IP_BLOCKED', 'USER_BLOCKED', 'INTRUSION_DETECTED'];
        if (code && securityCodes.includes(code)) {
            // Mark for downstream handlers so they don't overwrite with generic fallback
            error.__isSecurityBlock = true;
            error.__securityCode = code;
            await showSecurityToast(code, retryAfterHeader);
        } else if (status === 429) {
            // Laravel throttle (login/register/password-reset) -> RATE_LIMITED
            // Has Retry-After header, body.message = "Too Many Attempts."
            error.__isSecurityBlock = true;
            error.__securityCode = 'RATE_LIMITED';
            await showSecurityToast('RATE_LIMITED', retryAfterHeader);
        }

        // Password change required: force the user to the change-password page.
        if (status === 403 && body?.must_change_password) {
            const { useAuthStore } = await import('../stores/auth');
            const { default: router } = await import('../router');
            const authStore = useAuthStore();
            const route = router.currentRoute.value;
            if (route.name !== 'ChangePassword') {
                router.push({ name: 'ChangePassword' });
            }
        }

        // Password change pending email verification: force the verify page.
        if (status === 403 && body?.password_change_pending_verification) {
            const { default: router } = await import('../router');
            const route = router.currentRoute.value;
            if (route.name !== 'VerifyPasswordChange') {
                router.push({ name: 'VerifyPasswordChange' });
            }
        }

        // Token expired / invalid: clear session and redirect to login
        // Skip if this was already a security block toast — don't double-redirect
        if (status === 401 && !error.config?.__skipAuthFail && !error.__isSecurityBlock) {
            const { useAuthStore } = await import('../stores/auth');
            const { default: router } = await import('../router');
            const authStore = useAuthStore();

            authStore.clearUser();

            const route = router.currentRoute.value;
            if (route.name !== 'Login' && !route.meta?.guest) {
                router.push({ name: 'Login' });
            }
        }

        return Promise.reject(error);
    }
);

export default axios;