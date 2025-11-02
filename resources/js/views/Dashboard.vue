<script setup>
import { ref } from "vue";
import { Search, Avatar } from "@/components/ui";
import TorrentResults from "@/components/TorrentResults.vue";
import { useAuth } from "@/composables/useAuth";
import { useTorrentSearch } from "@/composables/useTorrentSearch";

const { user } = useAuth();
const { results, loading, error, searchTorrents, clearResults } =
    useTorrentSearch();

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
            </div>
            <!-- TODO: Change profile -->
            <Avatar :src="user?.avatar" :alt="user?.name" :size="lg" />
        </div>

        <!-- Torrent Results -->
        <TorrentResults
            :results="results"
            :loading="loading"
            :error="error"
            @copyMagnet="() => {}"
        />
    </div>
</template>
