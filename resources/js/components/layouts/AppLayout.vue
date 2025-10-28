<script setup>
import Header from "@/components/partials/Header.vue";
import { useTheme } from "@/composables/useTheme";
import { onMounted } from "vue";

const { initTheme } = useTheme();

onMounted(() => {
    initTheme();
});
</script>

<template>
    <div class="ml-70">
        <Header />
        <router-view />
    </div>
</template>
