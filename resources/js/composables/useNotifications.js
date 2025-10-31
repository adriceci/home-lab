import { ref, onUnmounted } from "vue";

const notifications = ref([]);
const timers = new Map();

/**
 * Generate unique ID for notification
 */
const generateId = () => {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
};

/**
 * Remove notification by ID
 */
const remove = (id) => {
    const index = notifications.value.findIndex((n) => n.id === id);
    if (index > -1) {
        // Clear timer if exists
        if (timers.has(id)) {
            clearTimeout(timers.get(id));
            timers.delete(id);
        }
        notifications.value.splice(index, 1);
    }
};

/**
 * Add notification with auto-dismiss
 */
const add = (type, message, duration = 5000) => {
    const id = generateId();
    const notification = {
        id,
        type,
        message,
        duration,
        timestamp: Date.now(),
    };

    notifications.value.push(notification);

    // Auto-dismiss after duration
    if (duration > 0) {
        const timer = setTimeout(() => {
            remove(id);
        }, duration);
        timers.set(id, timer);
    }

    return id;
};

/**
 * Show success notification
 */
const showSuccess = (message, duration = 5000) => {
    return add("success", message, duration);
};

/**
 * Show error notification
 */
const showError = (message, duration = 5000) => {
    return add("error", message, duration);
};

/**
 * Show warning notification
 */
const showWarning = (message, duration = 5000) => {
    return add("warning", message, duration);
};

/**
 * Show info notification
 */
const showInfo = (message, duration = 5000) => {
    return add("info", message, duration);
};

/**
 * Clear all notifications
 */
const clear = () => {
    // Clear all timers
    timers.forEach((timer) => clearTimeout(timer));
    timers.clear();
    notifications.value = [];
};

/**
 * Cleanup on unmount
 */
onUnmounted(() => {
    clear();
});

export function useNotifications() {
    return {
        notifications,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        remove,
        clear,
    };
}
