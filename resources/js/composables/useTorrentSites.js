import { ref, computed } from "vue";
import ApiService from "@/services/apiService";

const sites = ref([]);
const loading = ref(false);
const error = ref(null);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

export function useTorrentSites() {
    const fetchSites = async (params = {}) => {
        loading.value = true;
        error.value = null;

        try {
            // Filter by torrent type
            const filters = { ...params, type: "torrent" };
            const response = await ApiService.get("/domains", filters);
            sites.value = response.data;
            pagination.value = {
                current_page: response.current_page,
                last_page: response.last_page,
                per_page: response.per_page,
                total: response.total,
            };
            return response;
        } catch (err) {
            error.value = err.message || "Failed to fetch torrent sites";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchSite = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(`/domains/${id}`);
            return response;
        } catch (err) {
            error.value = err.message || "Failed to fetch torrent site";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createSite = async (siteData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.post("/domains", {
                ...siteData,
                type: "torrent",
            });
            await fetchSites();
            return response;
        } catch (err) {
            error.value = err.message || "Failed to create torrent site";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateSite = async (id, siteData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.put(`/domains/${id}`, {
                ...siteData,
                type: "torrent",
            });
            await fetchSites();
            return response;
        } catch (err) {
            error.value = err.message || "Failed to update torrent site";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const deleteSite = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            await ApiService.delete(`/domains/${id}`);
            await fetchSites();
        } catch (err) {
            error.value = err.message || "Failed to delete torrent site";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const clearError = () => {
        error.value = null;
    };

    const clearSites = () => {
        sites.value = [];
        pagination.value = {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        };
    };

    return {
        // State
        sites: computed(() => sites.value),
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        pagination: computed(() => pagination.value),

        // Actions
        fetchSites,
        fetchSite,
        createSite,
        updateSite,
        deleteSite,
        clearError,
        clearSites,
    };
}

