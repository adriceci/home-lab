<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    type: {
        type: String,
        default: "text",
    },
    placeholder: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "",
    },
    error: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg"].includes(value),
    },
    variant: {
        type: String,
        default: "default",
        validator: (value) => ["default", "filled", "outlined"].includes(value),
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

const emit = defineEmits(["update:modelValue", "focus", "blur", "keydown"]);

const inputRef = ref(null);

const inputClasses = computed(() => {
    const baseClasses =
        "w-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0";

    const sizeClasses = {
        sm: "px-3 py-1.5 text-sm",
        md: "px-3 py-2 text-sm",
        lg: "px-4 py-3 text-base",
    };

    const variantClasses = {
        default:
            "bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md focus:border-green-primary focus:ring-green-primary text-gray-900 dark:text-white",
        filled: "bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md focus:border-green-primary focus:ring-green-primary focus:bg-white dark:focus:bg-gray-600 text-gray-900 dark:text-white",
        outlined:
            "bg-transparent border-2 border-gray-300 dark:border-gray-600 rounded-md focus:border-green-primary focus:ring-green-primary text-gray-900 dark:text-white",
    };

    const stateClasses = props.error
        ? "border-red-500 focus:border-red-500 focus:ring-red-500"
        : "";

    const disabledClasses = props.disabled
        ? "bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed"
        : "";

    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
        stateClasses,
        disabledClasses,
    ].join(" ");
});

const containerClasses = computed(() => {
    const baseClasses = "relative";
    const iconClasses = props.icon
        ? props.iconPosition === "left"
            ? "pl-10"
            : "pr-10"
        : "";
    return [baseClasses, iconClasses].join(" ");
});

const handleInput = (event) => {
    emit("update:modelValue", event.target.value);
};

const handleFocus = (event) => {
    emit("focus", event);
};

const handleBlur = (event) => {
    emit("blur", event);
};

const handleKeydown = (event) => {
    emit("keydown", event);
};

const focus = () => {
    inputRef.value?.focus();
};
</script>

<template>
    <div class="input-container">
        <!-- Label -->
        <label
            v-if="label"
            class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
        >
            {{ label }}
            <span v-if="required" class="text-red-500 ml-1">*</span>
        </label>

        <!-- Input Container -->
        <div :class="containerClasses">
            <!-- Left Icon -->
            <div
                v-if="icon && iconPosition === 'left'"
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
                <component
                    :is="icon"
                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                />
            </div>

            <!-- Input Field -->
            <input
                ref="inputRef"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :class="inputClasses"
                @input="handleInput"
                @focus="handleFocus"
                @blur="handleBlur"
                @keydown="handleKeydown"
            />

            <!-- Right Icon -->
            <div
                v-if="icon && iconPosition === 'right'"
                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
            >
                <component
                    :is="icon"
                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                />
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>

        <!-- Help Text -->
        <p
            v-if="$slots.help && !error"
            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
        >
            <slot name="help" />
        </p>
    </div>
</template>

<style scoped>
.input-container {
    @apply w-full;
}
</style>
