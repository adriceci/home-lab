<script setup>
import { computed } from "vue";

const props = defineProps({
    value: {
        type: Number,
        default: 0,
        validator: (value) => value >= 0 && value <= 100,
    },
    max: {
        type: Number,
        default: 100,
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg"].includes(value),
    },
    variant: {
        type: String,
        default: "default",
        validator: (value) =>
            ["default", "success", "warning", "danger", "info"].includes(value),
    },
    animated: {
        type: Boolean,
        default: false,
    },
    striped: {
        type: Boolean,
        default: false,
    },
    showLabel: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: "",
    },
});

const progressClasses = computed(() => {
    const baseClasses =
        "w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden";

    const sizeClasses = {
        sm: "h-1",
        md: "h-2",
        lg: "h-3",
    };

    return [baseClasses, sizeClasses[props.size]].join(" ");
});

const barClasses = computed(() => {
    const baseClasses = "h-full transition-all duration-300 ease-in-out";

    const variantClasses = {
        default: "bg-green-500",
        success: "bg-green-500",
        warning: "bg-yellow-500",
        danger: "bg-red-500",
        info: "bg-blue-500",
    };

    const animatedClasses = props.animated ? "animate-pulse" : "";
    const stripedClasses = props.striped ? "bg-stripes" : "";

    return [
        baseClasses,
        variantClasses[props.variant],
        animatedClasses,
        stripedClasses,
    ].join(" ");
});

const barStyle = computed(() => {
    const percentage = Math.min(
        Math.max((props.value / props.max) * 100, 0),
        100
    );
    return {
        width: `${percentage}%`,
    };
});

const labelText = computed(() => {
    if (props.label) return props.label;
    if (props.showLabel) return `${Math.round(props.value)}%`;
    return "";
});
</script>

<template>
    <div class="progress-container">
        <!-- Label -->
        <div v-if="labelText" class="flex justify-between items-center mb-1">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ labelText }}
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-300">
                {{ Math.round(value) }}%
            </span>
        </div>

        <!-- Progress Bar -->
        <div :class="progressClasses">
            <div :class="barClasses" :style="barStyle"></div>
        </div>

        <!-- Custom Label Slot -->
        <div v-if="$slots.label" class="mt-1">
            <slot name="label" />
        </div>
    </div>
</template>

<style scoped>
.bg-stripes {
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.15) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.15) 50%,
        rgba(255, 255, 255, 0.15) 75%,
        transparent 75%,
        transparent
    );
    background-size: 1rem 1rem;
    animation: stripes 1s linear infinite;
}

@keyframes stripes {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 1rem 0;
    }
}
</style>
