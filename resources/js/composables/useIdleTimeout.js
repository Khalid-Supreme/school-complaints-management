import { onBeforeUnmount, onMounted, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

export function useIdleTimeout() {
    const authStore = useAuthStore();
    const router = useRouter();

    const timeoutMs = Number(import.meta.env.VITE_IDLE_TIMEOUT_MINUTES || 15) * 60 * 1000;

    let timer = null;

    const clearTimer = () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    };

    const startTimer = () => {
        clearTimer();
        if (!authStore.isAuthenticated) return;
        timer = setTimeout(async () => {
            await authStore.logout();
            router.push('/login');
        }, timeoutMs);
    };

    const handleActivity = () => {
        if (authStore.isAuthenticated) {
            startTimer();
        }
    };

    const stopListening = () => {
        ACTIVITY_EVENTS.forEach((event) => {
            window.removeEventListener(event, handleActivity);
        });
    };

    const startListening = () => {
        stopListening();
        ACTIVITY_EVENTS.forEach((event) => {
            window.addEventListener(event, handleActivity, { passive: true });
        });
    };

    onMounted(() => {
        startListening();
        startTimer();
    });

    onBeforeUnmount(() => {
        clearTimer();
        stopListening();
    });

    watch(() => authStore.isAuthenticated, (isAuth) => {
        if (isAuth) {
            startListening();
            startTimer();
        } else {
            clearTimer();
            stopListening();
        }
    });

    return { reset: startTimer };
}
