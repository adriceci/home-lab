import axios from "axios";

// Create axios instance with default configuration
const apiClient = axios.create({
    baseURL: "/api",
    timeout: 10000,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("auth_token");
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        // Add CSRF token for stateful requests
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        if (csrfToken) {
            config.headers["X-CSRF-TOKEN"] = csrfToken;
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor to handle common errors
apiClient.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Handle 401 Unauthorized
        if (error.response?.status === 401) {
            localStorage.removeItem("auth_token");
            localStorage.removeItem("user");
            window.location.href = "/login";
        }

        // Handle 422 Validation errors
        if (error.response?.status === 422) {
            // Let the component handle validation errors
            return Promise.reject(error);
        }

        // Handle 500 Server errors
        if (error.response?.status >= 500) {
            console.error("Server error:", error.response.data);
        }

        return Promise.reject(error);
    }
);

// API Service class
class ApiService {
    // Generic CRUD methods
    static async get(url, params = {}) {
        try {
            const response = await apiClient.get(url, { params });
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    static async post(url, data = {}) {
        try {
            const response = await apiClient.post(url, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    static async put(url, data = {}) {
        try {
            const response = await apiClient.put(url, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    static async patch(url, data = {}) {
        try {
            const response = await apiClient.patch(url, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    static async delete(url) {
        try {
            const response = await apiClient.delete(url);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    // Utility methods
    static isAuthenticated() {
        return !!localStorage.getItem("auth_token");
    }

    static getStoredUser() {
        const user = localStorage.getItem("user");
        return user ? JSON.parse(user) : null;
    }

    static getAuthToken() {
        return localStorage.getItem("auth_token");
    }

    static clearAuth() {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user");
    }

    // Error handling
    static handleError(error) {
        if (error.response) {
            // Server responded with error status
            const { status, data } = error.response;

            switch (status) {
                case 401:
                    return new Error("Unauthorized. Please log in again.");
                case 403:
                    return new Error(
                        "Forbidden. You do not have permission to perform this action."
                    );
                case 404:
                    return new Error("Resource not found.");
                case 422:
                    // Validation errors - return the original error to preserve validation messages
                    return error;
                case 429:
                    return new Error(
                        "Too many requests. Please try again later."
                    );
                case 500:
                    return new Error(
                        "Internal server error. Please try again later."
                    );
                default:
                    return new Error(
                        data?.message || "An error occurred. Please try again."
                    );
            }
        } else if (error.request) {
            // Network error
            return new Error(
                "Network error. Please check your connection and try again."
            );
        } else {
            // Other error
            return new Error(error.message || "An unexpected error occurred.");
        }
    }

    // Request cancellation
    static createCancelToken() {
        return axios.CancelToken.source();
    }

    // File upload helper
    static async uploadFile(url, file, onProgress = null) {
        const formData = new FormData();
        formData.append("file", file);

        const config = {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        };

        if (onProgress) {
            config.onUploadProgress = (progressEvent) => {
                const percentCompleted = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total
                );
                onProgress(percentCompleted);
            };
        }

        try {
            const response = await apiClient.post(url, formData, config);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }
}

export default ApiService;
