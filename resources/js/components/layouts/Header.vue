<script setup>
import {
    HomeIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    ArrowRightOnRectangleIcon,
} from "@heroicons/vue/24/outline";
import { Badge } from "@/components/ui";
import { useRouter } from "vue-router";

const router = useRouter();

const menuItems = [
    { name: "Dashboard", icon: HomeIcon, to: "/dashboard" },
    { name: "Analytics", icon: ChartBarIcon, to: "/analytics" },
];

const generalItems = [
    { name: "Settings", icon: Cog6ToothIcon, to: "/settings" },
    { name: "Logout", icon: ArrowRightOnRectangleIcon, to: "/logout" },
];

const isActive = (to) => {
    return router.currentRoute.value.path === to;
};
</script>

<template>
    <aside
        class="bg-white border-r border-gray-200 w-64 fixed left-0 top-0 z-30 shadow-lg flex flex-col m-6 rounded-2xl h-[calc(100vh-3rem)]"
    >
        <!-- Logo -->
        <div class="p-6">
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-gray-900">Home Lab</h1>
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
                                ? 'text-gray-900 bg-green-50'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
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
                                    : 'text-gray-400',
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
                    <router-link
                        :to="item.to"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg transition-colors hover:text-gray-900 hover:bg-gray-50"
                    >
                        <component
                            :is="item.icon"
                            class="w-5 h-5 mr-3 text-gray-400"
                        />
                        <span>{{ item.name }}</span>
                    </router-link>
                </li>
            </ul>
        </div>
    </aside>
</template>
