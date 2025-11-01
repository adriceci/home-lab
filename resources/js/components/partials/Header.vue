<script setup>
import { computed } from "vue";
import {
    HomeIcon,
    ChartBarIcon,
    DocumentTextIcon,
    FolderIcon,
    GlobeAltIcon,
    Cog6ToothIcon,
    ArrowRightOnRectangleIcon,
    SunIcon,
    MoonIcon,
} from "@heroicons/vue/24/outline";
import { Badge, Button } from "@/components/ui";
import { useRouter } from "vue-router";
import { useTheme } from "@/composables/useTheme";
import { useAuth } from "@/composables/useAuth";

const router = useRouter();
const { isDark, toggleTheme } = useTheme();
const { logout, isAdmin } = useAuth();

const allMenuItems = computed(() => {
    return [
        { name: "Dashboard", icon: HomeIcon, to: "/dashboard" },
        { name: "Analytics", icon: ChartBarIcon, to: "/analytics" },
        { name: "Archivos", icon: FolderIcon, to: "/files" },
        { name: "Dominios", icon: GlobeAltIcon, to: "/domains" },
        {
            name: "Audit Logs",
            icon: DocumentTextIcon,
            to: "/audit-logs",
            adminOnly: true,
        },
    ];
});

// Filter menu items based on user role
const menuItems = computed(() => {
    return allMenuItems.value.filter((item) => {
        if (item.adminOnly) {
            return isAdmin.value;
        }
        return true;
    });
});

const generalItems = [
    { name: "Settings", icon: Cog6ToothIcon, to: "/settings" },
    { name: "Logout", icon: ArrowRightOnRectangleIcon, action: "logout" },
];

const isActive = (to) => {
    return router.currentRoute.value.path === to;
};

const handleItemClick = async (item) => {
    if (item.action === "logout") {
        try {
            await logout();
            router.push("/login");
        } catch (error) {
            console.error("Logout error:", error);
            // Still redirect to login even if logout fails
            router.push("/login");
        }
    } else if (item.to) {
        router.push(item.to);
    }
};
</script>

<template>
    <aside
        class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 w-64 fixed left-0 top-0 z-30 shadow-lg flex flex-col m-6 rounded-lg h-[calc(100vh-3rem)]"
    >
        <!-- Logo -->
        <div class="p-6">
            <div class="flex items-center space-x-3">
                <img
                    src="/assets/images/logo.png"
                    alt="Home Lab"
                    height="40"
                    width="40"
                />
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                    Home Lab
                </h1>

                <!-- Theme Toggle Button -->
                <Button variant="ghost" size="sm" @click="toggleTheme">
                    <component
                        :is="isDark ? MoonIcon : SunIcon"
                        class="w-5 h-5"
                    />
                </Button>
            </div>
        </div>

        <!-- Main Menu Items -->
        <div class="py-4 flex-1">
            <ul class="space-y-1">
                <li v-for="item in menuItems" :key="item.name">
                    <router-link
                        :to="item.to"
                        :class="[
                            'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors relative',
                            isActive(item.to)
                                ? 'text-gray-900 dark:text-white bg-green-50 dark:bg-green-900/20'
                                : 'text-gray-600 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700',
                        ]"
                    >
                        <!-- Active indicator bar -->
                        <div
                            v-if="isActive(item.to)"
                            class="absolute left-0 top-0 bottom-0 w-1 bg-green-primary rounded-r-full"
                        ></div>

                        <component
                            :is="item.icon"
                            :class="[
                                'w-5 h-5 mr-3',
                                isActive(item.to)
                                    ? 'text-green-primary'
                                    : 'text-gray-400 dark:text-gray-500',
                            ]"
                        />
                        <span class="flex-1">{{ item.name }}</span>
                        <Badge v-if="item.badge" variant="primary" size="xs">
                            {{ item.badge }}
                        </Badge>
                    </router-link>
                </li>
            </ul>
        </div>

        <!-- General Items at Bottom -->
        <div class="py-4">
            <ul class="space-y-1">
                <li v-for="item in generalItems" :key="item.name">
                    <button
                        v-if="item.action"
                        @click="handleItemClick(item)"
                        class="w-full flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-200 rounded-lg transition-colors hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <component
                            :is="item.icon"
                            class="w-5 h-5 mr-3 text-gray-400 dark:text-gray-500"
                        />
                        <span>{{ item.name }}</span>
                    </button>
                    <router-link
                        v-else
                        :to="item.to"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-200 rounded-lg transition-colors hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <component
                            :is="item.icon"
                            class="w-5 h-5 mr-3 text-gray-400 dark:text-gray-500"
                        />
                        <span>{{ item.name }}</span>
                    </router-link>
                </li>
            </ul>
        </div>
    </aside>
</template>
