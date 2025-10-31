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

const emit = defineEmits(["update:modelValue", "search", "categories-change"]);

const inputValue = ref(props.modelValue);
let debounceTimer = null;

// Categories with their TPB category codes
const categories = ref({
    Audio: { enabled: false, code: 101 },
    Video: { enabled: false, code: 201 },
    Applications: { enabled: false, code: 301 },
    Games: { enabled: false, code: 401 },
    Other: { enabled: false, code: 600 },
});

const getSelectedCategories = () => {
    return Object.entries(categories.value)
        .filter(([_, cat]) => cat.enabled)
        .map(([name, cat]) => ({ name, code: cat.code }));
};

const toggleCategory = (categoryName) => {
    categories.value[categoryName].enabled =
        !categories.value[categoryName].enabled;
    emit("categories-change", getSelectedCategories());

    // If there's a search query, trigger search with new categories
    if (inputValue.value.trim().length >= 2) {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }
        debounceTimer = setTimeout(() => {
            emit("search", inputValue.value, getSelectedCategories());
        }, 300);
    }
};

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
        emit("search", value, getSelectedCategories());
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
            emit("search", inputValue.value, getSelectedCategories());
        }
    }
};
</script>

<template>
    <div class="space-y-4">
        <!-- Search Input -->
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

        <!-- Category Filters -->
        <div class="flex flex-wrap items-center gap-4">
            <label
                v-for="(category, name) in categories"
                :key="name"
                class="flex items-center space-x-2 cursor-pointer"
            >
                <input
                    type="checkbox"
                    :checked="category.enabled"
                    @change="toggleCategory(name)"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <span
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    :class="{
                        'text-blue-600 dark:text-blue-400': category.enabled,
                    }"
                >
                    {{ name }}
                </span>
            </label>
        </div>
    </div>
</template>
