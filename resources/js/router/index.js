import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AuthLayout from '../layouts/AuthLayout.vue';
import AppLayout from '../layouts/AppLayout.vue';

const routes = [
    {
        path: '/',
        name: 'Landing',
        component: () => import('../pages/LandingPage.vue'),
        meta: { public: true }
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../pages/auth/LoginPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('../pages/auth/ForgotPasswordPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },
    {
        path: '/reset-password',
        name: 'ResetPassword',
        component: () => import('../pages/auth/ResetPasswordPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },
    {
        path: '/email-verification',
        name: 'EmailVerification',
        component: () => import('../pages/auth/EmailVerificationPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },
    {
        path: '/forgot-password',
        name: 'ForgotPassword',
        component: () => import('../pages/auth/ForgotPasswordPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },
    {
        path: '/reset-password',
        name: 'ResetPassword',
        component: () => import('../pages/auth/ResetPasswordPage.vue'),
        meta: { layout: AuthLayout, guest: true }
    },

    // Student Routes
    {
        path: '/student',
        name: 'StudentDashboard',
        component: () => import('../pages/student/StudentDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['student'] }
    },
    {
        path: '/student/submit',
        name: 'StudentSubmitComplaint',
        component: () => import('../pages/student/SubmitComplaint.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['student'] }
    },
    {
        path: '/student/complaints/:id',
        name: 'StudentComplaintDetails',
        component: () => import('../pages/complaints/ComplaintDetails.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['student'] }
    },

    // Staff Complainant Routes (staff role)
    {
        path: '/staff',
        name: 'StaffDashboard',
        component: () => import('../pages/staff/StaffDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['staff'] }
    },
    {
        path: '/staff/submit',
        name: 'StaffSubmitComplaint',
        component: () => import('../pages/student/SubmitComplaint.vue'), // Reuse student submit page
        meta: { layout: AppLayout, requiresAuth: true, roles: ['staff'] }
    },
    {
        path: '/staff/complaints/:id',
        name: 'StaffComplaintDetails',
        component: () => import('../pages/complaints/ComplaintDetails.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['staff'] }
    },

    // Complaint Officer Routes
    {
        path: '/officer',
        name: 'OfficerDashboard',
        component: () => import('../pages/officer/OfficerDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['complaint_officer'] }
    },
    {
        path: '/officer/complaints/:id',
        name: 'OfficerComplaintDetails',
        component: () => import('../pages/complaints/ComplaintDetails.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['complaint_officer'] }
    },

    // Admin Routes
    {
        path: '/admin',
        name: 'AdminDashboard',
        component: () => import('../pages/admin/AdminDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/admin/users',
        name: 'AdminUsers',
        component: () => import('../pages/admin/users/Users.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/admin/users/students',
        name: 'AdminStudents',
        component: () => import('../pages/admin/users/Students.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/admin/users/staff',
        name: 'AdminStaff',
        component: () => import('../pages/admin/users/Staff.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/admin/complaints',
        name: 'AdminComplaints',
        component: () => import('../pages/admin/Complaints.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/admin/complaints/:id',
        name: 'AdminComplaintDetails',
        component: () => import('../pages/complaints/ComplaintDetails.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['admin'] }
    },

    // Security Dashboard (Admin or Security role)
    {
        path: '/security',
        name: 'SecurityDashboard',
        component: () => import('../pages/security/SecurityDashboard.vue'),
        meta: { layout: AppLayout, requiresAuth: true, roles: ['security', 'admin'] }
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

const getDashboardRoute = (roleSlug, user) => {
    switch (roleSlug) {
        case 'admin': return '/admin';
        case 'staff': return '/staff';
        case 'security': return '/security';
        case 'student': return '/student';
        case 'complaint_officer': return '/officer';
        default: return '/login';
    }
};

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Try to load user if token is present and user is not loaded
    if (localStorage.getItem('auth_token') && !authStore.user) {
        await authStore.fetchUser();
    }

    const isAuthenticated = authStore.isAuthenticated;
    const role = authStore.role;

    // 1. Guard for requiresAuth
    if (to.meta.requiresAuth && !isAuthenticated) {
        return next('/login');
    }

    // 2. Guard for guests-only (like login screen)
    if (to.meta.guest && isAuthenticated) {
        return next(getDashboardRoute(role, authStore.user));
    }

    // 3. Guard for role-specific authorization
    if (to.meta.roles && !to.meta.roles.includes(role)) {
        return next(getDashboardRoute(role, authStore.user));
    }

    // 4. Guard for complainant differentiation handled by role-based route meta

    next();
});

export default router;
