import { ref, computed } from "vue";
import ApiService from "@/services/apiService";

const user = ref(null);
const token = ref(localStorage.getItem("auth_token"));
const loading = ref(false);
const error = ref(null);

// Initialize user from localStorage
const storedUser = localStorage.getItem("user");
if (storedUser) {
    try {
        user.value = JSON.parse(storedUser);
    } catch (e) {
        console.error("Error parsing stored user:", e);
        localStorage.removeItem("user");
        localStorage.removeItem("auth_token");
    }
}

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const isAdmin = computed(() => user.value?.is_admin === true);

    const login = async (credentials) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.post("/login", credentials);
            const { user: userData, token: authToken } = response;

            // Store auth data
            localStorage.setItem("auth_token", authToken);
            localStorage.setItem("user", JSON.stringify(userData));

            user.value = userData;
            token.value = authToken;
            return { user: userData, token: authToken };
        } catch (err) {
            error.value = err.message || "Login failed";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const register = async (userData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.post("/register", userData);
            const { user: newUser, token: authToken } = response;

            // Store auth data
            localStorage.setItem("auth_token", authToken);
            localStorage.setItem("user", JSON.stringify(newUser));

            user.value = newUser;
            token.value = authToken;
            return { user: newUser, token: authToken };
        } catch (err) {
            error.value = err.message || "Registration failed";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const logout = async () => {
        loading.value = true;
        error.value = null;

        try {
            await ApiService.post("/logout");
        } catch (err) {
            console.error("Logout error:", err);
        } finally {
            // Clear auth data regardless of API response
            localStorage.removeItem("auth_token");
            localStorage.removeItem("user");
            user.value = null;
            token.value = null;
            loading.value = false;
        }
    };

    const refreshUser = async () => {
        if (!isAuthenticated.value) return;

        loading.value = true;
        error.value = null;

        try {
            const userData = await ApiService.get("/user");
            user.value = userData;
            return userData;
        } catch (err) {
            error.value = err.message || "Failed to refresh user data";
            // If refresh fails, user might be logged out
            if (err.message?.includes("Unauthorized")) {
                logout();
            }
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const setUser = (userData) => {
        user.value = userData;
        localStorage.setItem("user", JSON.stringify(userData));
    };

    const clearError = () => {
        error.value = null;
    };

    return {
        user: computed(() => user.value),
        token: computed(() => token.value),
        isAuthenticated,
        isAdmin,
        loading: computed(() => loading.value),
        error: computed(() => error.value),
        login,
        register,
        logout,
        refreshUser,
        setUser,
        clearError,
    };
}
