<script setup>
import { computed, ref, watch } from "vue";
import {
    ArrowDownTrayIcon,
    LinkIcon,
    CalendarIcon,
    UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { Table } from "@/components/ui";
import { useTorrentActions } from "@/composables/useTorrentActions";
import ApiService from "@/services/apiService";

const props = defineProps({
    results: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["copyMagnet", "download"]);

const hasResults = computed(
    () => localResults.value && localResults.value.length > 0
);

// Use torrent actions composable
const { downloadTorrent, copyMagnetLink, isDownloading, downloadError } =
    useTorrentActions();

// Local reactive copy of results that we can update with magnet links
const localResults = ref([]);

// Track which results are currently fetching magnet links
const fetchingMagnets = ref(new Set());
// Track results with updated magnet links
const resultsWithMagnets = ref({});

// Check if a result is currently fetching its magnet link
const isFetchingMagnet = (row) => {
    const key = row.source_url || row.title;
    return fetchingMagnets.value.has(key);
};

// Check if a result has a magnet link available
const hasMagnetLink = (row) => {
    const key = row.source_url || row.title;
    return row.magnet_link || resultsWithMagnets.value[key] || false;
};

// Get magnet link for a result asynchronously
const fetchMagnetLink = async (row) => {
    const key = row.source_url || row.title;

    // Skip if already has magnet link or is already fetching
    if (hasMagnetLink(row) || fetchingMagnets.value.has(key)) {
        return;
    }

    // Only fetch for 1337x results without magnet links
    if (row.source !== "1337x" || !row.source_url) {
        return;
    }

    fetchingMagnets.value.add(key);

    try {
        const response = await ApiService.post("/torrents/fetch-magnet", {
            source_url: row.source_url,
            source: row.source,
        });

        if (response.success && response.magnet_link) {
            // Store the magnet link for this result
            resultsWithMagnets.value[key] = response.magnet_link;

            // Update the result in local results array
            const resultIndex = localResults.value.findIndex(
                (r) => (r.source_url || r.title) === key
            );
            if (resultIndex !== -1) {
                // Update local results reactively
                localResults.value[resultIndex] = {
                    ...localResults.value[resultIndex],
                    magnet_link: response.magnet_link,
                };
            }
        }
    } catch (error) {
        console.error("Error fetching magnet link:", error);
        // Don't show error to user, just log it
    } finally {
        fetchingMagnets.value.delete(key);
    }
};

// Sync props.results with localResults and trigger magnet link fetching
watch(
    () => props.results,
    (newResults) => {
        localResults.value = newResults ? [...newResults] : [];

        // Fetch magnet links asynchronously for 1337x results without magnet links
        if (newResults && newResults.length > 0) {
            newResults.forEach((result) => {
                if (
                    result.source === "1337x" &&
                    !result.magnet_link &&
                    result.source_url
                ) {
                    // Fetch asynchronously (don't await to avoid blocking)
                    fetchMagnetLink(result);
                }
            });
        }
    },
    { immediate: true, deep: true }
);

const handleCopyMagnetLink = async (magnetLink) => {
    try {
        await copyMagnetLink(
            magnetLink,
            (link) => {
                emit("copyMagnet", link);
            },
            (error) => {
                console.error("Error copying magnet link:", error);
            }
        );
    } catch (error) {
        console.error("Error copying magnet link:", error);
    }
};

const handleDownload = async (row) => {
    try {
        await downloadTorrent(
            row,
            ({ torrent, message }) => {
                emit("download", { row: torrent, message });
                console.log(message);
            },
            ({ torrent, error }) => {
                console.error("Error downloading torrent:", error);
                emit("download", { row: torrent, error });
            }
        );
    } catch (error) {
        // Error is already handled in the composable callback
    }
};

const formatSize = (size) => {
    if (!size) return "N/A";
    return size;
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    try {
        return new Date(dateString).toLocaleDateString();
    } catch {
        return dateString;
    }
};

const getSourceBadgeClass = (source) => {
    const classes = {
        "1337x":
            "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        ThePirateBay:
            "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    };
    return (
        classes[source] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300"
    );
};

const columns = [
    {
        key: "title",
        label: "Título",
        type: "string",
        sortable: true,
    },
    {
        key: "size",
        label: "Tamaño",
        type: "string",
        sortable: true,
    },
    {
        key: "seeders",
        label: "Seeders",
        type: "number",
        sortable: true,
    },
    {
        key: "leechers",
        label: "Leechers",
        type: "number",
        sortable: true,
    },
    {
        key: "upload_date",
        label: "Fecha",
        type: "date",
        sortable: true,
    },
    {
        key: "source",
        label: "Fuente",
        type: "string",
        sortable: true,
    },
    {
        key: "actions",
        label: "Acciones",
        sortable: false,
        searchable: false,
        align: "right",
    },
];
</script>

<template>
    <div v-if="loading" class="flex justify-center items-center py-12">
        <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
        ></div>
    </div>

    <div
        v-else-if="error"
        class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6"
    >
        {{ error }}
    </div>

    <div v-else-if="hasResults" class="space-y-4">
        <Table
            :data="localResults"
            :columns="columns"
            :loading="loading"
            :items-per-page="10"
        >
            <!-- Title Column -->
            <template #cell-title="{ value, row }">
                <div
                    class="text-sm font-medium text-gray-900 dark:text-white max-w-md truncate"
                    :title="row.title"
                >
                    {{ value }}
                </div>
            </template>

            <!-- Size Column -->
            <template #cell-size="{ value }">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatSize(value) }}
                </div>
            </template>

            <!-- Seeders Column -->
            <template #cell-seeders="{ value }">
                <div
                    class="flex items-center text-sm text-green-600 dark:text-green-400"
                >
                    <UserGroupIcon class="w-4 h-4 mr-1" />
                    {{ value }}
                </div>
            </template>

            <!-- Leechers Column -->
            <template #cell-leechers="{ value }">
                <div
                    class="flex items-center text-sm text-red-600 dark:text-red-400"
                >
                    <UserGroupIcon class="w-4 h-4 mr-1" />
                    {{ value }}
                </div>
            </template>

            <!-- Date Column -->
            <template #cell-upload_date="{ value }">
                <div
                    class="flex items-center text-sm text-gray-500 dark:text-gray-400"
                >
                    <CalendarIcon class="w-4 h-4 mr-1" />
                    {{ formatDate(value) }}
                </div>
            </template>

            <!-- Source Column -->
            <template #cell-source="{ value }">
                <span
                    :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        getSourceBadgeClass(value),
                    ]"
                >
                    {{ value }}
                </span>
            </template>

            <!-- Actions Column -->
            <template #cell-actions="{ row }">
                <div
                    class="flex items-center justify-end space-x-2"
                    @click.stop
                >
                    <button
                        v-if="hasMagnetLink(row)"
                        @click="
                            handleCopyMagnetLink(
                                row.magnet_link ||
                                    resultsWithMagnets[
                                        row.source_url || row.title
                                    ]
                            )
                        "
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                        title="Copiar magnet link"
                    >
                        <LinkIcon class="w-4 h-4 mr-1" />
                        Magnet
                    </button>
                    <button
                        @click="
                            handleDownload({
                                ...row,
                                magnet_link:
                                    row.magnet_link ||
                                    resultsWithMagnets[
                                        row.source_url || row.title
                                    ],
                            })
                        "
                        :disabled="
                            isDownloading(row) ||
                            isFetchingMagnet(row) ||
                            (!hasMagnetLink(row) && row.source === '1337x')
                        "
                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="
                            isFetchingMagnet(row)
                                ? 'Obteniendo magnet link...'
                                : !hasMagnetLink(row) && row.source === '1337x'
                                ? 'Esperando magnet link...'
                                : 'Descargar torrent'
                        "
                    >
                        <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
                        <span v-if="isDownloading(row)">...</span>
                        <span v-else-if="isFetchingMagnet(row)"
                            >Cargando...</span
                        >
                        <span
                            v-else-if="
                                !hasMagnetLink(row) && row.source === '1337x'
                            "
                            >Esperando...</span
                        >
                        <span v-else>Descargar</span>
                    </button>
                    <a
                        v-if="row.source_url"
                        :href="row.source_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors"
                        title="Ver en fuente original"
                        @click.stop
                    >
                        Ver
                    </a>
                </div>
            </template>
        </Table>
    </div>

    <div
        v-else
        class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center"
    >
        <p class="text-gray-500 dark:text-gray-400">
            No se encontraron resultados. Intenta con otra búsqueda.
        </p>
    </div>
</template>
