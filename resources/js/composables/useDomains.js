import { ref, computed } from "vue";
import ApiService from "@/services/apiService";
import { useNotifications } from "@/composables/useNotifications";

const domains = ref([]);
const loading = ref(false);
const error = ref(null);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

export function useDomains() {
    const { showSuccess, showError } = useNotifications();

    const fetchDomains = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get("/domains", params);
            
            // Laravel pagination returns: { data: [...], current_page: 1, last_page: 1, ... }
            // ApiService.get() returns response.data from axios, which is the Laravel response
            // So response should be: { data: [...], current_page: 1, ... }
            
            if (response && typeof response === 'object') {
                // Handle paginated response
                if (response.data !== undefined) {
                    domains.value = Array.isArray(response.data) ? response.data : [];
                    pagination.value = {
                        current_page: response.current_page ?? 1,
                        last_page: response.last_page ?? 1,
                        per_page: response.per_page ?? 15,
                        total: response.total ?? 0,
                    };
                } else if (Array.isArray(response)) {
                    // Direct array response (shouldn't happen with pagination)
                    domains.value = response;
                    pagination.value = {
                        current_page: 1,
                        last_page: 1,
                        per_page: response.length,
                        total: response.length,
                    };
                } else {
                    console.warn('Unexpected response structure from /domains:', response);
                    domains.value = [];
                }
            } else {
                domains.value = [];
            }
            
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to fetch domains";
            error.value = errorMessage;
            showError(errorMessage);
            domains.value = [];
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchDomain = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(`/domains/${id}`);
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to fetch domain";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createDomain = async (domainData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.post("/domains", domainData);
            await fetchDomains();
            showSuccess("Domain created successfully. VirusTotal information is being verified.");
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to create domain";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateDomain = async (id, domainData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.put(`/domains/${id}`, domainData);
            await fetchDomains();
            showSuccess("Domain updated successfully");
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to update domain";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteDomain = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            await ApiService.delete(`/domains/${id}`);
            await fetchDomains();
            showSuccess("Domain deleted successfully");
        } catch (err) {
            const errorMessage = err.message || "Failed to delete domain";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const getVirusTotalInfo = async (id) => {
        // Don't set global loading to avoid triggering table refresh
        // This is just for displaying detail information
        try {
            const response = await ApiService.get(`/domains/${id}/virustotal`);
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to fetch VirusTotal information";
            showError(errorMessage);
            throw err;
        }
    };

    const refreshVirusTotalInfo = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.post(`/domains/${id}/refresh-virustotal`);
            showSuccess("VirusTotal information refresh has been queued");
            
            // Refresh the domain list to get updated status
            await fetchDomains();
            
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to refresh VirusTotal information";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const clearError = () => {
        error.value = null;
    };

    const clearDomains = () => {
        domains.value = [];
        pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        };
    };

    return {
        // State
        domains: computed(() => domains.value),
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        pagination: computed(() => pagination.value),

        // Actions
        fetchDomains,
        fetchDomain,
        createDomain,
        updateDomain,
        deleteDomain,
        getVirusTotalInfo,
        refreshVirusTotalInfo,
        clearError,
        clearDomains,
    };
}

