import axios from '../bootstrap';

export async function initializeCsrf() {
    await axios.get('/sanctum/csrf-cookie');
}

axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const body = error.response?.data;

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
        if (status === 401 && !error.config?.__skipAuthFail) {
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