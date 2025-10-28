<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: {
        type: String,
        default: "default",
        validator: (value) =>
            [
                "default",
                "success",
                "warning",
                "danger",
                "info",
                "primary",
            ].includes(value),
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["xs", "sm", "md", "lg"].includes(value),
    },
    rounded: {
        type: String,
        default: "full",
        validator: (value) =>
            ["none", "sm", "md", "lg", "full"].includes(value),
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const badgeClasses = computed(() => {
    const baseClasses = "inline-flex items-center font-medium";

    const sizeClasses = {
        xs: "px-1.5 py-0.5 text-xs",
        sm: "px-2 py-1 text-xs",
        md: "px-2.5 py-1.5 text-sm",
        lg: "px-3 py-2 text-base",
    };

    const variantClasses = {
        default:
            "bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200",
        success: "bg-green-tertiary text-white",
        warning:
            "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200",
        danger: "bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200",
        info: "bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200",
        primary: "bg-green-primary text-white",
    };

    const roundedClasses = {
        none: "",
        sm: "rounded-sm",
        md: "rounded-md",
        lg: "rounded-lg",
        full: "rounded-full",
    };

    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
        roundedClasses[props.rounded],
    ].join(" ");
});

const dotClasses = computed(() => {
    const baseClasses = "w-2 h-2 rounded-full";

    const variantClasses = {
        default: "bg-gray-400",
        success: "bg-green-tertiary",
        warning: "bg-yellow-500",
        danger: "bg-red-500",
        info: "bg-blue-500",
        primary: "bg-green-primary",
    };

    return [baseClasses, variantClasses[props.variant]].join(" ");
});
</script>

<template>
    <span :class="badgeClasses">
        <!-- Dot variant -->
        <span v-if="dot" :class="dotClasses"></span>

        <!-- Text content -->
        <span v-else>
            <slot />
        </span>
    </span>
</template>
