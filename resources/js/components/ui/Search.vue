<script setup>
import { ref, watch } from "vue";
import { MagnifyingGlassIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    placeholder: {
        type: String,
        default: "Buscar",
    },
    modelValue: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["update:modelValue", "search"]);

const inputValue = ref(props.modelValue);
let debounceTimer = null;

watch(
    () => props.modelValue,
    (newValue) => {
        inputValue.value = newValue;
    }
);

const debouncedSearch = (value) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    
    debounceTimer = setTimeout(() => {
        emit("search", value);
    }, 400);
};

const handleInput = (event) => {
    const value = event.target.value;
    inputValue.value = value;
    emit("update:modelValue", value);
    
    if (value.trim().length >= 2) {
        debouncedSearch(value);
    } else {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
    }
};

const handleKeyDown = (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        if (inputValue.value.trim().length >= 2) {
            emit("search", inputValue.value);
        }
    }
};
</script>

<template>
    <div class="flex items-center space-x-4">
        <div class="relative">
            <MagnifyingGlassIcon
                class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500"
            />
            <input
                type="text"
                :value="inputValue"
                :placeholder="placeholder"
                @input="handleInput"
                @keydown="handleKeyDown"
                class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md px-4 py-2 text-gray-900 dark:text-white transition-all duration-200 pl-10 pr-4 w-80 placeholder-gray-500 dark:placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <span
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-400 dark:text-gray-500"
                >⌘F</span
            >
        </div>
    </div>
</template>
