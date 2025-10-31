<script setup>
import { ref, computed, watch } from "vue";
import {
    MagnifyingGlassIcon,
    ArrowUpIcon,
    ArrowDownIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        required: true,
    },
    itemsPerPage: {
        type: Number,
        default: 10,
    },
    enableSearch: {
        type: Boolean,
        default: true,
    },
    enablePagination: {
        type: Boolean,
        default: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["row-click", "action"]);

const searchQuery = ref("");
const currentPage = ref(1);
const sortColumn = ref(null);
const sortDirection = ref(null); // 'asc', 'desc', or null

const getValue = (row, key) => {
    return key.split(".").reduce((obj, k) => obj?.[k], row);
};

const shouldIncludeInSearch = (column) => {
    return column.searchable !== false;
};

// Detect column type from values
const detectColumnType = (aValue, bValue) => {
    // Check if both values are numbers
    const aNum = Number(aValue);
    const bNum = Number(bValue);
    if (
        aValue !== null &&
        bValue !== null &&
        !isNaN(aNum) &&
        !isNaN(bNum) &&
        (typeof aValue === "number" || typeof bValue === "number")
    ) {
        return "number";
    }

    // Check if both values are dates
    if (aValue && bValue) {
        const aDate = new Date(aValue);
        const bDate = new Date(bValue);
        if (
            !isNaN(aDate.getTime()) &&
            !isNaN(bDate.getTime()) &&
            (aValue instanceof Date ||
                (typeof aValue === "string" &&
                    /^\d{4}-\d{2}-\d{2}/.test(aValue)))
        ) {
            return "date";
        }
    }

    return "string";
};

// Check if column key suggests it's a date column
const isDateColumn = (key) => {
    const dateKeywords = [
        "date",
        "created_at",
        "updated_at",
        "upload_date",
        "timestamp",
        "time",
    ];
    return dateKeywords.some((keyword) => key.toLowerCase().includes(keyword));
};

// Check if column key suggests it's a number column
const isNumberColumn = (key) => {
    const numberKeywords = [
        "seeders",
        "leechers",
        "count",
        "quantity",
        "amount",
        "price",
        "id",
    ];
    return numberKeywords.some((keyword) =>
        key.toLowerCase().includes(keyword)
    );
};

const searchFilter = (row) => {
    if (!searchQuery.value.trim()) return true;

    const query = searchQuery.value.toLowerCase().trim();

    return props.columns.filter(shouldIncludeInSearch).some((column) => {
        const value = getValue(row, column.key);
        if (value === null || value === undefined) return false;

        // Convert to string and search
        const stringValue = String(value).toLowerCase();
        return stringValue.includes(query);
    });
};

const sortData = (data) => {
    if (!sortColumn.value || !sortDirection.value) {
        return [...data];
    }

    const column = props.columns.find((col) => col.key === sortColumn.value);
    if (!column || column.sortable === false) {
        return [...data];
    }

    return [...data].sort((a, b) => {
        const aValue = getValue(a, sortColumn.value);
        const bValue = getValue(b, sortColumn.value);

        let comparison;

        // Use custom sort function if provided
        if (column.sortFn) {
            // sortFn should return comparison for ascending order
            // We'll apply direction inversion here
            comparison = column.sortFn(aValue, bValue);
            return sortDirection.value === "asc" ? comparison : -comparison;
        }

        // Determine type from column definition or auto-detect
        const columnType = column.type || detectColumnType(aValue, bValue);

        // Handle null/undefined values
        if (aValue === null || aValue === undefined) {
            if (bValue === null || bValue === undefined) return 0;
            return 1; // nulls go to end
        }
        if (bValue === null || bValue === undefined) return -1;

        // Date sorting
        if (columnType === "date" || isDateColumn(column.key)) {
            const aDate = new Date(aValue).getTime();
            const bDate = new Date(bValue).getTime();

            // Handle invalid dates
            if (isNaN(aDate) && isNaN(bDate)) return 0;
            if (isNaN(aDate)) return 1;
            if (isNaN(bDate)) return -1;

            comparison = aDate - bDate;
            return sortDirection.value === "asc" ? comparison : -comparison;
        }

        // Number sorting
        if (columnType === "number" || isNumberColumn(column.key)) {
            const aNum = Number(aValue);
            const bNum = Number(bValue);

            // If both are valid numbers, compare them
            if (!isNaN(aNum) && !isNaN(bNum)) {
                comparison = aNum - bNum;
                return sortDirection.value === "asc" ? comparison : -comparison;
            }
        }

        // Boolean sorting
        if (columnType === "boolean") {
            // true comes after false in ascending order
            // Convert to numbers: false = 0, true = 1
            const aBool =
                aValue === true ||
                aValue === 1 ||
                String(aValue).toLowerCase() === "true"
                    ? 1
                    : 0;
            const bBool =
                bValue === true ||
                bValue === 1 ||
                String(bValue).toLowerCase() === "true"
                    ? 1
                    : 0;
            comparison = aBool - bBool;
            return sortDirection.value === "asc" ? comparison : -comparison;
        }

        // String comparison (default)
        const aStr = String(aValue).toLowerCase();
        const bStr = String(bValue).toLowerCase();
        comparison = aStr.localeCompare(bStr);
        return sortDirection.value === "asc" ? comparison : -comparison;
    });
};

const filteredData = computed(() => {
    const filtered = props.data.filter(searchFilter);
    return sortData(filtered);
});

const paginatedData = computed(() => {
    if (!props.enablePagination) {
        return filteredData.value;
    }

    const start = (currentPage.value - 1) * props.itemsPerPage;
    const end = start + props.itemsPerPage;
    return filteredData.value.slice(start, end);
});

const totalPages = computed(() => {
    if (!props.enablePagination) return 1;
    return Math.ceil(filteredData.value.length / props.itemsPerPage);
});

const handleSort = (columnKey) => {
    const column = props.columns.find((col) => col.key === columnKey);
    if (!column || column.sortable === false) return;

    if (sortColumn.value === columnKey) {
        // Cycle through: asc -> desc -> null
        if (sortDirection.value === "asc") {
            sortDirection.value = "desc";
        } else if (sortDirection.value === "desc") {
            sortColumn.value = null;
            sortDirection.value = null;
        }
    } else {
        sortColumn.value = columnKey;
        sortDirection.value = "asc";
    }

    // Reset to first page when sorting changes
    currentPage.value = 1;
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const getSortIcon = (columnKey) => {
    if (sortColumn.value !== columnKey) {
        return null;
    }
    return sortDirection.value === "asc" ? ArrowUpIcon : ArrowDownIcon;
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

// Reset to page 1 when search changes
watch(searchQuery, () => {
    currentPage.value = 1;
});

watch(
    () => props.data,
    () => {
        currentPage.value = 1;
    }
);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <!-- Search Bar -->
        <div
            v-if="enableSearch"
            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"
        >
            <div class="relative">
                <MagnifyingGlassIcon
                    class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-gray-500"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar en la tabla..."
                    class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md px-4 py-2 text-gray-900 dark:text-white pl-10 pr-4 w-full placeholder-gray-500 dark:placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>
            <p
                v-if="enableSearch && data.length > 0"
                class="mt-2 text-sm text-gray-500 dark:text-gray-400"
            >
                Mostrando {{ formatNumber(filteredData.length) }} de
                {{ formatNumber(data.length) }} resultados
            </p>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            :class="[
                                'px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider',
                                column.align === 'right'
                                    ? 'text-right'
                                    : 'text-left',
                                column.sortable !== false
                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 select-none'
                                    : '',
                            ]"
                            @click="
                                column.sortable !== false
                                    ? handleSort(column.key)
                                    : null
                            "
                        >
                            <div
                                class="flex items-center space-x-1"
                                :class="
                                    column.align === 'right'
                                        ? 'justify-end'
                                        : ''
                                "
                            >
                                <span>{{ column.label }}</span>
                                <component
                                    v-if="column.sortable !== false"
                                    :is="getSortIcon(column.key)"
                                    class="w-4 h-4 text-gray-400 dark:text-gray-400"
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <tr v-if="loading" class="bg-white dark:bg-gray-800">
                        <td
                            :colspan="columns.length"
                            class="px-6 py-12 text-center"
                        >
                            <div
                                class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
                            ></div>
                        </td>
                    </tr>
                    <tr
                        v-else-if="paginatedData.length === 0"
                        class="bg-white dark:bg-gray-800"
                    >
                        <td
                            :colspan="columns.length"
                            class="px-6 py-12 text-center text-gray-500 dark:text-gray-400"
                        >
                            {{
                                searchQuery
                                    ? "No se encontraron resultados"
                                    : "No hay datos disponibles"
                            }}
                        </td>
                    </tr>
                    <tr
                        v-else
                        v-for="(row, index) in paginatedData"
                        :key="index"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        @click="emit('row-click', row)"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            :class="[
                                'px-6 py-4 whitespace-nowrap',
                                column.align === 'right'
                                    ? 'text-right'
                                    : 'text-left',
                            ]"
                        >
                            <!-- Custom render slot -->
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="getValue(row, column.key)"
                                :column="column"
                            >
                                <!-- Custom render function -->
                                <template v-if="column.render">
                                    <component
                                        :is="column.render"
                                        :row="row"
                                        :value="getValue(row, column.key)"
                                        :column="column"
                                    />
                                </template>
                                <!-- Default rendering -->
                                <template v-else>
                                    {{ getValue(row, column.key) || "-" }}
                                </template>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="enablePagination && totalPages > 1"
            class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6"
        >
            <div class="flex-1 flex justify-between sm:hidden">
                <button
                    @click="previousPage"
                    :disabled="currentPage === 1"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Anterior
                </button>
                <button
                    @click="nextPage"
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
                            formatNumber(
                                filteredData.length > 0
                                    ? (currentPage - 1) * itemsPerPage + 1
                                    : 0
                            )
                        }}
                        a
                        {{
                            formatNumber(
                                Math.min(
                                    currentPage * itemsPerPage,
                                    filteredData.length
                                )
                            )
                        }}
                        de {{ formatNumber(filteredData.length) }} resultados
                    </p>
                </div>
                <div>
                    <nav
                        class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                    >
                        <button
                            @click="previousPage"
                            :disabled="currentPage === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>
                        <button
                            v-for="page in totalPages"
                            :key="page"
                            @click="goToPage(page)"
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
                            @click="nextPage"
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
