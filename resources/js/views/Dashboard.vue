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

const handleSearch = async (query) => {
    if (query && query.trim().length >= 2) {
        await searchTorrents(query);
    } else {
        clearResults();
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar -->
        <div
            class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <Search
                v-model="searchQuery"
                @search="handleSearch"
                placeholder="Buscar torrents..."
            />
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
