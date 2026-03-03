import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import DashboardLayout from '../views/dashboard/DashboardLayout.vue';
import DashboardHome from '../views/dashboard/DashboardHome.vue';
import ProfileView from '../views/dashboard/ProfileView.vue';
import MealPlansView from '../views/dashboard/MealPlansView.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: RegisterView,
        meta: { guest: true },
    },
    {
        path: '/dashboard',
        component: DashboardLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: DashboardHome,
            },
            {
                path: 'profile',
                name: 'profile',
                component: ProfileView,
            },
            {
                path: 'meal-plans',
                name: 'meal-plans',
                component: MealPlansView,
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/dashboard',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.bootstrap();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({ name: 'login' });
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return next({ name: 'dashboard' });
    }

    return next();
});

export default router;

