<template>
  <div class="h-screen overflow-hidden bg-[#F8FAF6] font-sans">
    <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-950/30 md:hidden" @click="closeSidebar"></div>

    <div class="flex h-full min-w-0">
      <aside
        class="fixed inset-y-0 left-0 z-40 flex w-[280px] flex-col bg-white transition-all duration-300 md:static md:flex-shrink-0 md:overflow-y-auto"
        :class="sidebarClasses" style="border-right: 1px solid #E8EFE9;">
        <div class="flex items-center gap-3 px-6 py-7">
          <div class="w-10 h-10 rounded-[10px] bg-sage-600 flex items-center justify-center text-white"
            style="box-shadow: 0 2px 8px rgba(106, 156, 94, 0.25);">
            <i :class="brand.icon" class="text-lg"></i>
          </div>
          <div :class="sidebarCollapsed ? 'md:hidden' : 'md:block'">
            <span class="block font-semibold text-[0.85rem] lg:text-[0.95rem] tracking-tight text-charcoal">{{ brand.title }}</span>
            <span class="block text-[0.65rem] text-sage-500 font-medium tracking-[0.12em] uppercase">{{ brand.subtitle
              }}</span>
          </div>
        </div>

        <nav class="flex-1 space-y-1 px-4 py-2">
          <template v-for="nav in sideNav" :key="nav.to">
            <router-link :to="nav.to" class="nav-link" active-class="nav-link-active" @click="closeSidebarOnMobile">
              <i :class="nav.icon" class="text-base nav-icon"></i>
              <span class="font-medium text-[0.8rem] lg:text-[0.875rem]" :class="sidebarCollapsed ? 'md:hidden' : 'md:inline'">{{
                nav.label }}</span>
            </router-link>
          </template>
        </nav>

        <div class="p-4 border-t" :class="sidebarCollapsed ? 'md:px-3' : ''" style="border-color: #E8EFE9;">
          <div class="flex items-center gap-3 mb-3 px-2">
            <div
              class="w-9 h-9 rounded-full bg-sage-100 flex items-center justify-center text-sage-600 font-semibold text-sm">
              {{ authStore.user?.name ? authStore.user.name.charAt(0).toUpperCase() : 'U' }}
            </div>
            <div class="overflow-hidden flex-1 min-w-0">
              <span class="block text-[0.8rem] lg:text-[0.825rem] font-semibold text-charcoal truncate"
                :class="sidebarCollapsed ? 'md:hidden' : 'md:block'">{{ authStore.user?.name }}</span>
              <span class="block text-[0.7rem] text-slate-400 truncate"
                :class="sidebarCollapsed ? 'md:hidden' : 'md:block'">{{ authStore.user?.institution_id }}</span>
            </div>
          </div>
          <Button :label="sidebarCollapsed ? '' : 'Sign Out'" icon="pi pi-sign-out" severity="danger" text
            class="w-full !justify-start hover:!bg-red-50/60" @click="handleLogout" />
        </div>
      </aside>

      <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        <header
          class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-sage-100 bg-white/80 px-4 backdrop-blur-md sm:px-6 lg:px-8"
          style="border-bottom: 1px solid #E8EFE9;">
          <div class="flex items-center gap-3">
            <Button icon="pi pi-bars" text rounded class="!h-9 !w-9" @click="toggleSidebar"
              aria-label="Toggle sidebar" />
            <i class="pi pi-shield text-sage-500 text-xs hidden sm:inline"></i>
            <span class="text-[0.7rem] text-slate-400 font-medium tracking-wider uppercase">{{ headerText }}</span>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-[0.7rem] font-semibold tracking-wide px-2.5 py-1 rounded-full"
              :style="{ background: badge.bg, color: badge.text }">{{ badge.label }}</span>
            <span class="text-[0.7rem] text-slate-400 hidden md:inline">{{ footerStatus }}</span>
          </div>
        </header>

        <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
          <slot />
        </main>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import Toast from 'primevue/toast';

const authStore = useAuthStore();
const router = useRouter();
const role = computed(() => authStore.role);
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);
const viewportWidth = ref(window.innerWidth);

const isMobile = computed(() => viewportWidth.value < 768);

const sideNav = computed(() => {
  switch (role.value) {
    case 'admin':
      return [
        { to: '/admin', icon: 'pi pi-sliders-h', label: 'System Metrics' },
        { to: '/admin/complaints', icon: 'pi pi-bars', label: 'All Complaints' },
        { to: '/security', icon: 'pi pi-exclamation-triangle', label: 'Security (IPS)' },
        { to: '/admin/users', icon: 'pi pi-users', label: 'User Management' },
      ];
    case 'complaint_officer':
      return [{ to: '/officer', icon: 'pi pi-briefcase', label: 'My Assignments' }];
    case 'security':
      return [{ to: '/security', icon: 'pi pi-exclamation-triangle', label: 'Security (IPS)' }];
    case 'student':
      return [
        { to: '/student', icon: 'pi pi-home', label: 'Dashboard' },
        { to: '/student/submit', icon: 'pi pi-pencil', label: 'Submit Complaint' },
      ];
    case 'staff':
      return [
        { to: '/staff', icon: 'pi pi-home', label: 'Dashboard' },
        { to: '/staff/submit', icon: 'pi pi-pencil', label: 'Submit Complaint' },
      ];
    default:
      return [];
  }
});

const brand = computed(() => {
  switch (role.value) {
    case 'admin':
      return { title: 'SUPER ADMIN', subtitle: 'System Console', icon: 'pi pi-shield' };
    case 'security':
      return { title: 'SECURITY OFFICER', subtitle: 'System Console', icon: 'pi pi-shield' };
    case 'complaint_officer':
      return { title: 'COMPLAINT OFFICER', subtitle: 'Officer Portal', icon: 'pi pi-briefcase' };
    case 'student':
      return { title: 'STUDENT', subtitle: 'Student Portal', icon: 'pi pi-graduation-cap' };
    case 'staff':
      return { title: 'STAFF', subtitle: 'Staff Portal', icon: 'pi pi-briefcase' };
    default:
      return { title: 'GUEST', subtitle: 'Portal', icon: 'pi pi-shield' };
  }
});

const headerText = computed(() => {
  switch (role.value) {
    case 'admin':
    case 'security':
      return 'Apex Firewall & IPS Protection Daemon Active';
    case 'complaint_officer':
      return 'Al-Hikmah University Security Shield Active';
    case 'student':
      return 'Secure Student Complaint Terminal Active';
    case 'staff':
      return 'Secure Staff Complaint Terminal Active';
    default:
      return 'Secure Connection Established';
  }
});

const badge = computed(() => {
  switch (role.value) {
    case 'admin':
      return { label: 'Super Admin', bg: '#fef2f2', text: '#b91c1c' };
    case 'security':
      return { label: 'Security Officer', bg: '#fef3c7', text: '#b45309' };
    case 'complaint_officer':
      return { label: 'Complaint Officer', bg: '#ecfdf5', text: '#047857' };
    case 'student':
      return { label: 'Student', bg: '#eff6ff', text: '#1d4ed8' };
    case 'staff':
      return { label: 'Staff', bg: '#faf5ff', text: '#7e22ce' };
    default:
      return { label: 'User', bg: '#f3f4f6', text: '#374151' };
  }
});

const footerStatus = computed(() => 'Database Guard Layer Active');

const sidebarClasses = computed(() => {
  if (isMobile.value) {
    return sidebarOpen.value ? 'translate-x-0' : '-translate-x-full';
  }

  return sidebarCollapsed.value ? 'md:w-[88px]' : 'md:w-[260px]';
});

const toggleSidebar = () => {
  if (isMobile.value) {
    sidebarOpen.value = !sidebarOpen.value;
    return;
  }

  sidebarCollapsed.value = !sidebarCollapsed.value;
};

const closeSidebarOnMobile = () => {
  if (isMobile.value) {
    sidebarOpen.value = false;
  }
};

const closeSidebar = () => {
  sidebarOpen.value = false;
};

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const syncViewportWidth = () => {
  viewportWidth.value = window.innerWidth;
};

onMounted(() => {
  window.addEventListener('resize', syncViewportWidth);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncViewportWidth);
});
</script>
