import { createRouter, createWebHistory } from 'vue-router';
import AppLayout from '../layouts/AppLayout.vue';
import AuthLayout from '../layouts/AuthLayout.vue';

const routes = [
    {
        path: '/',
        name: 'Landing',
        component: () => import('../pages/LandingPage.vue'),
        meta: { layout: AppLayout, public: true }
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../pages/auth/LoginPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },
    {
        path: '/admin',
        name: 'AdminDashboard',
        component: () => import('../pages/admin/AdminDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/staff',
        name: 'StaffDashboard',
        component: () => import('../pages/staff/StaffDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['staff'] }
    },
    {
        path: '/complainant',
        name: 'ComplainantDashboard',
        component: () => import('../pages/complainant/ComplainantDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['complainant'] }
    },
    {
        path: '/security',
        name: 'SecurityDashboard',
        component: () => import('../pages/security/SecurityDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['security'] }
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    if (!to.meta.requiresAuth) {
        return true;
    }

    return true;
});

export default router;
