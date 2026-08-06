import axios from '../bootstrap';

export async function initializeCsrf() {
    await axios.get('/sanctum/csrf-cookie');
}

axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;

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