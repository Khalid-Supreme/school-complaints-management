import { defineStore } from 'pinia';
import axios from '../bootstrap';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        initialized: false,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.user),
        role: (state) => state.user?.role ?? null,
    },
    actions: {
        async fetchUser() {
            const { data } = await axios.get('/api/user');
            this.user = data;
            this.initialized = true;

            return data;
        },
        clearUser() {
            this.user = null;
            this.initialized = true;
        },
    },
});
