<script setup>
import { computed } from "vue";

const props = defineProps({
    variant: {
        type: String,
        default: "default",
        validator: (value) =>
            ["default", "elevated", "outlined", "filled"].includes(value),
    },
    padding: {
        type: String,
        default: "md",
        validator: (value) => ["none", "sm", "md", "lg", "xl"].includes(value),
    },
    rounded: {
        type: String,
        default: "lg",
        validator: (value) =>
            ["none", "sm", "md", "lg", "xl", "2xl", "full"].includes(value),
    },
    hover: {
        type: Boolean,
        default: false,
    },
    clickable: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["click"]);

const cardClasses = computed(() => {
    const baseClasses =
        "bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition-all duration-200";

    const variantClasses = {
        default: "shadow-sm",
        elevated: "shadow-lg hover:shadow-xl",
        outlined: "shadow-none border-2",
        filled: "bg-gray-50 dark:bg-gray-700 border-gray-100 dark:border-gray-600 shadow-sm",
    };

    const paddingClasses = {
        none: "",
        sm: "p-3",
        md: "p-4",
        lg: "p-6",
        xl: "p-8",
    };

    const roundedClasses = {
        none: "",
        sm: "rounded-sm",
        md: "rounded-md",
        lg: "rounded-lg",
        xl: "rounded-xl",
        "2xl": "rounded-2xl",
        full: "rounded-full",
    };

    const interactiveClasses = props.clickable
        ? "cursor-pointer hover:shadow-md"
        : "";
    const hoverClasses = props.hover
        ? "hover:shadow-md hover:-translate-y-1"
        : "";

    return [
        baseClasses,
        variantClasses[props.variant],
        paddingClasses[props.padding],
        roundedClasses[props.rounded],
        interactiveClasses,
        hoverClasses,
    ].join(" ");
});

const handleClick = (event) => {
    if (props.clickable) {
        emit("click", event);
    }
};
</script>

<template>
    <div :class="cardClasses" @click="handleClick">
        <!-- Card Header -->
        <div
            v-if="$slots.header"
            class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4"
        >
            <slot name="header" />
        </div>

        <!-- Card Content -->
        <div class="card-content">
            <slot />
        </div>

        <!-- Card Footer -->
        <div
            v-if="$slots.footer"
            class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4"
        >
            <slot name="footer" />
        </div>
    </div>
</template>

<style scoped>
.card-content {
    @apply flex-1;
}
</style>
