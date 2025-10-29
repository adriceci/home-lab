<script setup>
import { Search, Avatar } from "@/components/ui";
import { useAuth } from "@/composables/useAuth";

const { user } = useAuth();
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar -->
        <div
            class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <Search />
            <!-- TODO: Change profile -->
            <Avatar :src="user?.avatar" :alt="user?.name" :size="lg" />
        </div>
    </div>
</template>
