<script setup>
import { ref, onMounted } from "vue";
import { Table } from "@/components/ui";
import ApiService from "@/services/apiService";
import { useDownloadStatus } from "@/composables/useDownloadStatus";

const files = ref([]);
const loading = ref(false);
const error = ref(null);
const currentPage = ref(1);
const perPage = ref(15);
const totalPages = ref(1);
const total = ref(0);

const { getStatusLabel, getStatusColorClass, getProgress } =
    useDownloadStatus();

const columns = [
    {
        key: "name",
        label: "Nombre",
        type: "string",
        sortable: true,
    },
    {
        key: "type",
        label: "Tipo",
        type: "string",
        sortable: true,
    },
    {
        key: "size",
        label: "Tamaño",
        type: "string",
        sortable: false,
    },
    {
        key: "download_status",
        label: "Estado Descarga",
        type: "string",
        sortable: true,
    },
    {
        key: "storage_disk",
        label: "Almacenamiento",
        type: "string",
        sortable: true,
    },
    {
        key: "virustotal_status",
        label: "Estado VirusTotal",
        type: "string",
        sortable: true,
    },
    {
        key: "created_at",
        label: "Fecha Creación",
        type: "date",
        sortable: true,
    },
];

const fetchFiles = async (page = 1) => {
    loading.value = true;
    error.value = null;

    try {
        const response = await ApiService.get(
            "/files",
            {
                per_page: perPage.value,
                page: page,
            },
            true
        );

        if (response.data) {
            files.value = response.data.data || [];
            currentPage.value = response.data.current_page || 1;
            totalPages.value = response.data.last_page || 1;
            total.value = response.data.total || 0;
        }
    } catch (err) {
        error.value = err.message || "Error al cargar los archivos";
        console.error("Error fetching files:", err);
    } finally {
        loading.value = false;
    }
};

const formatSize = (bytes) => {
    if (!bytes || bytes === 0) return "N/A";
    const units = ["B", "KB", "MB", "GB", "TB"];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(2)} ${units[unitIndex]}`;
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString("es-ES", {
            year: "numeric",
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    } catch {
        return dateString;
    }
};

const getStorageDiskLabel = (disk) => {
    const labels = {
        quarantine: "Cuarentena",
        local: "Local",
        public: "Público",
    };
    return labels[disk] || disk;
};

const getStorageDiskBadgeClass = (disk) => {
    const classes = {
        quarantine:
            "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
        local: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        public: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    };
    return (
        classes[disk] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300"
    );
};

const getVirusTotalStatusLabel = (status) => {
    const labels = {
        pending: "Pendiente",
        scanning: "Escaneando",
        completed: "Completado",
        error: "Error",
    };
    return labels[status] || status || "N/A";
};

const getVirusTotalStatusBadgeClass = (status) => {
    const classes = {
        pending:
            "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300",
        scanning:
            "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        completed:
            "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        error: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    };
    return (
        classes[status] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300"
    );
};

onMounted(() => {
    fetchFiles();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Archivos
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Gestión de archivos del sistema
            </p>
        </div>

        <!-- Error Message -->
        <div
            v-if="error"
            class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6"
        >
            {{ error }}
        </div>

        <!-- Files Table -->
        <Table
            :data="files"
            :columns="columns"
            :loading="loading"
            :items-per-page="perPage"
            :enable-pagination="false"
        >
            <!-- Name Column -->
            <template #cell-name="{ value, row }">
                <div
                    class="text-sm font-medium text-gray-900 dark:text-white max-w-md truncate"
                    :title="row.name"
                >
                    {{ value }}
                </div>
            </template>

            <!-- Type Column -->
            <template #cell-type="{ value }">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ value || "N/A" }}
                </div>
            </template>

            <!-- Size Column -->
            <template #cell-size="{ row }">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatSize(row.size) }}
                </div>
            </template>

            <!-- Download Status Column -->
            <template #cell-download_status="{ row }">
                <div class="flex items-center space-x-2">
                    <span
                        v-if="row.download_status"
                        :class="[
                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                            getStatusColorClass(row.download_status),
                        ]"
                    >
                        {{ getStatusLabel(row.download_status) }}
                    </span>
                    <span v-else class="text-sm text-gray-400">N/A</span>
                    <!-- Progress bar for non-terminal states -->
                    <div
                        v-if="
                            row.download_status &&
                            ![
                                'completed',
                                'failed',
                                'cancelled',
                                'url_rejected',
                                'file_rejected',
                            ].includes(row.download_status)
                        "
                        class="flex-1 max-w-xs"
                    >
                        <div
                            class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2"
                        >
                            <div
                                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="{
                                    width:
                                        getProgress(row.download_status) + '%',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Storage Disk Column -->
            <template #cell-storage_disk="{ value }">
                <span
                    v-if="value"
                    :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        getStorageDiskBadgeClass(value),
                    ]"
                >
                    {{ getStorageDiskLabel(value) }}
                </span>
                <span v-else class="text-sm text-gray-400">N/A</span>
            </template>

            <!-- VirusTotal Status Column -->
            <template #cell-virustotal_status="{ value }">
                <span
                    v-if="value"
                    :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        getVirusTotalStatusBadgeClass(value),
                    ]"
                >
                    {{ getVirusTotalStatusLabel(value) }}
                </span>
                <span v-else class="text-sm text-gray-400">N/A</span>
            </template>

            <!-- Created At Column -->
            <template #cell-created_at="{ row }">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ formatDate(row.created_at) }}
                </div>
            </template>
        </Table>

        <!-- Custom Pagination -->
        <div
            v-if="totalPages > 1"
            class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
        >
            <div class="flex-1 flex justify-between sm:hidden">
                <button
                    @click="fetchFiles(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Anterior
                </button>
                <button
                    @click="fetchFiles(currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Siguiente
                </button>
            </div>
            <div
                class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando
                        {{
                            files.length > 0
                                ? (currentPage - 1) * perPage + 1
                                : 0
                        }}
                        a
                        {{ Math.min(currentPage * perPage, total) }} de
                        {{ total }} resultados
                    </p>
                </div>
                <div>
                    <nav
                        class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                    >
                        <button
                            @click="fetchFiles(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>
                        <button
                            v-for="page in totalPages"
                            :key="page"
                            @click="fetchFiles(page)"
                            :class="[
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                page === currentPage
                                    ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 dark:border-blue-700 text-blue-600 dark:text-blue-300'
                                    : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600',
                            ]"
                        >
                            {{ page }}
                        </button>
                        <button
                            @click="fetchFiles(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
