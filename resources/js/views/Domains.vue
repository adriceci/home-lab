<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CheckIcon,
    XMarkIcon,
    InformationCircleIcon,
    ArrowPathIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
} from "@heroicons/vue/24/outline";
import { Table, Drawer } from "@/components/ui";
import { useDomains } from "@/composables/useDomains";

const {
    domains,
    loading,
    error,
    pagination,
    fetchDomains,
    createDomain,
    updateDomain,
    deleteDomain,
    getVirusTotalInfo,
    refreshVirusTotalInfo,
    clearError,
} = useDomains();

const showModal = ref(false);
const showDetailModal = ref(false);
const editingDomain = ref(null);
const selectedDomain = ref(null);
const detailVirusTotalInfo = ref(null);
const formData = ref({
    name: "",
    url: "",
    description: "",
    type: "",
    status: "active",
    is_active: true,
});

const openCreateModal = () => {
    editingDomain.value = null;
    formData.value = {
        name: "",
        url: "",
        description: "",
        type: "",
        status: "active",
        is_active: true,
    };
    showModal.value = true;
};

const openEditModal = (domain) => {
    editingDomain.value = domain;
    formData.value = {
        name: domain.name,
        url: domain.url || "",
        description: domain.description || "",
        type: domain.type || "",
        status: domain.status || "active",
        is_active: domain.is_active,
    };
    showModal.value = true;
};

const openDetailModal = async (domain) => {
    selectedDomain.value = domain;
    try {
        const response = await getVirusTotalInfo(domain.id);
        detailVirusTotalInfo.value = response;
    } catch (err) {
        detailVirusTotalInfo.value = null;
    }
    showDetailModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingDomain.value = null;
    formData.value = {
        name: "",
        url: "",
        description: "",
        type: "",
        status: "active",
        is_active: true,
    };
    clearError();
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedDomain.value = null;
    detailVirusTotalInfo.value = null;
};

const handleSubmit = async () => {
    try {
        if (editingDomain.value) {
            await updateDomain(editingDomain.value.id, formData.value);
        } else {
            await createDomain(formData.value);
        }
        closeModal();
    } catch (err) {
        // Error is handled by composable
    }
};

const handleDelete = async (domain) => {
    if (confirm(`¿Estás seguro de eliminar "${domain.name}"?`)) {
        try {
            await deleteDomain(domain.id);
        } catch (err) {
            // Error is handled by composable
        }
    }
};

const handleRefreshVirusTotal = async (domain) => {
    try {
        await refreshVirusTotalInfo(domain.id);
        // Refresh domains list after a short delay to see updated status
        setTimeout(() => {
            fetchDomains();
        }, 1000);
    } catch (err) {
        // Error is handled by composable
    }
};

// Helper function to get reputation badge color
const getReputationColor = (reputation) => {
    if (reputation === null || reputation === undefined) return "gray";
    if (reputation > 50) return "green";
    if (reputation >= 0) return "yellow";
    return "red";
};

// Helper function to get reputation label
const getReputationLabel = (reputation) => {
    if (reputation === null || reputation === undefined) return "N/A";
    if (reputation > 50) return "Seguro";
    if (reputation >= 0) return "Neutral";
    return "Malicioso";
};

// Helper function to get VirusTotal status badge color
const getVirusTotalStatusColor = (status) => {
    const statusMap = {
        pending: "gray",
        scanning: "blue",
        checked: "green",
        error: "red",
        quota_exceeded: "orange",
        authentication_error: "red",
        not_found: "yellow",
        forbidden: "red",
        timeout: "orange",
        transient_error: "yellow",
        dependency_error: "orange",
        already_exists: "yellow",
        bad_request: "red",
        not_available: "yellow",
    };
    return statusMap[status] || "gray";
};

// Helper function to format date
const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleString("es-ES", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Helper function to get relative time
const getRelativeTime = (date) => {
    if (!date) return "Nunca";
    const now = new Date();
    const then = new Date(date);
    const diffMs = now - then;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "Hace un momento";
    if (diffMins < 60) return `Hace ${diffMins} minutos`;
    if (diffHours < 24) return `Hace ${diffHours} horas`;
    if (diffDays < 7) return `Hace ${diffDays} días`;
    return formatDate(date);
};

const columns = [
    {
        key: "name",
        label: "Dominio",
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
        key: "is_active",
        label: "Estado",
        type: "boolean",
        sortable: true,
    },
    {
        key: "virustotal_reputation",
        label: "Reputación",
        type: "number",
        sortable: true,
    },
    {
        key: "virustotal_votes",
        label: "Votos",
        sortable: false,
    },
    {
        key: "virustotal_status",
        label: "Estado VirusTotal",
        sortable: true,
    },
    {
        key: "virustotal_last_checked_at",
        label: "Última Verificación",
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

// Poll for updates if any domain is pending
let pollInterval = null;
const startPolling = () => {
    if (pollInterval) return;
    
    pollInterval = setInterval(() => {
        // Check if domains.value exists and is an array before using .some()
        if (!domains.value || !Array.isArray(domains.value)) {
            return;
        }
        
        const hasPending = domains.value.some(
            (d) => d.virustotal_status === "pending" || d.virustotal_status === "scanning"
        );
        
        if (hasPending) {
            fetchDomains();
        } else {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }, 5000); // Poll every 5 seconds
};

onMounted(() => {
    fetchDomains();
    startPolling();
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Gestión de Dominios
            </h1>
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
                <PlusIcon class="w-5 h-5 mr-2" />
                Agregar Dominio
            </button>
        </div>

        <!-- Error Message -->
        <div
            v-if="error"
            class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded"
        >
            {{ error }}
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div
                class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
            ></div>
        </div>

        <!-- Domains Table -->
        <Table
            v-else
            :data="domains"
            :columns="columns"
            :loading="loading"
            :items-per-page="15"
        >
            <!-- Domain Name Column -->
            <template #cell-name="{ value }">
                <div
                    class="text-sm font-medium text-gray-900 dark:text-white"
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

            <!-- Status Column -->
            <template #cell-is_active="{ value }">
                <span
                    :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        value
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                    ]"
                >
                    {{ value ? "Activo" : "Inactivo" }}
                </span>
            </template>

            <!-- Reputation Column -->
            <template #cell-virustotal_reputation="{ row }">
                <span
                    :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        getReputationColor(row.virustotal_reputation) === 'green'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                            : getReputationColor(row.virustotal_reputation) === 'yellow'
                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                            : getReputationColor(row.virustotal_reputation) === 'red'
                            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                    ]"
                >
                    {{ row.virustotal_reputation !== null && row.virustotal_reputation !== undefined 
                        ? `${row.virustotal_reputation} - ${getReputationLabel(row.virustotal_reputation)}`
                        : 'N/A' }}
                </span>
            </template>

            <!-- Votes Column -->
            <template #cell-virustotal_votes="{ row }">
                <div class="flex items-center space-x-3">
                    <div class="flex items-center text-green-600 dark:text-green-400">
                        <CheckIcon class="w-4 h-4 mr-1" />
                        <span class="text-sm font-medium">{{ row.virustotal_votes_harmless || 0 }}</span>
                    </div>
                    <div class="flex items-center text-red-600 dark:text-red-400">
                        <XMarkIcon class="w-4 h-4 mr-1" />
                        <span class="text-sm font-medium">{{ row.virustotal_votes_malicious || 0 }}</span>
                    </div>
                </div>
            </template>

            <!-- VirusTotal Status Column -->
            <template #cell-virustotal_status="{ row }">
                <span
                    :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        getVirusTotalStatusColor(row.virustotal_status) === 'green'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                            : getVirusTotalStatusColor(row.virustotal_status) === 'blue'
                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                            : getVirusTotalStatusColor(row.virustotal_status) === 'red'
                            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                            : getVirusTotalStatusColor(row.virustotal_status) === 'orange'
                            ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
                            : getVirusTotalStatusColor(row.virustotal_status) === 'yellow'
                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                    ]"
                >
                    {{ row.virustotal_status || 'pending' }}
                </span>
            </template>

            <!-- Last Checked Column -->
            <template #cell-virustotal_last_checked_at="{ row }">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ getRelativeTime(row.virustotal_last_checked_at) }}
                </div>
            </template>

            <!-- Actions Column -->
            <template #cell-actions="{ row }">
                <div
                    class="flex items-center justify-end space-x-2"
                    @click.stop
                >
                    <button
                        @click="openDetailModal(row)"
                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                        title="Ver detalles"
                    >
                        <InformationCircleIcon class="w-4 h-4" />
                    </button>
                    <button
                        @click="handleRefreshVirusTotal(row)"
                        class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white text-xs font-medium rounded hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors"
                        title="Refrescar información VirusTotal"
                    >
                        <ArrowPathIcon class="w-4 h-4" />
                    </button>
                    <button
                        @click="openEditModal(row)"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                        title="Editar"
                    >
                        <PencilIcon class="w-4 h-4" />
                    </button>
                    <button
                        @click="handleDelete(row)"
                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                        title="Eliminar"
                    >
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </template>
        </Table>

        <!-- Create/Edit Drawer -->
        <Drawer
            :show="showModal"
            :title="editingDomain ? 'Editar Dominio' : 'Nuevo Dominio'"
            size="sm"
            @close="closeModal"
            @update:show="showModal = $event"
        >
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Nombre del Dominio
                    </label>
                    <input
                        v-model="formData.name"
                        type="text"
                        required
                        placeholder="ejemplo.com"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        URL (opcional)
                    </label>
                    <input
                        v-model="formData.url"
                        type="url"
                        placeholder="https://ejemplo.com"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Tipo
                    </label>
                    <input
                        v-model="formData.type"
                        type="text"
                        placeholder="torrent, custom, etc."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Descripción
                    </label>
                    <textarea
                        v-model="formData.description"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    ></textarea>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Estado
                    </label>
                    <select
                        v-model="formData.status"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>

                <div class="flex items-center">
                    <input
                        v-model="formData.is_active"
                        type="checkbox"
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                    />
                    <label
                        class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Activo
                    </label>
                </div>

                <div v-if="!editingDomain" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <InformationCircleIcon class="w-4 h-4 inline mr-1" />
                        Se verificará automáticamente la información de seguridad con VirusTotal al crear el dominio.
                    </p>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <CheckIcon class="w-5 h-5 inline mr-1" />
                        Guardar
                    </button>
                </div>
            </form>
        </Drawer>

        <!-- Detail Drawer -->
        <Drawer
            :show="showDetailModal && !!selectedDomain"
            :title="`Detalles del Dominio: ${selectedDomain?.name || ''}`"
            size="xl"
            @close="closeDetailModal"
            @update:show="showDetailModal = $event"
        >
            <div class="space-y-6">
                <!-- Domain Information -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        Información del Dominio
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedDomain.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedDomain.type || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">URL</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedDomain.url || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedDomain.status || 'N/A' }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ selectedDomain.description || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- VirusTotal Information -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Información de VirusTotal
                        </h4>
                        <button
                            @click="handleRefreshVirusTotal(selectedDomain)"
                            class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white text-xs font-medium rounded hover:bg-yellow-700"
                        >
                            <ArrowPathIcon class="w-4 h-4 mr-1" />
                            Refrescar
                        </button>
                    </div>

                    <div v-if="detailVirusTotalInfo" class="space-y-4">
                        <!-- Reputation -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Reputación</span>
                                <span
                                    :class="[
                                        'px-3 py-1 text-sm font-semibold rounded-full',
                                        getReputationColor(detailVirusTotalInfo.virustotal_reputation) === 'green'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                            : getReputationColor(detailVirusTotalInfo.virustotal_reputation) === 'yellow'
                                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                            : getReputationColor(detailVirusTotalInfo.virustotal_reputation) === 'red'
                                            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                    ]"
                                >
                                    {{ detailVirusTotalInfo.virustotal_reputation !== null 
                                        ? `${detailVirusTotalInfo.virustotal_reputation} - ${getReputationLabel(detailVirusTotalInfo.virustotal_reputation)}`
                                        : 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <!-- Votes -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Votos de la Comunidad</h5>
                            <div class="flex items-center space-x-6">
                                <div class="flex items-center">
                                    <CheckIcon class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Harmless:</span>
                                    <span class="ml-2 text-lg font-semibold text-green-600 dark:text-green-400">
                                        {{ detailVirusTotalInfo.virustotal_votes_harmless || 0 }}
                                    </span>
                                </div>
                                <div class="flex items-center">
                                    <XMarkIcon class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Malicious:</span>
                                    <span class="ml-2 text-lg font-semibold text-red-600 dark:text-red-400">
                                        {{ detailVirusTotalInfo.virustotal_votes_malicious || 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Analysis Stats -->
                        <div v-if="detailVirusTotalInfo.virustotal_last_analysis_stats" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Estadísticas de Análisis</h5>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Harmless</span>
                                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                                        {{ detailVirusTotalInfo.virustotal_last_analysis_stats.harmless || 0 }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Malicious</span>
                                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">
                                        {{ detailVirusTotalInfo.virustotal_last_analysis_stats.malicious || 0 }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Suspicious</span>
                                    <p class="text-lg font-semibold text-yellow-600 dark:text-yellow-400">
                                        {{ detailVirusTotalInfo.virustotal_last_analysis_stats.suspicious || 0 }}
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Undetected</span>
                                    <p class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                                        {{ detailVirusTotalInfo.virustotal_last_analysis_stats.undetected || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div v-if="detailVirusTotalInfo.virustotal_categories && Object.keys(detailVirusTotalInfo.virustotal_categories).length > 0" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Categorías</h5>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="(category, key) in detailVirusTotalInfo.virustotal_categories"
                                    :key="key"
                                    class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs rounded-full"
                                >
                                    {{ category }}
                                </span>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Último Análisis</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(detailVirusTotalInfo.virustotal_last_analysis_date) }}
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Última Verificación</label>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(detailVirusTotalInfo.virustotal_last_checked_at) }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Estado VirusTotal</span>
                                <span
                                    :class="[
                                        'px-3 py-1 text-xs font-semibold rounded-full',
                                        getVirusTotalStatusColor(detailVirusTotalInfo.virustotal_status) === 'green'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                            : getVirusTotalStatusColor(detailVirusTotalInfo.virustotal_status) === 'red'
                                            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                            : getVirusTotalStatusColor(detailVirusTotalInfo.virustotal_status) === 'orange'
                                            ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
                                            : getVirusTotalStatusColor(detailVirusTotalInfo.virustotal_status) === 'yellow'
                                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                    ]"
                                >
                                    {{ detailVirusTotalInfo.virustotal_status || 'pending' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <ExclamationTriangleIcon class="w-4 h-4 inline mr-1" />
                            No hay información de VirusTotal disponible para este dominio.
                        </p>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <button
                        @click="closeDetailModal"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                        Cerrar
                    </button>
                </div>
            </template>
        </Drawer>
    </div>
</template>

