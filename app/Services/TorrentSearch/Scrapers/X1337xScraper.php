<?php

namespace App\Services\TorrentSearch\Scrapers;

use App\Services\TorrentSearch\TorrentSearchEngine;

class X1337xScraper extends BaseScraper
{
    protected function buildSearchUrl(string $query): string
    {
        $baseUrl = rtrim($this->getBaseUrl(), '/');
        return "{$baseUrl}/search/" . urlencode($query) . "/1/";
    }

    public function parseResult($html): array
    {
        $results = [];
        
        // 1337x typically uses table structure for results
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $rows)) {
            foreach ($rows[1] as $row) {
                // Extract torrent data from table row
                $result = [
                    'title' => $this->extractText($row, 'title'),
                    'size' => $this->extractText($row, 'size'),
                    'seeders' => (int) $this->extractText($row, 'seeders'),
                    'leechers' => (int) $this->extractText($row, 'leechers'),
                    'upload_date' => $this->extractText($row, 'date'),
                    'magnet_link' => $this->extractMagnetLink($row),
                    'torrent_link' => $this->extractLink($row, 'torrent'),
                    'source_url' => $this->extractLink($row, 'detail'),
                ];

                if (!empty($result['title'])) {
                    $results[] = TorrentSearchEngine::standardizeResult($result, $this->getName());
                }
            }
        }

        return $results;
    }

    protected function extractText(string $html, string $type): string
    {
        return '';
    }

    protected function extractLink(string $html, string $type): ?string
    {
        return null;
    }
}

