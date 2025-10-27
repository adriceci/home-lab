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
                class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
            />
            <input
                type="text"
                :placeholder="placeholder"
                class="bg-gray-50 border border-gray-300 rounded-md px-4 py-2 text-gray-900 transition-all duration-200 pl-10 pr-4 w-80"
            />
            <span
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-400"
                >⌘F</span
            >
        </div>
    </div>
</template>
