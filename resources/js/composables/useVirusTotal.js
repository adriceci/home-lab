import { ref, computed } from "vue";
import ApiService from "@/services/apiService";
import { useNotifications } from "@/composables/useNotifications";

// Global state
const loading = ref(false);
const error = ref(null);
const scanResult = ref(null);
const report = ref(null);
const quotaInfo = ref(null);
const isConfigured = ref(false);

export function useVirusTotal() {
    const { showSuccess, showError, showInfo } = useNotifications();

    // Computed properties
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => !!error.value);
    const currentError = computed(() => error.value);
    const hasScanResult = computed(() => !!scanResult.value);
    const hasReport = computed(() => !!report.value);

    /**
     * Clear error state
     */
    const clearError = () => {
        error.value = null;
    };

    /**
     * Clear all state
     */
    const clearState = () => {
        error.value = null;
        scanResult.value = null;
        report.value = null;
    };

    /**
     * Scan a URL with VirusTotal
     */
    const scanUrl = async (url) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.post("/virustotal/scan-url", {
                url,
            });
            scanResult.value = response.data;
            showSuccess("URL scan started successfully");
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to scan URL";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get URL analysis report
     */
    const getUrlReport = async (urlId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(
                `/virustotal/url-report/${urlId}`
            );
            report.value = response.data;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to get URL report";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Scan a file with VirusTotal (automatically handles small/large files)
     */
    const scanFile = async (file, onProgress = null) => {
        loading.value = true;
        error.value = null;

        try {
            // Check file size to determine upload method
            const isLargeFile = file.size > 32 * 1024 * 1024; // 32MB

            if (isLargeFile) {
                return await scanLargeFile(file, onProgress);
            } else {
                return await scanSmallFile(file, onProgress);
            }
        } catch (err) {
            const errorMessage = err.message || "Failed to scan file";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Scan small file (< 32MB) directly
     */
    const scanSmallFile = async (file, onProgress = null) => {
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
            const response = await ApiService.uploadFile(
                "/virustotal/scan-file",
                file,
                onProgress
            );
            scanResult.value = response.data;
            showSuccess("File scan started successfully");
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to scan small file";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        }
    };

    /**
     * Scan large file (> 32MB) using upload URL
     */
    const scanLargeFile = async (file, onProgress = null) => {
        try {
            // Get upload URL first
            const uploadUrlResponse = await ApiService.get(
                "/virustotal/large-file-upload-url"
            );
            const uploadUrl = uploadUrlResponse.data.data.upload_url;

            // Upload file to VirusTotal
            const response = await ApiService.uploadLargeFile(
                "/virustotal/scan-file-large",
                file,
                uploadUrl,
                onProgress
            );
            scanResult.value = response.data;
            showSuccess("Large file scan started successfully");
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to scan large file";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        }
    };

    /**
     * Get file analysis report
     */
    const getFileReport = async (fileId) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(
                `/virustotal/file-report/${fileId}`
            );
            report.value = response.data;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to get file report";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get domain information
     */
    const getDomainInfo = async (domain) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(
                `/virustotal/domain/${domain}`
            );
            report.value = response.data;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to get domain information";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get IP address information
     */
    const getIpInfo = async (ip) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(`/virustotal/ip/${ip}`);
            report.value = response.data;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to get IP information";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get file analysis by hash
     */
    const getFileAnalysis = async (hash) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get(
                `/virustotal/file-analysis/${hash}`
            );
            report.value = response.data;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to get file analysis";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Poll for report results (useful for checking scan completion)
     */
    const pollReport = async (
        id,
        type = "file",
        maxAttempts = 30,
        interval = 2000
    ) => {
        let attempts = 0;

        return new Promise((resolve, reject) => {
            const poll = async () => {
                try {
                    const response =
                        type === "url"
                            ? await getUrlReport(id)
                            : await getFileReport(id);

                    // Check if scan is complete
                    const data = response.data.data;
                    const status =
                        data.attributes?.status ||
                        data.attributes?.last_analysis_stats?.status;

                    if (status === "completed" || status === "finished") {
                        resolve(response);
                        return;
                    }

                    if (attempts >= maxAttempts) {
                        reject(
                            new Error(
                                "Polling timeout: Maximum attempts reached"
                            )
                        );
                        return;
                    }

                    attempts++;
                    setTimeout(poll, interval);
                } catch (err) {
                    reject(err);
                }
            };

            poll();
        });
    };

    /**
     * Get API quota information
     */
    const getQuotaInfo = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get("/virustotal/quota");
            quotaInfo.value = response.data;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to get quota information";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Check if VirusTotal is configured
     */
    const checkConfiguration = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await ApiService.get("/virustotal/configured");
            isConfigured.value = response.data.configured;
            return response;
        } catch (err) {
            const errorMessage = err.message || "Failed to check configuration";
            error.value = errorMessage;
            showError(errorMessage);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Get scan statistics from report
     */
    const getScanStats = (reportData) => {
        if (!reportData?.data?.attributes) return null;

        const stats = reportData.data.attributes.last_analysis_stats;
        if (!stats) return null;

        return {
            total: stats.total || 0,
            malicious: stats.malicious || 0,
            suspicious: stats.suspicious || 0,
            undetected: stats.undetected || 0,
            harmless: stats.harmless || 0,
            maliciousPercentage:
                stats.total > 0
                    ? Math.round((stats.malicious / stats.total) * 100)
                    : 0,
            suspiciousPercentage:
                stats.total > 0
                    ? Math.round((stats.suspicious / stats.total) * 100)
                    : 0,
        };
    };

    /**
     * Get detection engines that flagged the item as malicious
     */
    const getMaliciousDetections = (reportData) => {
        if (!reportData?.data?.attributes?.last_analysis_results) return [];

        const results = reportData.data.attributes.last_analysis_results;
        return Object.entries(results)
            .filter(([_, result]) => result.category === "malicious")
            .map(([engine, result]) => ({
                engine,
                result: result.result,
                method: result.method,
            }));
    };

    /**
     * Check if an item is considered malicious based on thresholds
     */
    const isMalicious = (reportData, threshold = 1) => {
        const stats = getScanStats(reportData);
        return stats && stats.malicious >= threshold;
    };

    /**
     * Get risk level based on scan results
     */
    const getRiskLevel = (reportData) => {
        const stats = getScanStats(reportData);
        if (!stats) return "unknown";

        if (stats.malicious >= 5) return "high";
        if (stats.malicious >= 2) return "medium";
        if (stats.malicious >= 1) return "low";
        if (stats.suspicious >= 3) return "suspicious";
        return "clean";
    };

    return {
        // State
        loading: isLoading,
        error: currentError,
        hasError,
        scanResult: computed(() => scanResult.value),
        report: computed(() => report.value),
        hasScanResult,
        hasReport,
        quotaInfo: computed(() => quotaInfo.value),
        isConfigured: computed(() => isConfigured.value),

        // Methods
        clearError,
        clearState,
        scanUrl,
        getUrlReport,
        scanFile,
        scanSmallFile,
        scanLargeFile,
        getFileReport,
        getDomainInfo,
        getIpInfo,
        getFileAnalysis,
        pollReport,
        getQuotaInfo,
        checkConfiguration,

        // Utility methods
        getScanStats,
        getMaliciousDetections,
        isMalicious,
        getRiskLevel,
    };
}
