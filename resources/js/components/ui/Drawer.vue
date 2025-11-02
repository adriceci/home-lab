<script setup>
import { computed, onMounted, onUnmounted, watch } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "",
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg", "xl", "2xl"].includes(value),
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    position: {
        type: String,
        default: "right",
        validator: (value) =>
            ["left", "right", "top", "bottom"].includes(value),
    },
});

const emit = defineEmits(["close", "update:show"]);

const sizeClasses = computed(() => {
    const sizes = {
        sm: "w-80",
        md: "w-96",
        lg: "w-[28rem]",
        xl: "w-[32rem]",
        "2xl": "w-[40rem]",
    };
    return sizes[props.size] || sizes.md;
});

const positionClasses = computed(() => {
    if (props.position === "left") {
        return {
            container: "left-0 pl-0 pr-10 sm:pr-16",
            transition: {
                enterFrom: "-translate-x-full",
                enterTo: "translate-x-0",
                leaveFrom: "translate-x-0",
                leaveTo: "-translate-x-full",
            },
        };
    }

    if (props.position === "top") {
        return {
            container: "top-0 left-0 pt-0 pb-10 sm:pb-16",
            transition: {
                enterFrom: "-translate-y-full",
                enterTo: "translate-y-0",
                leaveFrom: "translate-y-0",
                leaveTo: "-translate-y-full",
            },
        };
    }

    if (props.position === "bottom") {
        return {
            container: "bottom-0 left-0 pt-10 sm:pt-16 pb-0",
            transition: {
                enterFrom: "translate-y-full",
                enterTo: "translate-y-0",
                leaveFrom: "translate-y-0",
                leaveTo: "translate-y-full",
            },
        };
    }

    // Default: right
    return {
        container: "right-0 pl-10 sm:pl-16 pr-0",
        transition: {
            enterFrom: "translate-x-full",
            enterTo: "translate-x-0",
            leaveFrom: "translate-x-0",
            leaveTo: "translate-x-full",
        },
    };
});

const handleClose = () => {
    emit("close");
    emit("update:show", false);
};

const handleEscape = (event) => {
    if (event.key === "Escape" && props.show && props.closeable) {
        handleClose();
    }
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget && props.closeable) {
        handleClose();
    }
};

watch(
    () => props.show,
    (newValue) => {
        if (newValue) {
            document.addEventListener("keydown", handleEscape);
        } else {
            document.removeEventListener("keydown", handleEscape);
        }
    },
    { immediate: true }
);

onMounted(() => {
    if (props.show) {
        document.addEventListener("keydown", handleEscape);
    }
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleEscape);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50"
                @click="handleBackdropClick"
            >
                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div
                            :class="[
                                'pointer-events-none fixed top-0 bottom-0 flex max-w-full',
                                positionClasses.container,
                                props.position === 'top' ||
                                props.position === 'bottom'
                                    ? 'w-full left-0 right-0 flex-col'
                                    : '',
                            ]"
                        >
                            <Transition
                                enter-active-class="transform transition ease-in-out duration-500 sm:duration-700"
                                :enter-from-class="
                                    positionClasses.transition.enterFrom
                                "
                                :enter-to-class="
                                    positionClasses.transition.enterTo
                                "
                                leave-active-class="transform transition ease-in-out duration-500 sm:duration-700"
                                :leave-from-class="
                                    positionClasses.transition.leaveFrom
                                "
                                :leave-to-class="
                                    positionClasses.transition.leaveTo
                                "
                            >
                                <div
                                    v-if="show"
                                    :class="[
                                        'pointer-events-auto h-screen',
                                        sizeClasses,
                                        props.position === 'top' ||
                                        props.position === 'bottom'
                                            ? 'w-full'
                                            : '',
                                    ]"
                                    @click.stop
                                >
                                    <div
                                        class="flex h-full flex-col bg-white dark:bg-gray-800 shadow-xl"
                                    >
                                        <!-- Header -->
                                        <div
                                            v-if="
                                                title ||
                                                $slots.header ||
                                                closeable
                                            "
                                            class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                                        >
                                            <div v-if="$slots.header">
                                                <slot name="header" />
                                            </div>
                                            <h3
                                                v-else-if="title"
                                                class="text-lg font-bold text-gray-900 dark:text-white"
                                            >
                                                {{ title }}
                                            </h3>
                                            <div v-else></div>
                                            <button
                                                v-if="closeable"
                                                @click="handleClose"
                                                class="relative rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:ring-2 focus:ring-gray-500 focus:outline-none transition-colors"
                                                type="button"
                                            >
                                                <span
                                                    class="absolute -inset-2.5"
                                                />
                                                <span class="sr-only"
                                                    >Close</span
                                                >
                                                <XMarkIcon
                                                    class="w-6 h-6"
                                                    aria-hidden="true"
                                                />
                                            </button>
                                        </div>

                                        <!-- Content -->
                                        <div class="flex-1 overflow-y-auto p-6">
                                            <slot />
                                        </div>

                                        <!-- Footer -->
                                        <div
                                            v-if="$slots.footer"
                                            class="p-6 border-t border-gray-200 dark:border-gray-700 flex-shrink-0"
                                        >
                                            <slot name="footer" />
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
