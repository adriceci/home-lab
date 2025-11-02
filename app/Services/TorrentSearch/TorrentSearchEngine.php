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

        // Sort results by seeders (descending) and then by leechers (descending)
        // This ensures results from all platforms are mixed together, ordered by quality
        usort($results, function ($a, $b) {
            // First sort by seeders (descending)
            $seedersCompare = ($b['seeders'] ?? 0) <=> ($a['seeders'] ?? 0);
            if ($seedersCompare !== 0) {
                return $seedersCompare;
            }
            // If seeders are equal, sort by leechers (descending)
            return ($b['leechers'] ?? 0) <=> ($a['leechers'] ?? 0);
        });

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Search completed', [
            'total_results' => count($results),
            'query' => $query,
            'results_by_source' => array_count_values(array_column($results, 'source')),
        ]);

        return $results;
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
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Cache-Control' => 'max-age=0',
                'Referer' => 'https://1337x.to/',
            ];

            // Special handling for 1337x.to to avoid Cloudflare blocks
            // When accessing 1337x, use more realistic browser headers and set referer
            if (strpos($url, '1337x.to') !== false) {
                $defaultHeaders['Referer'] = 'https://1337x.to/';
                // Add a small delay to mimic human behavior (but don't block, just log)
                LogEngine::debug('torrent_search', '[TorrentSearchEngine] 1337x detected, using enhanced headers', [
                    'url' => $url,
                ]);
            }

            $finalHeaders = array_merge($defaultHeaders, $headers);

            LogEngine::debug('torrent_search', '[TorrentSearchEngine] Making HTTP request', [
                'url' => $url,
                'timeout' => 30, // Increased timeout
                'has_custom_headers' => !empty($headers),
                'is_1337x' => strpos($url, '1337x.to') !== false,
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

            // Handle 403 for 1337x - Cloudflare protection
            if ($statusCode === 403 && strpos($url, '1337x.to') !== false) {
                $bodyContent = $response->body();
                $isCloudflareChallenge = strpos($bodyContent, 'Just a moment') !== false || 
                                         strpos($bodyContent, 'cf-browser-verification') !== false ||
                                         strpos($bodyContent, 'challenge-platform') !== false;
                
                if ($isCloudflareChallenge) {
                    LogEngine::warning('torrent_search', '[TorrentSearchEngine] 1337x blocked by Cloudflare', [
                        'url' => $url,
                        'status_code' => $statusCode,
                        'response_preview' => substr($bodyContent, 0, 300),
                        'note' => 'Cloudflare anti-bot protection is blocking the request. This is expected behavior for 1337x.to',
                    ]);
                    return null;
                }
            }

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
     * Search torrents with extended magnet link fetching (option 2)
     * This performs a normal search and then fetches magnet links from detail pages
     *
     * @param string $query
     * @param array $categories
     * @return array
     */
    public function searchWithMagnets(string $query, array $categories = []): array
    {
        LogEngine::info('torrent_search', '[TorrentSearchEngine] Starting extended search with magnet fetching', [
            'query' => $query,
            'categories' => $categories,
        ]);

        // First, do a normal search
        $results = $this->search($query, $categories);

        if (empty($results)) {
            return $results;
        }

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Normal search completed, fetching magnet links', [
            'results_count' => count($results),
        ]);

        // Get active torrent sites to find 1337x scraper
        $sites = Domain::where('type', 'torrent')
            ->where('is_active', true)
            ->get();

        // Find 1337x site and scraper
        $x1337xSite = $sites->firstWhere('name', '1337x');
        if (!$x1337xSite) {
            LogEngine::warning('torrent_search', '[TorrentSearchEngine] 1337x site not found, skipping magnet fetching');
            return $results;
        }

        $scraper = $this->getScraperForSite($x1337xSite);
        if (!$scraper || !($scraper instanceof \App\Services\TorrentSearch\Scrapers\X1337xScraper)) {
            LogEngine::warning('torrent_search', '[TorrentSearchEngine] 1337x scraper not found, skipping magnet fetching');
            return $results;
        }

        $magnetFetched = 0;
        $magnetFailed = 0;

        // For each result from 1337x without magnet_link, try to fetch it
        foreach ($results as &$result) {
            // Only process 1337x results that don't have magnet links and have source_url
            if ($result['source'] === '1337x' && empty($result['magnet_link']) && !empty($result['source_url'])) {
                try {
                    LogEngine::debug('torrent_search', '[TorrentSearchEngine] Fetching magnet link for result', [
                        'title' => substr($result['title'], 0, 50),
                        'source_url' => $result['source_url'],
                    ]);

                    $magnetLink = $scraper->fetchMagnetFromDetailUrl($result['source_url']);

                    if (!empty($magnetLink)) {
                        $result['magnet_link'] = $magnetLink;
                        $magnetFetched++;
                        LogEngine::debug('torrent_search', '[TorrentSearchEngine] Successfully fetched magnet link', [
                            'title' => substr($result['title'], 0, 50),
                        ]);
                    } else {
                        $magnetFailed++;
                        LogEngine::debug('torrent_search', '[TorrentSearchEngine] Failed to fetch magnet link', [
                            'title' => substr($result['title'], 0, 50),
                        ]);
                    }
                } catch (Exception $e) {
                    $magnetFailed++;
                    LogEngine::warning('torrent_search', '[TorrentSearchEngine] Error fetching magnet link', [
                        'title' => substr($result['title'], 0, 50),
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with next result, don't fail the entire search
                }
            }
        }
        unset($result); // Break reference

        // Sort results by seeders (descending) and then by leechers (descending)
        // This ensures results from all platforms are mixed together, ordered by quality
        usort($results, function ($a, $b) {
            // First sort by seeders (descending)
            $seedersCompare = ($b['seeders'] ?? 0) <=> ($a['seeders'] ?? 0);
            if ($seedersCompare !== 0) {
                return $seedersCompare;
            }
            // If seeders are equal, sort by leechers (descending)
            return ($b['leechers'] ?? 0) <=> ($a['leechers'] ?? 0);
        });

        LogEngine::info('torrent_search', '[TorrentSearchEngine] Extended search completed', [
            'total_results' => count($results),
            'magnets_fetched' => $magnetFetched,
            'magnets_failed' => $magnetFailed,
            'results_by_source' => array_count_values(array_column($results, 'source')),
        ]);

        return $results;
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
