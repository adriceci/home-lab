import { ref, computed } from "vue";
import ApiService from "@/services/apiService";

const results = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref("");

export function useTorrentSearch() {
    const searchTorrents = async (query, categories = []) => {
        if (!query || query.trim().length < 2) {
            return;
        }

        loading.value = true;
        error.value = null;
        searchQuery.value = query;

        try {
            const response = await ApiService.post("/torrents/search", {
                query: query.trim(),
                categories: categories,
            });

            if (response.success) {
                results.value = response.data || [];
            } else {
                error.value = response.message || "Error performing search";
                results.value = [];
            }
        } catch (err) {
            error.value = err.message || "Failed to search torrents";
            results.value = [];
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const clearResults = () => {
        results.value = [];
        error.value = null;
        searchQuery.value = "";
    };

    const clearError = () => {
        error.value = null;
    };

    return {
        // State
        results: computed(() => results.value),
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        searchQuery: computed(() => searchQuery.value),

        // Actions
        searchTorrents,
        clearResults,
        clearError,
    };
}
