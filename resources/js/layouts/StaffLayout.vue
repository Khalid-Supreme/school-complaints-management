<template>
    <div class="min-h-screen flex bg-surface-50 dark:bg-surface-950 font-sans">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-slate-100 flex flex-col border-r border-slate-800 shadow-xl z-20 transition-all duration-300">
            <div class="p-6 border-b border-slate-800 flex items-center gap-3">
                <div class="bg-indigo-600 p-2 rounded-lg text-white shadow-md shadow-indigo-500/30">
                    <i class="pi pi-lock text-xl"></i>
                </div>
                <div>
                    <span class="font-extrabold text-md tracking-wider text-white">APEX SECURE</span>
                    <span class="block text-xs text-indigo-400 font-semibold tracking-widest">STAFF PORTAL</span>
                </div>
            </div>
            
            <nav class="flex-1 p-4 space-y-2">
                <router-link to="/staff" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all group" active-class="bg-indigo-600 text-white hover:bg-indigo-600">
                    <i class="pi pi-home text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Dashboard</span>
                </router-link>
                
                <router-link to="/staff/submit" class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-all group" active-class="bg-indigo-600 text-white hover:bg-indigo-600">
                    <i class="pi pi-plus-circle text-lg group-hover:scale-110 transition-transform"></i>
                    <span class="font-medium">Submit Complaint</span>
                </router-link>
            </nav>

            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold text-sm">
                        {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="overflow-hidden">
                        <span class="block text-sm font-bold text-white truncate text-ellipsis">{{ authStore.user?.name }}</span>
                        <span class="block text-xs text-slate-400 truncate text-ellipsis">{{ authStore.user?.institution_id }}</span>
                    </div>
                </div>
                <Button label="Sign Out" icon="pi pi-sign-out" severity="danger" text class="w-full text-left justify-start p-button-sm hover:bg-red-500/10" @click="handleLogout" />
            </div>
        </aside>

        <!-- Main Content Pane -->
        <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
            <header class="h-16 border-b border-surface-200 dark:border-surface-800 bg-surface-0/80 dark:bg-surface-900/80 backdrop-blur-md flex items-center justify-between px-8 z-10 sticky top-0">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield text-indigo-600 font-bold animate-pulse"></i>
                    <span class="text-xs text-surface-500 dark:text-surface-400 font-semibold uppercase tracking-wider">Al-Hikmah University Security Shield Active</span>
                </div>
                <div class="flex items-center gap-4">
                    <Badge value="Staff Account" severity="warning" class="text-xs font-bold text-white" style="background-color: #6366f1" />
                    <span class="text-xs text-surface-400">AES-256 E2E Active</span>
                </div>
            </header>

            <main class="flex-1 p-8 bg-surface-50 dark:bg-surface-950">
                <slot />
            </main>
        </div>
        
        <Toast />
    </div>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Toast from 'primevue/toast';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>
