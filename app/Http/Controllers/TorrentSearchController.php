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
     * Extended search for torrents with magnet link fetching (option 2)
     * This performs a normal search and then fetches magnet links from detail pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchExtended(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:255',
            'categories' => 'nullable|array',
            'categories.*.name' => 'string',
            'categories.*.code' => 'integer',
        ]);

        try {
            $categories = $validated['categories'] ?? [];
            $results = $this->searchEngine->searchWithMagnets($validated['query'], $categories);

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing extended search: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Fetch magnet link from a torrent detail URL (async)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchMagnetLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_url' => 'required|url',
            'source' => 'required|string',
        ]);

        try {
            // Only fetch magnet links for 1337x (other sources should have them already)
            if ($validated['source'] !== '1337x') {
                return response()->json([
                    'success' => false,
                    'message' => 'Magnet link fetching only supported for 1337x',
                    'magnet_link' => '',
                ], 400);
            }

            // Get the scraper for 1337x
            $sites = \App\Models\Domain::where('type', 'torrent')
                ->where('name', '1337x')
                ->where('is_active', true)
                ->get();

            if ($sites->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '1337x site not found',
                    'magnet_link' => '',
                ], 404);
            }

            $site = $sites->first();
            
            // Create scraper instance directly
            $scraperClass = "App\\Services\\TorrentSearch\\Scrapers\\X1337xScraper";
            if (!class_exists($scraperClass)) {
                return response()->json([
                    'success' => false,
                    'message' => '1337x scraper class not found',
                    'magnet_link' => '',
                ], 500);
            }

            $scraper = new $scraperClass($site);

            $magnetLink = $scraper->fetchMagnetFromDetailUrl($validated['source_url']);

            return response()->json([
                'success' => true,
                'magnet_link' => $magnetLink,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching magnet link: ' . $e->getMessage(),
                'magnet_link' => '',
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
