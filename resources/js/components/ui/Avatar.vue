<script setup>
import { computed } from "vue";

const props = defineProps({
    src: {
        type: String,
        default: "",
    },
    alt: {
        type: String,
        default: "",
    },
    size: {
        type: String,
        default: "md",
        validator: (value) =>
            ["xs", "sm", "md", "lg", "xl", "2xl"].includes(value),
    },
    shape: {
        type: String,
        default: "circle",
        validator: (value) => ["circle", "square", "rounded"].includes(value),
    },
    fallback: {
        type: String,
        default: "",
    },
    status: {
        type: String,
        default: "",
        validator: (value) =>
            ["online", "offline", "away", "busy"].includes(value),
    },
    clickable: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["click"]);

const avatarClasses = computed(() => {
    const baseClasses =
        "inline-block overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0 content-center cursor-pointer";

    const sizeClasses = {
        xs: "w-6 h-6",
        sm: "w-8 h-8",
        md: "w-10 h-10",
        lg: "w-12 h-12",
        xl: "w-16 h-16",
        "2xl": "w-20 h-20",
    };

    const shapeClasses = {
        circle: "rounded-full",
        square: "rounded-none",
        rounded: "rounded-lg",
    };

    const clickableClasses = props.clickable
        ? "cursor-pointer hover:opacity-80 transition-opacity"
        : "";

    return [
        baseClasses,
        sizeClasses[props.size],
        shapeClasses[props.shape],
        clickableClasses,
    ].join(" ");
});

const fallbackClasses = computed(() => {
    const baseClasses =
        "flex items-center justify-center text-gray-600 dark:text-gray-200 font-medium";

    const sizeClasses = {
        xs: "text-xs",
        sm: "text-sm",
        md: "text-sm",
        lg: "text-base",
        xl: "text-lg",
        "2xl": "text-xl",
    };

    return [baseClasses, sizeClasses[props.size]].join(" ");
});

const statusClasses = computed(() => {
    if (!props.status) return "";

    const baseClasses =
        "absolute bottom-0 right-0 rounded-full border-2 border-white";

    const sizeClasses = {
        xs: "w-2 h-2",
        sm: "w-2.5 h-2.5",
        md: "w-3 h-3",
        lg: "w-3.5 h-3.5",
        xl: "w-4 h-4",
        "2xl": "w-5 h-5",
    };

    const statusColors = {
        online: "bg-green-500",
        offline: "bg-gray-400",
        away: "bg-yellow-500",
        busy: "bg-red-500",
    };

    return [
        baseClasses,
        sizeClasses[props.size],
        statusColors[props.status],
    ].join(" ");
});

const handleClick = (event) => {
    if (props.clickable) {
        emit("click", event);
    }
};

const getInitials = (name) => {
    if (!name) return "";
    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .join("")
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <div :class="avatarClasses" @click="handleClick">
        <!-- Image Avatar -->
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            class="w-full h-full object-cover"
            @error="$emit('error')"
        />

        <!-- Fallback Avatar -->
        <div v-else :class="fallbackClasses">
            {{ fallback || getInitials(alt) }}
        </div>

        <!-- Status Indicator -->
        <div v-if="status" :class="statusClasses"></div>
    </div>
</template>
