import { ref, computed } from "vue";
import ApiService from "@/services/apiService";

const statusMap = ref(new Map()); // fileId -> status info

// Download status mappings
const downloadStatusLabels = {
    pending: "Pendiente",
    verifying_url: "Verificando URL",
    url_verified: "URL Verificada",
    url_rejected: "URL Rechazada",
    downloading: "Descargando",
    download_completed: "Descarga Completada",
    scanning_file: "Escaneando Archivo",
    file_verified: "Archivo Verificado",
    file_rejected: "Archivo Rechazado",
    moving_to_storage: "Moviendo a Almacenamiento",
    completed: "Completado",
    failed: "Fallido",
    cancelled: "Cancelado",
};

const downloadStatusColors = {
    pending: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300",
    verifying_url:
        "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    url_verified:
        "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    url_rejected: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    downloading:
        "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    download_completed:
        "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    scanning_file:
        "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    file_verified:
        "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    file_rejected: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    moving_to_storage:
        "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300",
    completed:
        "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    failed: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    cancelled: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300",
};

const downloadStatusProgress = {
    pending: 0,
    verifying_url: 10,
    url_verified: 20,
    url_rejected: 0,
    downloading: 40,
    download_completed: 50,
    scanning_file: 70,
    file_verified: 80,
    file_rejected: 0,
    moving_to_storage: 90,
    completed: 100,
    failed: 0,
    cancelled: 0,
};

export function useDownloadStatus() {
    /**
     * Get download status for a file
     *
     * @param {string} fileId - File ID
     * @returns {Promise<Object>}
     */
    const getDownloadStatus = async (fileId) => {
        if (!fileId) {
            return null;
        }

        try {
            const response = await ApiService.get(
                `/files/${fileId}/download-status`,
                {},
                true
            );

            if (response.success && response.data) {
                // Cache the status
                statusMap.value.set(fileId, response.data);
                return response.data;
            }

            return null;
        } catch (error) {
            console.error("Error fetching download status:", error);
            return null;
        }
    };

    /**
     * Poll download status (useful for real-time updates)
     *
     * @param {string} fileId - File ID
     * @param {Function} onUpdate - Callback when status updates
     * @param {number} interval - Polling interval in ms (default: 3000)
     * @returns {Function} Function to stop polling
     */
    const pollDownloadStatus = (fileId, onUpdate, interval = 3000) => {
        if (!fileId) {
            return () => {};
        }

        let pollingInterval = null;

        const poll = async () => {
            const status = await getDownloadStatus(fileId);
            if (status) {
                statusMap.value.set(fileId, status);
                if (onUpdate) {
                    onUpdate(status);
                }

                // Stop polling if status is terminal
                if (status.is_terminal) {
                    stopPolling();
                }
            }
        };

        // Poll immediately
        poll();

        // Then poll at interval
        pollingInterval = setInterval(poll, interval);

        const stopPolling = () => {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        };

        return stopPolling;
    };

    /**
     * Get cached status for a file
     *
     * @param {string} fileId - File ID
     * @returns {Object|null}
     */
    const getCachedStatus = (fileId) => {
        if (!fileId) {
            return null;
        }
        return statusMap.value.get(fileId) || null;
    };

    /**
     * Clear cached status for a file
     *
     * @param {string} fileId - File ID
     */
    const clearCachedStatus = (fileId) => {
        if (fileId) {
            statusMap.value.delete(fileId);
        }
    };

    /**
     * Get status label in Spanish from status string
     *
     * @param {string|Object} status - Status string or status object
     * @returns {string}
     */
    const getStatusLabel = (status) => {
        if (!status) return "Desconocido";

        // If status is an object with label property (from API)
        if (typeof status === "object" && status.label) {
            return status.label;
        }

        // If status is a string, use mapping
        const statusString =
            typeof status === "string" ? status : status.status;
        return (
            downloadStatusLabels[statusString] || statusString || "Desconocido"
        );
    };

    /**
     * Get status color class from status string
     *
     * @param {string|Object} status - Status string or status object
     * @returns {string}
     */
    const getStatusColorClass = (status) => {
        if (!status)
            return "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";

        // If status is an object with color_class property (from API)
        if (typeof status === "object" && status.color_class) {
            return status.color_class;
        }

        // If status is a string, use mapping
        const statusString =
            typeof status === "string" ? status : status.status;
        return (
            downloadStatusColors[statusString] ||
            "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300"
        );
    };

    /**
     * Get progress percentage from status string
     *
     * @param {string|Object} status - Status string or status object
     * @returns {number}
     */
    const getProgress = (status) => {
        if (!status) return 0;

        // If status is an object with progress property (from API)
        if (typeof status === "object" && status.progress !== undefined) {
            return status.progress;
        }

        // If status is a string, use mapping
        const statusString =
            typeof status === "string" ? status : status.status;
        return downloadStatusProgress[statusString] || 0;
    };

    /**
     * Check if status is terminal
     */
    const isTerminal = (status) => {
        if (!status) return false;
        return status.is_terminal || false;
    };

    /**
     * Check if status indicates an error
     */
    const isError = (status) => {
        if (!status) return false;
        return status.is_error || false;
    };

    return {
        // Actions
        getDownloadStatus,
        pollDownloadStatus,
        getCachedStatus,
        clearCachedStatus,

        // Helpers
        getStatusLabel,
        getStatusColorClass,
        getProgress,
        isTerminal,
        isError,

        // State
        statusMap: computed(() => statusMap.value),

        // Mappings (exported for direct access if needed)
        downloadStatusLabels,
        downloadStatusColors,
        downloadStatusProgress,
    };
}
