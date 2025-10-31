import { ref, computed } from "vue";
import ApiService from "@/services/apiService";
import { useNotifications } from "@/composables/useNotifications";

const results = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref("");

export function useTorrentSearch() {
    const { showSuccess, showError, showInfo } = useNotifications();

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
                const resultCount = results.value.length;
                if (resultCount > 0) {
                    showSuccess(`Found ${resultCount} torrent${resultCount !== 1 ? 's' : ''}`);
                } else {
                    showInfo("No torrents found for your search");
                }
            } else {
                const errorMessage = response.message || "Error performing search";
                error.value = errorMessage;
                results.value = [];
                showError(errorMessage);
            }
        } catch (err) {
            const errorMessage = err.message || "Failed to search torrents";
            error.value = errorMessage;
            results.value = [];
            showError(errorMessage);
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
