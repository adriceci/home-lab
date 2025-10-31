<?php

namespace App\Http\Controllers;

use App\Services\TorrentSearch\TorrentSearchEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TorrentSearchController extends Controller
{
    protected TorrentSearchEngine $searchEngine;

    public function __construct(TorrentSearchEngine $searchEngine)
    {
        $this->searchEngine = $searchEngine;
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
}
