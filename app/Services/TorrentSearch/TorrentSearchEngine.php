<?php

namespace App\Services\TorrentSearch;

use App\Models\Domain;
use App\Services\TorrentSearch\Contracts\TorrentScraperInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class TorrentSearchEngine
{
    /**
     * Search torrents across all active torrent sites
     *
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        $results = [];

        // Get all active torrent sites from database
        $sites = Domain::where('type', 'torrent')
            ->where('is_active', true)
            ->get();

        if ($sites->isEmpty()) {
            return $results;
        }

        // Search each site (sequentially for now to avoid overwhelming servers)
        foreach ($sites as $site) {
            try {
                $siteResults = $this->searchSite($site, $query);
                $results = array_merge($results, $siteResults);
            } catch (Exception $e) {
                Log::error('Torrent search error for ' . $site->name . ': ' . $e->getMessage());
                continue;
            }
        }

        return $results;
    }

    /**
     * Search a specific torrent site
     *
     * @param Domain $site
     * @param string $query
     * @return array
     */
    protected function searchSite(Domain $site, string $query): array
    {
        try {
            $scraper = $this->getScraperForSite($site);

            if (!$scraper) {
                Log::warning("No scraper found for site: {$site->name}");
                return [];
            }

            return $scraper->search($query);
        } catch (Exception $e) {
            Log::error("Error searching site {$site->name}: " . $e->getMessage());
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

        if (!class_exists($scraperClass)) {
            return null;
        }

        try {
            return new $scraperClass($site);
        } catch (Exception $e) {
            Log::error("Error instantiating scraper {$scraperClass}: " . $e->getMessage());
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
        try {
            $defaultHeaders = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ];

            $response = Http::withHeaders(array_merge($defaultHeaders, $headers))
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (Exception $e) {
            Log::error("Error fetching URL {$url}: " . $e->getMessage());
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
