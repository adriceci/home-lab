<script setup>
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    placeholder: {
        type: String,
        default: "Buscar",
    },
});
</script>

<template>
    <div class="flex items-center space-x-4">
        <div class="relative">
            <MagnifyingGlassIcon
                class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500"
            />
            <input
                type="text"
                :placeholder="placeholder"
                class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md px-4 py-2 text-gray-900 dark:text-white transition-all duration-200 pl-10 pr-4 w-80 placeholder-gray-500 dark:placeholder-gray-300"
            />
            <span
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-400 dark:text-gray-500"
                >⌘F</span
            >
        </div>
    </div>
</template>
