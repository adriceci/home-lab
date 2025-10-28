<script setup>
import { ref, onMounted } from "vue";
import { useAuditLogs } from "@/composables/useAuditLogs";

const {
    auditLogs,
    loading,
    error,
    pagination,
    fetchAuditLogs,
    fetchAuditLogStats,
    clearError,
} = useAuditLogs();

const stats = ref({});
const filters = ref({
    action: "",
    from_date: "",
    to_date: "",
});

const loadData = async () => {
    try {
        const [, statsData] = await Promise.all([
            fetchAuditLogs(filters.value),
            fetchAuditLogStats(filters.value),
        ]);
        stats.value = statsData;
    } catch (err) {
        console.error("Error loading audit logs:", err);
    }
};

const applyFilters = () => {
    clearError();
    loadData();
};

const goToPage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchAuditLogs({ ...filters.value, page });
    }
};

const getActionBadgeClass = (action) => {
    const classes = {
        login: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        logout: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300",
        register:
            "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
        login_failed:
            "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
        create: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
        update: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
        delete: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    };
    return (
        classes[action] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300"
    );
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg
                        class="w-6 h-6 text-blue-600 dark:text-blue-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Total Logs
                    </p>
                    <p
                        class="text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ stats?.total_logs || 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg
                        class="w-6 h-6 text-green-600 dark:text-green-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Successful Logins
                    </p>
                    <p
                        class="text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ stats?.successful_logins || 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                    <svg
                        class="w-6 h-6 text-red-600 dark:text-red-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                        ></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Failed Logins
                    </p>
                    <p
                        class="text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ stats?.failed_logins || 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg
                        class="w-6 h-6 text-purple-600 dark:text-purple-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                        ></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p
                        class="text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Active Users
                    </p>
                    <p
                        class="text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ stats?.users_count || 0 }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >Action</label
                >
                <select
                    v-model="filters.action"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">All Actions</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="register">Register</option>
                    <option value="login_failed">Login Failed</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >From Date</label
                >
                <input
                    v-model="filters.from_date"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
            </div>
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >To Date</label
                >
                <input
                    v-model="filters.to_date"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
            </div>
            <div class="flex items-end">
                <button
                    @click="applyFilters"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <div
        v-if="error"
        class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6"
    >
        {{ error }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
        <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
        ></div>
    </div>

    <!-- Audit Logs Table -->
    <div
        v-else
        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden"
    >
        <div class="overflow-x-auto">
            <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                        >
                            Action
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                        >
                            User
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                        >
                            Description
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                        >
                            IP Address
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                        >
                            Date
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <tr
                        v-for="log in auditLogs"
                        :key="log.id"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="getActionBadgeClass(log.action)"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{ log.action }}
                            </span>
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                        >
                            {{ log.user?.name || "System" }}
                        </td>
                        <td
                            class="px-6 py-4 text-sm text-gray-900 dark:text-white"
                        >
                            {{ log.description }}
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ log.ip_address }}
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ formatDate(log.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="pagination.last_page > 1"
            class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6"
        >
            <div class="flex-1 flex justify-between sm:hidden">
                <button
                    @click="goToPage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Previous
                </button>
                <button
                    @click="goToPage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Next
                </button>
            </div>
            <div
                class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
            >
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Showing
                        {{
                            (pagination.current_page - 1) *
                                pagination.per_page +
                            1
                        }}
                        to
                        {{
                            Math.min(
                                pagination.current_page * pagination.per_page,
                                pagination.total
                            )
                        }}
                        of {{ pagination.total }} results
                    </p>
                </div>
                <div>
                    <nav
                        class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                    >
                        <button
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="goToPage(pagination.current_page + 1)"
                            :disabled="
                                pagination.current_page === pagination.last_page
                            "
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
