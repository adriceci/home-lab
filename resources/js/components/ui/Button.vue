<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: {
        type: String,
        default: "primary",
        validator: (value) =>
            ["primary", "secondary", "outline", "ghost", "danger"].includes(
                value
            ),
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["xs", "sm", "md", "lg", "xl"].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: null,
    },
    iconPosition: {
        type: String,
        default: "left",
        validator: (value) => ["left", "right"].includes(value),
    },
});

const emit = defineEmits(["click"]);

const buttonClasses = computed(() => {
    const baseClasses =
        "inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer";

    const sizeClasses = {
        xs: "px-2 py-1 text-xs",
        sm: "px-3 py-1.5 text-sm",
        md: "px-4 py-2 text-sm",
        lg: "px-6 py-3 text-base",
        xl: "px-8 py-4 text-lg",
    };

    const variantClasses = {
        primary:
            "bg-green-primary hover:bg-green-secondary text-white focus:ring-green-primary shadow-sm hover:shadow-md",
        secondary:
            "bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-white focus:ring-gray-500 border border-gray-300 dark:border-gray-600",
        outline:
            "bg-transparent hover:bg-green-50 dark:hover:bg-green-900/20 text-green-primary border border-green-primary hover:border-green-secondary focus:ring-green-primary",
        ghost: "bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 focus:ring-gray-500",
        danger: "bg-red-500 hover:bg-red-600 text-white focus:ring-red-500 shadow-sm hover:shadow-md",
    };

    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
    ].join(" ");
});

const handleClick = (event) => {
    if (!props.disabled && !props.loading) {
        emit("click", event);
    }
};
</script>

<template>
    <button
        :class="buttonClasses"
        :disabled="disabled || loading"
        @click="handleClick"
    >
        <!-- Loading spinner -->
        <svg
            v-if="loading"
            class="animate-spin -ml-1 mr-2 h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            ></circle>
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
        </svg>

        <!-- Left icon -->
        <component
            v-else-if="icon && iconPosition === 'left'"
            :is="icon"
            class="w-4 h-4 mr-2"
        />

        <!-- Button content -->
        <slot />

        <!-- Right icon -->
        <component
            v-if="icon && iconPosition === 'right'"
            :is="icon"
            class="w-4 h-4 ml-2"
        />
    </button>
</template>
