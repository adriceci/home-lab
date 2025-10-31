<script setup>
import { ref, onMounted } from "vue";
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CheckIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";
import { Table } from "@/components/ui";
import { useTorrentSites } from "@/composables/useTorrentSites";

const {
    sites,
    loading,
    error,
    pagination,
    fetchSites,
    createSite,
    updateSite,
    deleteSite,
    clearError,
} = useTorrentSites();

const showModal = ref(false);
const editingSite = ref(null);
const formData = ref({
    name: "",
    url: "",
    description: "",
    is_active: true,
});

const openCreateModal = () => {
    editingSite.value = null;
    formData.value = {
        name: "",
        url: "",
        description: "",
        is_active: true,
    };
    showModal.value = true;
};

const openEditModal = (site) => {
    editingSite.value = site;
    formData.value = {
        name: site.name,
        url: site.url,
        description: site.description || "",
        is_active: site.is_active,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingSite.value = null;
    formData.value = {
        name: "",
        url: "",
        description: "",
        is_active: true,
    };
    clearError();
};

const handleSubmit = async () => {
    try {
        if (editingSite.value) {
            await updateSite(editingSite.value.id, formData.value);
        } else {
            await createSite(formData.value);
        }
        closeModal();
    } catch (err) {
        // Error is handled by composable
    }
};

const handleDelete = async (site) => {
    if (confirm(`¿Estás seguro de eliminar "${site.name}"?`)) {
        try {
            await deleteSite(site.id);
        } catch (err) {
            // Error is handled by composable
        }
    }
};

const columns = [
    {
        key: "name",
        label: "Nombre",
        type: "string",
        sortable: true,
    },
    {
        key: "url",
        label: "URL",
        type: "string",
        sortable: true,
    },
    {
        key: "description",
        label: "Descripción",
        type: "string",
        sortable: true,
    },
    {
        key: "is_active",
        label: "Estado",
        type: "boolean",
        sortable: true,
        sortFn: (a, b) => {
            return a === b ? 0 : a ? 1 : -1;
        },
    },
    {
        key: "actions",
        label: "Acciones",
        sortable: false,
        searchable: false,
        align: "right",
    },
];

onMounted(() => {
    fetchSites();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Gestión de Webs de Torrents
            </h1>
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
            >
                <PlusIcon class="w-5 h-5 mr-2" />
                Agregar Web
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

        <!-- Sites Table -->
        <Table
            v-else
            :data="sites"
            :columns="columns"
            :loading="loading"
            :items-per-page="10"
        >
            <!-- Name Column -->
            <template #cell-name="{ value }">
                <div
                    class="text-sm font-medium text-gray-900 dark:text-white"
                >
                    {{ value }}
                </div>
            </template>

            <!-- URL Column -->
            <template #cell-url="{ value }">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <a
                        :href="value"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-blue-600 dark:text-blue-400 hover:underline"
                        @click.stop
                    >
                        {{ value }}
                    </a>
                </div>
            </template>

            <!-- Description Column -->
            <template #cell-description="{ value }">
                <div
                    class="text-sm text-gray-500 dark:text-gray-400 max-w-md truncate"
                >
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

            <!-- Actions Column -->
            <template #cell-actions="{ row }">
                <div
                    class="flex items-center justify-end space-x-2"
                    @click.stop
                >
                    <button
                        @click="openEditModal(row)"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                    >
                        <PencilIcon class="w-4 h-4 mr-1" />
                        Editar
                    </button>
                    <button
                        @click="handleDelete(row)"
                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                    >
                        <TrashIcon class="w-4 h-4 mr-1" />
                        Eliminar
                    </button>
                </div>
            </template>
        </Table>

        <!-- Create/Edit Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click="closeModal"
        >
            <div
                class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800"
                @click.stop
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{
                            editingSite
                                ? "Editar Web de Torrents"
                                : "Nueva Web de Torrents"
                        }}
                    </h3>
                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            Nombre
                        </label>
                        <input
                            v-model="formData.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            URL
                        </label>
                        <input
                            v-model="formData.url"
                            type="url"
                            required
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
            </div>
        </div>
    </div>
</template>
