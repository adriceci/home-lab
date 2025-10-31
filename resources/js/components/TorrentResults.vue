<script setup>
import { computed } from "vue";
import {
    ArrowDownTrayIcon,
    LinkIcon,
    CalendarIcon,
    UserGroupIcon,
} from "@heroicons/vue/24/outline";
import { Table } from "@/components/ui";

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

const hasResults = computed(() => props.results && props.results.length > 0);

const copyMagnetLink = (magnetLink) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(magnetLink).then(() => {
            emit("copyMagnet", magnetLink);
        });
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
        <div class="px-6 py-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Resultados de búsqueda ({{ results.length }})
            </h3>
        </div>

        <Table
            :data="results"
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
                        v-if="row.magnet_link"
                        @click="copyMagnetLink(row.magnet_link)"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                        title="Copiar magnet link"
                    >
                        <LinkIcon class="w-4 h-4 mr-1" />
                        Magnet
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
                        <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
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
