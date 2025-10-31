<script setup>
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";
import { useNotifications } from "@/composables/useNotifications";

const { notifications, remove } = useNotifications();

const iconMap = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

const typeClasses = {
    success:
        "bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200",
    error: "bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200",
    warning:
        "bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200",
    info: "bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200",
};

const iconColorClasses = {
    success: "text-green-500 dark:text-green-400",
    error: "text-red-500 dark:text-red-400",
    warning: "text-yellow-500 dark:text-yellow-400",
    info: "text-blue-500 dark:text-blue-400",
};

const getNotificationClasses = (type) => {
    return typeClasses[type] || typeClasses.info;
};

const getIconClasses = (type) => {
    return iconColorClasses[type] || iconColorClasses.info;
};
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed top-6 right-6 z-50 flex flex-col gap-3 max-w-md w-full pointer-events-none"
        >
            <TransitionGroup
                name="notification"
                tag="div"
                class="flex flex-col gap-3"
            >
                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    :class="[
                        'pointer-events-auto bg-white dark:bg-gray-800 border rounded-lg shadow-lg p-4 flex items-start gap-3 transition-all duration-300',
                        getNotificationClasses(notification.type),
                    ]"
                >
                    <!-- Icon -->
                    <component
                        :is="
                            iconMap[notification.type] || InformationCircleIcon
                        "
                        :class="[
                            'w-6 h-6 flex-shrink-0 mt-0.5',
                            getIconClasses(notification.type),
                        ]"
                    />

                    <!-- Message -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium break-words">
                            {{ notification.message }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button
                        @click="remove(notification.id)"
                        class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 rounded"
                        aria-label="Close notification"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.notification-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.notification-move {
    transition: transform 0.3s ease;
}
</style>
