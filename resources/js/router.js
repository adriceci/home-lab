import { createRouter, createWebHistory } from "vue-router";

import Dashboard from "@/views/Dashboard.vue";
import NotFound from "@/views/system/NotFound.vue";
import AppLayout from "@/components/layouts/AppLayout.vue";

const appRoutes = [
    {
        path: "/dashboard",
        component: AppLayout,
        children: [{ path: "", name: "dashboard", component: Dashboard }],
    },
];

const systemRoutes = [{ path: "/:pathMatch(.*)*", component: NotFound }];

const router = createRouter({
    history: createWebHistory(),
    routes: [...appRoutes, ...systemRoutes],
});

export default router;
