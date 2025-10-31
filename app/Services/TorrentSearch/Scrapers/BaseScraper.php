<?php

namespace App\Services\TorrentSearch\Scrapers;

use App\Models\Domain;
use App\Services\TorrentSearch\Contracts\TorrentScraperInterface;
use App\Services\TorrentSearch\TorrentSearchEngine;

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
     * @return string
     */
    abstract protected function buildSearchUrl(string $query): string;

    /**
     * Perform a search query on the torrent site
     *
     * @param string $query
     * @return array
     */
    public function search(string $query): array
    {
        $url = $this->buildSearchUrl($query);
        $html = TorrentSearchEngine::fetchUrl($url);

        if (!$html) {
            return [];
        }

        return $this->parseResult($html);
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

