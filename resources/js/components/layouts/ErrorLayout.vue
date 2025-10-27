<script setup>
import { useRouter } from "vue-router";
import { HomeIcon, ArrowLeftIcon } from "@heroicons/vue/24/outline";
import { Card } from "@/components/ui";

const router = useRouter();

const goHome = () => {
    router.push("/dashboard");
};

const goBack = () => {
    if (window.history.length > 1) {
        router.go(-1);
    } else {
        goHome();
    }
};
</script>

<template>
    <div class="h-full flex flex-col space-y-6">
        <!-- Action Cards -->
        <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-6">
            <!-- Go Home Card -->
            <Card clickable @click="goHome">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-green-primary">
                            Ir al Inicio
                        </h3>
                        <p class="text-lg font-semibold mt-2 text-gray-900">
                            Dashboard Principal
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            Regresa a la página principal del sistema
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-green-primary rounded-lg flex items-center justify-center"
                    >
                        <HomeIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
            </Card>

            <!-- Go Back Card -->
            <Card clickable @click="goBack">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-green-primary">
                            Volver Atrás
                        </h3>
                        <p class="text-lg font-semibold mt-2 text-gray-900">
                            Página Anterior
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            Regresa a la página donde estabas antes
                        </p>
                    </div>
                    <div
                        class="w-12 h-12 bg-green-primary rounded-lg flex items-center justify-center"
                    >
                        <ArrowLeftIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
            </Card>
        </div>

        <!-- Error Section -->
        <div class="flex-1">
            <Card
                class="text-center h-full flex items-center justify-center"
                padding="xl"
            >
                <div class="space-y-4">
                    <!-- Error Content Slot -->
                    <slot name="error-content">
                        <!-- Default 404 content -->
                        <h1 class="text-8xl font-bold text-green-primary">
                            404
                        </h1>
                        <h2 class="text-2xl font-extrabold text-gray-900">
                            Página no encontrada
                        </h2>
                        <p class="text-sm text-gray-600">
                            Lo sentimos, la página que estás buscando no está
                            disponible.
                        </p>
                    </slot>
                </div>
            </Card>
        </div>
    </div>
</template>
