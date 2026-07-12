<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-surface-50 dark:bg-surface-950 px-4">
    <div class="text-center max-w-md space-y-6">
      <div class="w-20 h-20 bg-blue-600/10 border border-blue-500/20 rounded-2xl flex items-center justify-center mx-auto text-blue-500 shadow-xl shadow-blue-500/5">
        <i class="pi pi-shield text-4xl animate-pulse"></i>
      </div>
      <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-surface-900 dark:text-surface-0 tracking-tight">Apex Secure CMS</h1>
        <p class="text-sm text-surface-500 dark:text-surface-400">Loading secure portal credentials, please wait...</p>
      </div>
      <div class="flex justify-center">
        <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const getDashboardRoute = (roleSlug) => {
    switch(roleSlug) {
    case 'admin': return '/admin';
    case 'staff': return '/staff';
    case 'security': return '/security';
    case 'student': return '/student';
    case 'complaint_officer': return '/officer';
    default: return '/login';
    }
};

onMounted(async () => {
    // If token exists, load user profile
    if (localStorage.getItem('auth_token')) {
        try {
            await authStore.fetchUser();
        } catch (e) {
            // ignore
        }
    }
    
    if (authStore.isAuthenticated) {
        router.push(getDashboardRoute(authStore.role));
    } else {
        router.push('/login');
    }
});
</script>
