<script setup>
import {
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    EyeIcon,
    ClockIcon,
    UsersIcon,
    DocumentTextIcon,
} from "@heroicons/vue/24/outline";
import { Button, Card, Badge, Progress } from "@/components/ui";

// Mock data for analytics
const analyticsData = {
    overview: {
        totalViews: 125430,
        totalUsers: 8940,
        totalProjects: 156,
        totalRevenue: 45680,
    },
    trends: {
        views: { current: 125430, previous: 118920, change: 5.5 },
        users: { current: 8940, previous: 8230, change: 8.6 },
        projects: { current: 156, previous: 142, change: 9.9 },
        revenue: { current: 45680, previous: 42100, change: 8.5 },
    },
    topPages: [
        { name: "Dashboard", views: 45620, change: 12.5 },
        { name: "Projects", views: 32150, change: -2.3 },
        { name: "Analytics", views: 28940, change: 8.7 },
        { name: "Settings", views: 18720, change: 15.2 },
    ],
    userActivity: [
        { time: "00:00", users: 45 },
        { time: "04:00", users: 32 },
        { time: "08:00", users: 156 },
        { time: "12:00", users: 234 },
        { time: "16:00", users: 189 },
        { time: "20:00", users: 167 },
    ],
    deviceStats: [
        { device: "Desktop", percentage: 65, users: 5811 },
        { device: "Mobile", percentage: 28, users: 2503 },
        { device: "Tablet", percentage: 7, users: 626 },
    ],
    recentActivity: [
        {
            user: "Sarah Johnson",
            action: "Created new project",
            project: "Website Redesign",
            time: "2 minutes ago",
            type: "success",
        },
        {
            user: "Mike Chen",
            action: "Updated project status",
            project: "Mobile App",
            time: "15 minutes ago",
            type: "info",
        },
        {
            user: "Emily Davis",
            action: "Completed task",
            project: "Dashboard Design",
            time: "1 hour ago",
            type: "success",
        },
        {
            user: "John Smith",
            action: "Added new team member",
            project: "E-commerce Platform",
            time: "2 hours ago",
            type: "info",
        },
    ],
};

const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

const getChangeColor = (change) => {
    return change >= 0 ? "text-green-600" : "text-red-600";
};

const getChangeIcon = (change) => {
    return change >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Views -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">
                            Total Views
                        </p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">
                            {{
                                formatNumber(analyticsData.overview.totalViews)
                            }}
                        </p>
                        <div class="flex items-center mt-1">
                            <component
                                :is="
                                    getChangeIcon(
                                        analyticsData.trends.views.change
                                    )
                                "
                                class="w-4 h-4 mr-1"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.views.change
                                    )
                                "
                            />
                            <span
                                class="text-sm font-medium"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.views.change
                                    )
                                "
                            >
                                {{
                                    Math.abs(analyticsData.trends.views.change)
                                }}%
                            </span>
                            <span class="text-sm text-gray-500 ml-1"
                                >vs last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center"
                    >
                        <EyeIcon class="w-6 h-6 text-blue-600" />
                    </div>
                </div>
            </Card>

            <!-- Total Users -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">
                            Total Users
                        </p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">
                            {{
                                formatNumber(analyticsData.overview.totalUsers)
                            }}
                        </p>
                        <div class="flex items-center mt-1">
                            <component
                                :is="
                                    getChangeIcon(
                                        analyticsData.trends.users.change
                                    )
                                "
                                class="w-4 h-4 mr-1"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.users.change
                                    )
                                "
                            />
                            <span
                                class="text-sm font-medium"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.users.change
                                    )
                                "
                            >
                                {{
                                    Math.abs(analyticsData.trends.users.change)
                                }}%
                            </span>
                            <span class="text-sm text-gray-500 ml-1"
                                >vs last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center"
                    >
                        <UsersIcon class="w-6 h-6 text-green-600" />
                    </div>
                </div>
            </Card>

            <!-- Total Projects -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">
                            Total Projects
                        </p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">
                            {{ analyticsData.overview.totalProjects }}
                        </p>
                        <div class="flex items-center mt-1">
                            <component
                                :is="
                                    getChangeIcon(
                                        analyticsData.trends.projects.change
                                    )
                                "
                                class="w-4 h-4 mr-1"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.projects.change
                                    )
                                "
                            />
                            <span
                                class="text-sm font-medium"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.projects.change
                                    )
                                "
                            >
                                {{
                                    Math.abs(
                                        analyticsData.trends.projects.change
                                    )
                                }}%
                            </span>
                            <span class="text-sm text-gray-500 ml-1"
                                >vs last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center"
                    >
                        <DocumentTextIcon class="w-6 h-6 text-purple-600" />
                    </div>
                </div>
            </Card>

            <!-- Total Revenue -->
            <Card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">
                            Total Revenue
                        </p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">
                            ${{
                                formatNumber(
                                    analyticsData.overview.totalRevenue
                                )
                            }}
                        </p>
                        <div class="flex items-center mt-1">
                            <component
                                :is="
                                    getChangeIcon(
                                        analyticsData.trends.revenue.change
                                    )
                                "
                                class="w-4 h-4 mr-1"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.revenue.change
                                    )
                                "
                            />
                            <span
                                class="text-sm font-medium"
                                :class="
                                    getChangeColor(
                                        analyticsData.trends.revenue.change
                                    )
                                "
                            >
                                {{
                                    Math.abs(
                                        analyticsData.trends.revenue.change
                                    )
                                }}%
                            </span>
                            <span class="text-sm text-gray-500 ml-1"
                                >vs last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center"
                    >
                        <ArrowTrendingUpIcon class="w-6 h-6 text-yellow-600" />
                    </div>
                </div>
            </Card>
        </div>

        <!-- Charts and Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- User Activity Chart -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3
                        class="text-2xl font-semibold text-gray-900 leading-tight"
                    >
                        User Activity (24h)
                    </h3>
                    <Badge variant="success">Live</Badge>
                </div>
                <div class="space-y-4">
                    <div class="flex items-end justify-between h-40">
                        <div
                            v-for="(data, index) in analyticsData.userActivity"
                            :key="index"
                            class="flex flex-col items-center"
                        >
                            <div
                                class="w-8 bg-green-500 rounded-t transition-all duration-300 hover:bg-green-600"
                                :style="`height: ${(data.users / 250) * 100}%`"
                                :title="`${data.users} users at ${data.time}`"
                            ></div>
                            <span class="text-xs text-gray-500 mt-2">{{
                                data.time
                            }}</span>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-between text-sm text-gray-600"
                    >
                        <span>Peak: 234 users at 12:00</span>
                        <span>Average: 138 users</span>
                    </div>
                </div>
            </Card>

            <!-- Device Statistics -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3
                        class="text-2xl font-semibold text-gray-900 leading-tight"
                    >
                        Device Statistics
                    </h3>
                    <Button variant="outline" size="sm">View Details</Button>
                </div>
                <div class="space-y-4">
                    <div
                        v-for="device in analyticsData.deviceStats"
                        :key="device.device"
                        class="space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">
                                {{ device.device }}
                            </span>
                            <span class="text-sm text-gray-500">
                                {{ device.users }} users ({{
                                    device.percentage
                                }}%)
                            </span>
                        </div>
                        <Progress
                            :value="device.percentage"
                            :max="100"
                            size="sm"
                            :color="
                                device.device === 'Desktop'
                                    ? 'green'
                                    : device.device === 'Mobile'
                                    ? 'blue'
                                    : 'purple'
                            "
                        />
                    </div>
                </div>
            </Card>

            <!-- Top Pages -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3
                        class="text-2xl font-semibold text-gray-900 leading-tight"
                    >
                        Top Pages
                    </h3>
                    <Button variant="outline" size="sm">View All</Button>
                </div>
                <div class="space-y-4">
                    <div
                        v-for="(page, index) in analyticsData.topPages"
                        :key="page.name"
                        class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                        <div class="flex items-center space-x-3">
                            <div
                                class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-semibold text-gray-600"
                            >
                                {{ index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ page.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ formatNumber(page.views) }} views
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <component
                                :is="getChangeIcon(page.change)"
                                class="w-4 h-4"
                                :class="getChangeColor(page.change)"
                            />
                            <span
                                class="text-sm font-medium"
                                :class="getChangeColor(page.change)"
                            >
                                {{ Math.abs(page.change) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Recent Activity -->
            <Card>
                <div class="flex items-center justify-between mb-6">
                    <h3
                        class="text-2xl font-semibold text-gray-900 leading-tight"
                    >
                        Recent Activity
                    </h3>
                    <Button variant="outline" size="sm">View All</Button>
                </div>
                <div class="space-y-4">
                    <div
                        v-for="activity in analyticsData.recentActivity"
                        :key="`${activity.user}-${activity.time}`"
                        class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors"
                    >
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                            :class="
                                activity.type === 'success'
                                    ? 'bg-green-100 text-green-600'
                                    : 'bg-blue-100 text-blue-600'
                            "
                        >
                            {{
                                activity.user
                                    .split(" ")
                                    .map((n) => n[0])
                                    .join("")
                            }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ activity.user }}
                                <span class="text-gray-600">{{
                                    activity.action
                                }}</span>
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ activity.project }} • {{ activity.time }}
                            </p>
                        </div>
                        <Badge
                            :variant="
                                activity.type === 'success' ? 'success' : 'info'
                            "
                            size="sm"
                        >
                            {{ activity.type }}
                        </Badge>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Performance Metrics -->
        <Card>
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-semibold text-gray-900 leading-tight">
                    Performance Metrics
                </h3>
                <div class="flex items-center space-x-2">
                    <ClockIcon class="w-5 h-5 text-gray-400" />
                    <span class="text-sm text-gray-500"
                        >Last updated 2 minutes ago</span
                    >
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">
                        98.5%
                    </div>
                    <p class="text-sm text-gray-600">Uptime</p>
                    <div class="mt-2">
                        <Progress
                            :value="98.5"
                            :max="100"
                            size="sm"
                            color="green"
                        />
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2">
                        1.2s
                    </div>
                    <p class="text-sm text-gray-600">Average Response Time</p>
                    <div class="mt-2">
                        <Progress
                            :value="85"
                            :max="100"
                            size="sm"
                            color="blue"
                        />
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-2">
                        99.9%
                    </div>
                    <p class="text-sm text-gray-600">Success Rate</p>
                    <div class="mt-2">
                        <Progress
                            :value="99.9"
                            :max="100"
                            size="sm"
                            color="purple"
                        />
                    </div>
                </div>
            </div>
        </Card>
    </div>
</template>
