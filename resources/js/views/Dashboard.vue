<script setup>
import { ref } from "vue";
import { Search, Avatar } from "@/components/ui";
import TorrentResults from "@/components/TorrentResults.vue";
import { useAuth } from "@/composables/useAuth";
import { useTorrentSearch } from "@/composables/useTorrentSearch";

const { user } = useAuth();
const {
    results,
    loading,
    loadingExtended,
    error,
    searchTorrents,
    searchTorrentsExtended,
    clearResults,
} = useTorrentSearch();

const searchQuery = ref("");
const selectedCategories = ref([]);

const handleSearch = async (query, categories = []) => {
    selectedCategories.value = categories;
    if (query && query.trim().length >= 2) {
        await searchTorrents(query, categories);
    } else {
        clearResults();
    }
};

const handleExtendedSearch = async () => {
    if (searchQuery.value && searchQuery.value.trim().length >= 2) {
        await searchTorrentsExtended(
            searchQuery.value,
            selectedCategories.value
        );
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar -->
        <div
            class="flex items-center gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <div class="flex items-center space-x-4 flex-1">
                <div class="flex-1">
                    <Search
                        v-model="searchQuery"
                        @search="handleSearch"
                        @categories-change="
                            (categories) => (selectedCategories = categories)
                        "
                        placeholder="Buscar"
                    />
                </div>
                <button
                    @click="handleExtendedSearch"
                    :disabled="
                        loading ||
                        loadingExtended ||
                        !searchQuery ||
                        searchQuery.trim().length < 2
                    "
                    title="La búsqueda ampliada puede tardar más de lo esperado. Obtiene los magnet links de cada resultado visitando sus páginas de detalle."
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                    <span v-if="loadingExtended" class="flex items-center">
                        <svg
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Buscando...
                    </span>
                    <span v-else>Búsqueda Ampliada</span>
                </button>
            </div>
            <!-- TODO: Change profile -->
            <Avatar :src="user?.avatar" :alt="user?.name" :size="lg" />
        </div>

        <!-- Torrent Results -->
        <TorrentResults
            :results="results"
            :loading="loading || loadingExtended"
            :error="error"
            @copyMagnet="() => {}"
        />
    </div>
</template>
