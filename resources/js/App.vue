<script setup>
// Theme initialization is handled in the Header component
import Notification from "@/components/Notification.vue";
</script>

<template>
    <div>
        <main class="min-h-screen p-6 bg-gray-50 dark:bg-gray-900">
            <router-view />
        </main>
        <Notification />
    </div>
</template>
