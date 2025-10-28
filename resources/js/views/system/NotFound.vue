<script setup>
import ErrorLayout from "@/components/layouts/ErrorLayout.vue";
</script>

<template>
    <ErrorLayout>
        <template #error-content>
            <h1
                class="text-8xl font-bold text-green-primary dark:text-green-400"
            >
                404
            </h1>
            <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">
                Página no encontrada
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Lo sentimos, la página que estás buscando no está disponible.
            </p>
        </template>
    </ErrorLayout>
</template>
