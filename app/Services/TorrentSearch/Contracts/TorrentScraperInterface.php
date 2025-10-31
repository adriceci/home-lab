<?php

namespace App\Services\TorrentSearch\Contracts;

interface TorrentScraperInterface
{
    /**
     * Perform a search query on the torrent site
     *
     * @param string $query The search query
     * @param array $categories Array of selected categories
     * @return array Array of standardized torrent results
     */
    public function search(string $query, array $categories = []): array;

    /**
     * Get the base URL of the torrent site
     *
     * @return string
     */
    public function getBaseUrl(): string;

    /**
     * Parse HTML/data and extract torrent information
     *
     * @param mixed $data The raw data (HTML, JSON, etc.)
     * @return array Array of standardized torrent results
     */
    public function parseResult($data): array;

    /**
     * Get the name/identifier of the scraper
     *
     * @return string
     */
    public function getName(): string;
}
