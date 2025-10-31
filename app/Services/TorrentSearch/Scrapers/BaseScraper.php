<?php

namespace App\Services\TorrentSearch\Scrapers;

use App\Models\Domain;
use App\Services\TorrentSearch\Contracts\TorrentScraperInterface;
use App\Services\TorrentSearch\TorrentSearchEngine;
use App\Services\LogEngine;

abstract class BaseScraper implements TorrentScraperInterface
{
    protected Domain $site;

    public function __construct(Domain $site)
    {
        $this->site = $site;
    }

    /**
     * Get the base URL of the torrent site
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->site->url ?? '';
    }

    /**
     * Get the name/identifier of the scraper
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->site->name;
    }

    /**
     * Build search URL for the site
     *
     * @param string $query
     * @param array $categories
     * @return string
     */
    abstract protected function buildSearchUrl(string $query, array $categories = []): string;

    /**
     * Perform a search query on the torrent site
     *
     * @param string $query
     * @param array $categories
     * @return array
     */
    public function search(string $query, array $categories = []): array
    {
        $scraperName = $this->getName();
        $startTime = microtime(true);

        LogEngine::info('torrent_search', '[BaseScraper] Starting search', [
            'scraper' => $scraperName,
            'query' => $query,
            'categories' => $categories,
            'base_url' => $this->getBaseUrl(),
        ]);

        $url = $this->buildSearchUrl($query, $categories);

        LogEngine::info('torrent_search', '[BaseScraper] Built search URL', [
            'scraper' => $scraperName,
            'url' => $url,
            'query' => $query,
        ]);

        $html = TorrentSearchEngine::fetchUrl($url);

        $fetchDuration = round((microtime(true) - $startTime) * 1000, 2);

        if (!$html) {
            LogEngine::error('torrent_search', '[BaseScraper] No HTML returned', [
                'scraper' => $scraperName,
                'url' => $url,
                'fetch_duration_ms' => $fetchDuration,
            ]);
            return [];
        }

        LogEngine::debug('torrent_search', '[BaseScraper] HTML received', [
            'scraper' => $scraperName,
            'html_length' => strlen($html),
            'html_preview' => substr($html, 0, 300) . '...',
            'fetch_duration_ms' => $fetchDuration,
        ]);

        $parseStartTime = microtime(true);
        $results = $this->parseResult($html);
        $parseDuration = round((microtime(true) - $parseStartTime) * 1000, 2);

        LogEngine::info('torrent_search', '[BaseScraper] Search completed', [
            'scraper' => $scraperName,
            'query' => $query,
            'results_count' => count($results),
            'parse_duration_ms' => $parseDuration,
            'total_duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'results_sample' => array_slice($results, 0, 3), // Log first 3 results as sample
        ]);

        return $results;
    }

    /**
     * Parse HTML and extract torrent information
     *
     * @param string $html
     * @return array
     */
    abstract public function parseResult($html): array;

    /**
     * Extract magnet link from HTML or data
     *
     * @param string $html
     * @return string
     */
    protected function extractMagnetLink(string $html): string
    {
        // Try to find magnet link in HTML
        if (preg_match('/href=["\'](magnet:[^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
