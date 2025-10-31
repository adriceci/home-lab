<?php

namespace App\Services\TorrentSearch;

use App\Models\Domain;
use App\Services\TorrentSearch\Contracts\TorrentScraperInterface;
use App\Services\LogEngine;
use Illuminate\Support\Facades\Http;
use Exception;

class TorrentSearchEngine
{
    /**
     * Search torrents across all active torrent sites
     *
     * @param string $query
     * @param array $categories
     * @return array
     */
    public function search(string $query, array $categories = []): array
    {
        $results = [];

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Starting search', [
            'query' => $query,
            'query_length' => strlen($query),
            'categories' => $categories,
            'categories_count' => count($categories),
        ]);

        // Get all active torrent sites from database
        $sites = Domain::where('type', 'torrent')
            ->where('is_active', true)
            ->get();

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Found active torrent sites', [
            'sites_count' => $sites->count(),
            'sites' => $sites->pluck('name')->toArray(),
        ]);

        if ($sites->isEmpty()) {
            LogEngine::warning('torrent_search', '[TorrentSearchEngine] No active torrent sites found');
            return $results;
        }

        // Search each site (sequentially for now to avoid overwhelming servers)
        foreach ($sites as $site) {
            LogEngine::info('torrent_search', '[TorrentSearchEngine] Searching site', [
                'site_name' => $site->name,
                'site_url' => $site->url,
                'site_id' => $site->id,
            ]);

            try {
                $startTime = microtime(true);
                $siteResults = $this->searchSite($site, $query, $categories);
                $duration = round((microtime(true) - $startTime) * 1000, 2);

                LogEngine::info('torrent_search', '[TorrentSearchEngine] Site search completed', [
                    'site_name' => $site->name,
                    'results_count' => count($siteResults),
                    'duration_ms' => $duration,
                ]);

                $results = array_merge($results, $siteResults);
            } catch (Exception $e) {
                LogEngine::error('torrent_search', '[TorrentSearchEngine] Site search failed', [
                    'site_name' => $site->name,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Search completed', [
            'total_results' => count($results),
            'query' => $query,
        ]);

        // Filter results to only include torrents with seeders > 100 AND leechers > 100
        $filteredResults = array_filter($results, function ($result) {
            $seeders = (int) ($result['seeders'] ?? 0);
            $leechers = (int) ($result['leechers'] ?? 0);
            return $seeders > 100 && $leechers > 100;
        });

        // Reset array keys to maintain sequential indexing
        $filteredResults = array_values($filteredResults);

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Results filtered by seeders/leechers', [
            'total_results_before_filter' => count($results),
            'total_results_after_filter' => count($filteredResults),
            'query' => $query,
        ]);

        return $filteredResults;
    }

    /**
     * Search a specific torrent site
     *
     * @param Domain $site
     * @param string $query
     * @param array $categories
     * @return array
     */
    protected function searchSite(Domain $site, string $query, array $categories = []): array
    {
        try {
            LogEngine::debug('torrent_search', '[TorrentSearchEngine] Getting scraper for site', [
                'site_name' => $site->name,
            ]);

            $scraper = $this->getScraperForSite($site);

            if (!$scraper) {
                LogEngine::warning('torrent_search', '[TorrentSearchEngine] No scraper found for site', [
                    'site_name' => $site->name,
                    'site_url' => $site->url,
                ]);
                return [];
            }

            LogEngine::debug('torrent_search', '[TorrentSearchEngine] Scraper instance created', [
                'site_name' => $site->name,
                'scraper_class' => get_class($scraper),
            ]);

            return $scraper->search($query, $categories);
        } catch (Exception $e) {
            LogEngine::error('torrent_search', '[TorrentSearchEngine] Error in searchSite', [
                'site_name' => $site->name,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);
            return [];
        }
    }

    /**
     * Get the appropriate scraper instance for a site
     *
     * @param Domain $site
     * @return TorrentScraperInterface|null
     */
    protected function getScraperForSite(Domain $site): ?TorrentScraperInterface
    {
        $scraperClass = $this->getScraperClassName($site->name);

        LogEngine::debug('torrent_search', '[TorrentSearchEngine] Resolving scraper class', [
            'site_name' => $site->name,
            'scraper_class' => $scraperClass,
        ]);

        if (!class_exists($scraperClass)) {
            LogEngine::warning('torrent_search', '[TorrentSearchEngine] Scraper class not found', [
                'site_name' => $site->name,
                'expected_class' => $scraperClass,
            ]);
            return null;
        }

        try {
            $scraper = new $scraperClass($site);
            LogEngine::debug('torrent_search', '[TorrentSearchEngine] Scraper instantiated successfully', [
                'site_name' => $site->name,
                'scraper_class' => $scraperClass,
            ]);
            return $scraper;
        } catch (Exception $e) {
            LogEngine::error('torrent_search', '[TorrentSearchEngine] Error instantiating scraper', [
                'site_name' => $site->name,
                'scraper_class' => $scraperClass,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get the scraper class name based on site name
     *
     * @param string $siteName
     * @return string
     */
    protected function getScraperClassName(string $siteName): string
    {
        // Normalize site name to class name
        $name = str_replace([' ', '-', '.'], '', $siteName);

        // Special cases
        $mapping = [
            '1337x' => 'X1337x',
            'ThePirateBay' => 'ThePirateBay',
        ];

        $normalized = $mapping[$siteName] ?? $name;

        return "App\\Services\\TorrentSearch\\Scrapers\\{$normalized}Scraper";
    }

    /**
     * Fetch HTML content from a URL
     *
     * @param string $url
     * @param array $headers
     * @return string|null
     */
    public static function fetchUrl(string $url, array $headers = []): ?string
    {
        $startTime = microtime(true);

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Fetching URL', [
            'url' => $url,
            'headers_count' => count($headers),
        ]);

        try {
            $defaultHeaders = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ];

            $finalHeaders = array_merge($defaultHeaders, $headers);

            LogEngine::debug('torrent_search', '[TorrentSearchEngine] Making HTTP request', [
                'url' => $url,
                'timeout' => 30, // Increased timeout
                'has_custom_headers' => !empty($headers),
            ]);

            $response = Http::withHeaders($finalHeaders)
                ->timeout(30) // Increased from 10 to 30 seconds
                ->get($url);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $response->status();
            $bodyLength = strlen($response->body());

            LogEngine::info('torrent_search', '[TorrentSearchEngine] HTTP request completed', [
                'url' => $url,
                'status_code' => $statusCode,
                'success' => $response->successful(),
                'body_length' => $bodyLength,
                'duration_ms' => $duration,
            ]);

            if ($response->successful()) {
                LogEngine::debug('torrent_search', '[TorrentSearchEngine] Response successful', [
                    'url' => $url,
                    'content_type' => $response->header('Content-Type'),
                    'body_preview' => substr($response->body(), 0, 200) . '...',
                ]);
                return $response->body();
            }

            LogEngine::warning('torrent_search', '[TorrentSearchEngine] HTTP request unsuccessful', [
                'url' => $url,
                'status_code' => $statusCode,
                'response_body_preview' => substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            LogEngine::error('torrent_search', '[TorrentSearchEngine] Error fetching URL', [
                'url' => $url,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'duration_ms' => $duration,
                'error_trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Standardize torrent result structure
     *
     * @param array $data
     * @param string $source
     * @return array
     */
    public static function standardizeResult(array $data, string $source): array
    {
        return [
            'title' => $data['title'] ?? '',
            'size' => $data['size'] ?? '',
            'seeders' => (int) ($data['seeders'] ?? 0),
            'leechers' => (int) ($data['leechers'] ?? 0),
            'upload_date' => $data['upload_date'] ?? '',
            'magnet_link' => $data['magnet_link'] ?? '',
            'torrent_link' => $data['torrent_link'] ?? null,
            'source' => $source,
            'source_url' => $data['source_url'] ?? '',
        ];
    }
}
