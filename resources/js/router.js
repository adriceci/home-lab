import { createRouter, createWebHistory } from "vue-router";
import { useAuth } from "@/composables/useAuth";

import Dashboard from "@/views/Dashboard.vue";
import Analytics from "@/views/Analytics.vue";
import Files from "@/views/Files.vue";
import AuditLogs from "@/vendor/audit-center/components/AuditLogs.vue";
import Login from "@/views/Login.vue";
import NotFound from "@/views/system/NotFound.vue";
import AppLayout from "@/components/layouts/AppLayout.vue";

const appRoutes = [
    {
        path: "/dashboard",
        component: AppLayout,
        children: [{ path: "", name: "dashboard", component: Dashboard }],
    },
    {
        path: "/analytics",
        component: AppLayout,
        children: [{ path: "", name: "analytics", component: Analytics }],
    },
    {
        path: "/files",
        component: AppLayout,
        children: [{ path: "", name: "files", component: Files }],
    },
    {
        path: "/audit-logs",
        component: AppLayout,
        children: [{ path: "", name: "audit-logs", component: AuditLogs }],
    },
];

const authRoutes = [
    {
        path: "/login",
        name: "login",
        component: Login,
    },
];

const systemRoutes = [{ path: "/:pathMatch(.*)*", component: NotFound }];

const router = createRouter({
    history: createWebHistory(),
    routes: [...authRoutes, ...appRoutes, ...systemRoutes],
});

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
    const { isAuthenticated } = useAuth();
    const isAuthRoute = to.path === "/login";

    if (!isAuthenticated.value && !isAuthRoute) {
        next("/login");
    } else if (isAuthenticated.value && isAuthRoute) {
        next("/dashboard");
    } else {
        next();
    }
});

export default router;
