import { ref, computed } from "vue";
import ApiService from "@/services/apiService";
import { useNotifications } from "@/composables/useNotifications";

const downloadingIds = ref(new Set());
const downloadError = ref(null);

export function useTorrentActions() {
    const { showSuccess, showError } = useNotifications();

    /**
     * Download a torrent
     *
     * @param {Object} torrent - Torrent object with magnet_link and/or torrent_link
     * @param {Function} onSuccess - Optional callback on success
     * @param {Function} onError - Optional callback on error
     * @returns {Promise}
     */
    const downloadTorrent = async (
        torrent,
        onSuccess = null,
        onError = null
    ) => {
        // Create a unique identifier for this torrent (use magnet_link or title as fallback)
        const torrentId =
            torrent.magnet_link || torrent.torrent_link || torrent.title;

        if (!torrentId) {
            const error = new Error("Torrent identifier not found");
            if (onError) onError(error);
            showError("Torrent identifier not found");
            throw error;
        }

        if (downloadingIds.value.has(torrentId)) {
            return; // Already downloading
        }

        downloadingIds.value.add(torrentId);
        downloadError.value = null;

        try {
            const response = await ApiService.post("/torrents/download", {
                magnet_link: torrent.magnet_link,
                torrent_link: torrent.torrent_link,
                source_url: torrent.source_url,
                title: torrent.title,
                size: torrent.size,
                seeders: torrent.seeders,
                leechers: torrent.leechers,
            });

            if (response.success) {
                const successMessage =
                    response.message || "Torrent download started successfully";
                showSuccess(successMessage);
                if (onSuccess) {
                    onSuccess({ 
                        torrent, 
                        message: response.message,
                        fileId: response.file_id, // Include file_id for status tracking
                    });
                }
                return {
                    success: true,
                    message: response.message,
                    fileId: response.file_id,
                    data: response,
                };
            } else {
                throw new Error(
                    response.message || "Error downloading torrent"
                );
            }
        } catch (error) {
            const errorMessage = error.message || "Failed to download torrent";
            downloadError.value = errorMessage;
            showError(errorMessage);

            if (onError) {
                onError({ torrent, error: errorMessage });
            }

            throw error;
        } finally {
            downloadingIds.value.delete(torrentId);
        }
    };

    /**
     * Copy magnet link to clipboard
     *
     * @param {string} magnetLink - Magnet link to copy
     * @param {Function} onSuccess - Optional callback on success
     * @param {Function} onError - Optional callback on error
     * @returns {Promise}
     */
    const copyMagnetLink = async (
        magnetLink,
        onSuccess = null,
        onError = null
    ) => {
        if (!magnetLink) {
            const error = new Error("Magnet link is required");
            if (onError) onError(error);
            showError("Magnet link is required");
            throw error;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            try {
                await navigator.clipboard.writeText(magnetLink);
                showSuccess("Magnet link copied to clipboard");
                if (onSuccess) {
                    onSuccess(magnetLink);
                }
                return { success: true };
            } catch (error) {
                const errorMessage = "Failed to copy magnet link to clipboard";
                showError(errorMessage);
                if (onError) {
                    onError(new Error(errorMessage));
                }
                throw new Error(errorMessage);
            }
        } else {
            // Fallback for browsers that don't support clipboard API
            const error = new Error("Clipboard API not supported");
            if (onError) onError(error);
            showError("Clipboard API not supported");
            throw error;
        }
    };

    /**
     * Check if a torrent is currently being downloaded
     *
     * @param {Object} torrent - Torrent object
     * @returns {boolean}
     */
    const isDownloading = (torrent) => {
        const torrentId =
            torrent.magnet_link || torrent.torrent_link || torrent.title;
        return downloadingIds.value.has(torrentId);
    };

    /**
     * Get the unique identifier for a torrent
     *
     * @param {Object} torrent - Torrent object
     * @returns {string|null}
     */
    const getTorrentId = (torrent) => {
        return (
            torrent.magnet_link || torrent.torrent_link || torrent.title || null
        );
    };

    /**
     * Clear download error
     */
    const clearDownloadError = () => {
        downloadError.value = null;
    };

    return {
        // State
        downloadError: computed(() => downloadError.value),
        downloadingIds: computed(() => downloadingIds.value),

        // Actions
        downloadTorrent,
        copyMagnetLink,
        isDownloading,
        getTorrentId,
        clearDownloadError,
    };
}
