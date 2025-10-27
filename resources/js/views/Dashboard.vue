<script setup>
import { BellIcon, EnvelopeIcon, ArrowUpIcon } from "@heroicons/vue/24/outline";
import { Card, Search } from "@/components/ui";
</script>

<template>
    <div class="space-y-6">
        <!-- Top Bar -->
        <div
            class="flex items-center justify-between bg-white p-4 rounded-lg shadow-sm border border-gray-200"
        >
            <Search />
            <div class="flex items-center space-x-4">
                <button
                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <EnvelopeIcon class="w-5 h-5 text-gray-600" />
                </button>
                <button
                    class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <BellIcon class="w-5 h-5 text-gray-600" />
                </button>
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-full overflow-hidden">
                        <img
                            src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=32&h=32&fit=crop&crop=face"
                            alt="Profile"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            Totok Michael
                        </p>
                        <p class="text-xs text-gray-500">tmichael20@mail.com</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-6">
            <!-- Total Projects Card -->
            <Card variant="filled" class="bg-gray-100 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">
                            Total Projects
                        </h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900">24</p>
                        <p class="text-sm text-gray-900 mt-1">
                            5 Increased from last month
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-gray-900" />
                    </div>
                </div>
            </Card>

            <!-- Ended Projects Card -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">
                            Ended Projects
                        </h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900">10</p>
                        <p class="text-sm text-gray-500 mt-1">
                            2 Increased from last month
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-gray-600" />
                    </div>
                </div>
            </Card>

            <!-- Running Projects Card -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">
                            Running Projects
                        </h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900">12</p>
                        <p class="text-sm text-gray-500 mt-1">
                            3 Increased from last month
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-gray-600" />
                    </div>
                </div>
            </Card>

            <!-- Pending Project Card -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">
                            Pending Project
                        </h3>
                        <p class="text-3xl font-bold mt-2 text-gray-900">2</p>
                        <p class="text-sm text-gray-500 mt-1">
                            1 Decreased from last month
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowUpIcon class="w-6 h-6 text-gray-600" />
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
