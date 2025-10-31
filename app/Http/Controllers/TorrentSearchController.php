<?php

namespace App\Http\Controllers;

use App\Services\TorrentDownloadService;
use App\Services\TorrentSearch\TorrentSearchEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class TorrentSearchController extends Controller
{
    protected TorrentSearchEngine $searchEngine;
    protected TorrentDownloadService $downloadService;

    public function __construct(
        TorrentSearchEngine $searchEngine,
        TorrentDownloadService $downloadService
    ) {
        $this->searchEngine = $searchEngine;
        $this->downloadService = $downloadService;
    }

    /**
     * Search for torrents across all active torrent sites
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:255',
            'categories' => 'nullable|array',
            'categories.*.name' => 'string',
            'categories.*.code' => 'integer',
        ]);

        try {
            $categories = $validated['categories'] ?? [];
            $results = $this->searchEngine->search($validated['query'], $categories);

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing search: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Initiate torrent download process
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function download(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'magnet_link' => 'nullable|string',
            'torrent_link' => 'nullable|string',
            'source_url' => 'nullable|url',
            'title' => 'nullable|string|max:255',
            'size' => 'nullable|string',
            'seeders' => 'nullable|integer',
            'leechers' => 'nullable|integer',
        ]);

        // Ensure at least one link is provided
        if (empty($validated['magnet_link']) && empty($validated['torrent_link'])) {
            return response()->json([
                'success' => false,
                'message' => 'Either magnet_link or torrent_link must be provided',
            ], 400);
        }

        try {
            // Prepare metadata from request
            $metadata = [
                'title' => $validated['title'] ?? null,
                'size' => $validated['size'] ?? null,
                'seeders' => $validated['seeders'] ?? null,
                'leechers' => $validated['leechers'] ?? null,
            ];

            // Filter out null values
            $metadata = array_filter($metadata, fn($value) => $value !== null);

            // Initiate download process
            $result = $this->downloadService->initiateDownload(
                $validated['magnet_link'] ?? null,
                $validated['torrent_link'] ?? null,
                $validated['source_url'] ?? null,
                $metadata
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Download process initiated successfully',
                'file_id' => $result['file_id'] ?? null,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate download: ' . $e->getMessage(),
            ], 500);
        }
    }
}
