import { ref, computed } from "vue";
import ApiService from "@/services/apiService";

const auditLogs = ref([]);
const loading = ref(false);
const error = ref(null);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

export function useAuditLogs() {
    const fetchAuditLogs = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get("/audit-logs", params);
            auditLogs.value = response.data;
            pagination.value = {
                current_page: response.current_page,
                last_page: response.last_page,
                per_page: response.per_page,
                total: response.total,
            };
            return response;
        } catch (err) {
            error.value = err.message || "Failed to fetch audit logs";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchAuditLog = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(`/audit-logs/${id}`);
            return response;
        } catch (err) {
            error.value = err.message || "Failed to fetch audit log";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchAuditLogStats = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get("/audit-logs-stats", params);
            return response;
        } catch (err) {
            error.value = err.message || "Failed to fetch audit log statistics";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const clearError = () => {
        error.value = null;
    };

    const clearAuditLogs = () => {
        auditLogs.value = [];
        pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        };
    };

    return {
        // State
        auditLogs: computed(() => auditLogs.value),
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        pagination: computed(() => pagination.value),

        // Actions
        fetchAuditLogs,
        fetchAuditLog,
        fetchAuditLogStats,
        clearError,
        clearAuditLogs,
    };
}
