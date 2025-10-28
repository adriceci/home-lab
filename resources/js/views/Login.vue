<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuth } from "@/composables/useAuth";
import { useTheme } from "@/composables/useTheme";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";
import { Card, Button } from "@/components/ui";

const router = useRouter();
const { login, register, loading, error, clearError } = useAuth();
const { isDark, toggleTheme, initTheme } = useTheme();
const isLogin = ref(true);

onMounted(() => {
    initTheme();
});

const form = reactive({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const toggleMode = () => {
    isLogin.value = !isLogin.value;
    clearError();
    // Reset form
    Object.keys(form).forEach((key) => {
        form[key] = "";
    });
};

const handleSubmit = async () => {
    try {
        if (isLogin.value) {
            await login({
                email: form.email,
                password: form.password,
            });
        } else {
            await register({
                name: form.name,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            });
        }

        // Redirect to dashboard
        router.push("/dashboard");
    } catch (err) {
        // Error is handled by the useAuth composable
        console.error("Authentication error:", err);
    }
};
</script>

<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-md w-full space-y-8">
            <div>
                <!-- Theme Toggle Button -->
                <div class="flex justify-end mb-4">
                    <Button variant="ghost" size="sm" @click="toggleTheme">
                        <component
                            :is="isDark ? SunIcon : MoonIcon"
                            class="w-5 h-5 text-gray-600 dark:text-gray-300"
                        />
                    </Button>
                </div>

                <div
                    class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100 dark:bg-green-900/20"
                >
                    <svg
                        class="h-6 w-6 text-green-primary"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
                <h2
                    class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white"
                >
                    Iniciar sesión
                </h2>
                <p
                    class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400"
                >
                    O
                    <Button variant="ghost" size="sm" @click="toggleMode">
                        {{ isLogin ? "crear una cuenta" : "iniciar sesión" }}
                    </Button>
                </p>
            </div>
            <Card class="mt-8" padding="lg" rounded="2xl">
                <form class="space-y-6" @submit.prevent="handleSubmit">
                    <div class="space-y-4">
                        <div v-if="!isLogin">
                            <label
                                for="name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Nombre completo
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                v-model="form.name"
                                required
                                class="appearance-none relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-primary focus:border-green-primary focus:z-10 sm:text-sm transition-colors"
                                placeholder="Ingrese su nombre completo"
                            />
                        </div>
                        <div>
                            <label
                                for="email"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Dirección de email
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                v-model="form.email"
                                required
                                class="appearance-none relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-primary focus:border-green-primary focus:z-10 sm:text-sm transition-colors"
                                placeholder="Ingrese su email"
                            />
                        </div>
                        <div>
                            <label
                                for="password"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Contraseña
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                v-model="form.password"
                                required
                                class="appearance-none relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-primary focus:border-green-primary focus:z-10 sm:text-sm transition-colors"
                                placeholder="Ingrese su contraseña"
                            />
                        </div>
                        <div v-if="!isLogin">
                            <label
                                for="password_confirmation"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Confirmar contraseña
                            </label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                v-model="form.password_confirmation"
                                required
                                class="appearance-none relative block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white bg-white dark:bg-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-primary focus:border-green-primary focus:z-10 sm:text-sm transition-colors"
                                placeholder="Ingrese su contraseña"
                            />
                        </div>
                    </div>

                    <div
                        v-if="error"
                        class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-red-400 dark:text-red-300"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3
                                    class="text-sm font-medium text-red-800 dark:text-red-200"
                                >
                                    {{ error }}
                                </h3>
                            </div>
                        </div>
                    </div>

                    <div>
                        <Button
                            type="submit"
                            variant="primary"
                            size="lg"
                            :disabled="loading"
                            :loading="loading"
                            class="w-full"
                        >
                            {{
                                loading
                                    ? "Procesando..."
                                    : isLogin
                                    ? "Iniciar sesión"
                                    : "Registrarse"
                            }}
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </div>
</template>
