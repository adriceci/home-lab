import { ref, computed } from "vue";

// This composable expects the consuming app to provide an API service
// The API service should be compatible with the interface used here
// Default configuration can be overridden via window.auditCenterConfig

const auditLogs = ref([]);
const loading = ref(false);
const error = ref(null);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

// Get API endpoint prefix from config or use default
// Note: Should not start with '/' so ApiService can use its baseURL '/api'
function getApiPrefix() {
    const prefix = window.auditCenterConfig?.apiPrefix || 'audit-logs';
    // Remove leading '/' if present to ensure ApiService uses its baseURL
    return prefix.startsWith('/') ? prefix.substring(1) : prefix;
}

// Get API service - expects a service with get method
function getApiService() {
    // Use the app's API service from window config
    if (window.auditCenterConfig?.apiService) {
        return window.auditCenterConfig.apiService;
    }
    
    // If no API service is found in config, throw an error
    throw new Error(
        'Audit Center: API service not found. Please configure window.auditCenterConfig.apiService ' +
        'in your app.js. Example:\n' +
        'window.auditCenterConfig = { apiService: ApiService, apiPrefix: "audit-logs" };'
    );
}

export function useAuditLogs() {
    const apiPrefix = getApiPrefix();
    let apiService;
    
    try {
        apiService = getApiService();
    } catch (e) {
        console.error(e.message);
        // Return a stub that will fail gracefully
        apiService = {
            get: async () => {
                throw new Error('API service not configured');
            }
        };
    }

    const fetchAuditLogs = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiService.get(`${apiPrefix}`, params);
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
            const response = await apiService.get(`${apiPrefix}/${id}`);
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
            const response = await apiService.get(`${apiPrefix}/stats`, params);
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
