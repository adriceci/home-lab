<script setup>
import { ref, onMounted } from "vue";
import { BellIcon, EnvelopeIcon, ArrowUpIcon } from "@heroicons/vue/24/outline";
import { Card, Search, Button } from "@/components/ui";
import { useAuth } from "@/composables/useAuth";
import { useAuditLogs } from "@/composables/useAuditLogs";

const { user } = useAuth();
const { auditStats, loading, fetchAuditLogStats } = useAuditLogs();

const loadStats = async () => {
    try {
        await fetchAuditLogStats();
    } catch (error) {
        console.error("Error loading stats:", error);
    }
};

onMounted(() => {
    loadStats();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar -->
        <div
            class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700"
        >
            <Search />
            <div class="flex items-center space-x-4">
                <Button variant="ghost" size="sm" @click="">
                    <EnvelopeIcon
                        class="w-5 h-5 text-gray-600 dark:text-gray-300"
                    />
                </Button>
                <Button variant="ghost" size="sm" @click="">
                    <BellIcon
                        class="w-5 h-5 text-gray-600 dark:text-gray-300"
                    />
                </Button>
                <div class="flex items-center space-x-3">
                    <div
                        class="w-8 h-8 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                    >
                        <span
                            class="text-sm font-medium text-gray-600 dark:text-gray-300"
                        >
                            {{ user?.name?.charAt(0) || "U" }}
                        </span>
                    </div>
                    <div>
                        <p
                            class="text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                            {{ user?.name || "User" }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-6">
            <!-- Total Audit Logs Card -->
            <Card
                variant="filled"
                class="bg-blue-100 dark:bg-blue-900 text-white"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-sm font-medium text-blue-900 dark:text-blue-100"
                        >
                            Total Audit Logs
                        </h3>
                        <p
                            class="text-3xl font-bold mt-2 text-blue-900 dark:text-blue-100"
                        >
                            {{ loading ? "..." : auditStats?.total_logs || 0 }}
                        </p>
                        <p
                            class="text-sm text-blue-800 dark:text-blue-200 mt-1"
                        >
                            System activity tracking
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-blue-200 dark:bg-blue-800 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon
                            class="w-6 h-6 text-blue-900 dark:text-blue-100"
                        />
                    </div>
                </div>
            </Card>

            <!-- Successful Logins Card -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-sm font-medium text-gray-600 dark:text-gray-300"
                        >
                            Successful Logins
                        </h3>
                        <p
                            class="text-3xl font-bold mt-2 text-gray-900 dark:text-gray-100"
                        >
                            {{
                                loading
                                    ? "..."
                                    : auditStats?.successful_logins || 0
                            }}
                        </p>
                        <p
                            class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                        >
                            User authentication events
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-green-600" />
                    </div>
                </div>
            </Card>

            <!-- Failed Logins Card -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-sm font-medium text-gray-600 dark:text-gray-300"
                        >
                            Failed Logins
                        </h3>
                        <p
                            class="text-3xl font-bold mt-2 text-gray-900 dark:text-gray-100"
                        >
                            {{
                                loading ? "..." : auditStats?.failed_logins || 0
                            }}
                        </p>
                        <p
                            class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                        >
                            Security monitoring
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-red-600" />
                    </div>
                </div>
            </Card>

            <!-- Active Users Card -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-sm font-medium text-gray-600 dark:text-gray-300"
                        >
                            Active Users
                        </h3>
                        <p
                            class="text-3xl font-bold mt-2 text-gray-900 dark:text-gray-100"
                        >
                            {{ loading ? "..." : auditStats?.users_count || 0 }}
                        </p>
                        <p
                            class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                        >
                            Registered users
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-purple-600" />
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
