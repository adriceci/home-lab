<?php

namespace App\Services\TorrentSearch\Scrapers;

use App\Services\TorrentSearch\TorrentSearchEngine;
use App\Services\LogEngine;

class X1337xScraper extends BaseScraper
{
    /**
     * Map TPB category codes to 1337x category names
     */
    protected function mapCategoryTo1337x(string $categoryName): ?string
    {
        $mapping = [
            'Audio' => 'Music',
            'Video' => 'Movies',
            'Applications' => 'Apps',
            'Games' => 'Games',
            'Other' => 'Other',
        ];

        return $mapping[$categoryName] ?? null;
    }

    /**
     * Override search method to handle Cloudflare blocking for global searches
     * When no categories are selected, search across multiple default categories
     * to get mixed results, as global searches are blocked by Cloudflare
     * 
     * @param string $query
     * @param array $categories
     * @return array
     */
    public function search(string $query, array $categories = []): array
    {
        // If no categories selected, 1337x global search is blocked by Cloudflare
        // Workaround: search across multiple default categories and combine results
        if (empty($categories)) {
            LogEngine::info('torrent_search', '[X1337xScraper] No categories selected, using multi-category workaround for Cloudflare bypass', [
                'query' => $query,
            ]);

            // Default categories to search across for mixed results
            // This bypasses Cloudflare blocking on global searches
            $defaultCategories = [
                ['name' => 'Video', 'code' => 201], // Movies
                ['name' => 'Audio', 'code' => 101], // Music
            ];

            $allResults = [];

            // Search each category and combine results
            foreach ($defaultCategories as $category) {
                try {
                    LogEngine::debug('torrent_search', '[X1337xScraper] Searching default category', [
                        'query' => $query,
                        'category' => $category['name'],
                    ]);

                    $categoryResults = parent::search($query, [$category]);

                    LogEngine::debug('torrent_search', '[X1337xScraper] Category search completed', [
                        'category' => $category['name'],
                        'results_count' => count($categoryResults),
                    ]);

                    $allResults = array_merge($allResults, $categoryResults);

                    // Small delay between requests to avoid rate limiting
                    usleep(300000); // 0.3 seconds
                } catch (\Exception $e) {
                    LogEngine::warning('torrent_search', '[X1337xScraper] Error searching category', [
                        'category' => $category['name'],
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with next category
                    continue;
                }
            }

            // Remove duplicates based on title and source_url
            $uniqueResults = [];
            $seen = [];
            foreach ($allResults as $result) {
                $key = md5($result['title'] . $result['source_url']);
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniqueResults[] = $result;
                }
            }

            LogEngine::info('torrent_search', '[X1337xScraper] Multi-category search completed', [
                'query' => $query,
                'total_results' => count($uniqueResults),
                'categories_searched' => count($defaultCategories),
            ]);

            return $uniqueResults;
        }

        // If categories are selected, use normal search (works with Cloudflare)
        return parent::search($query, $categories);
    }

    protected function buildSearchUrl(string $query, array $categories = []): string
    {
        $baseUrl = rtrim($this->getBaseUrl(), '/');

        LogEngine::debug('torrent_search', '[X1337xScraper] Building search URL', [
            'base_url' => $baseUrl,
            'query' => $query,
            'categories' => $categories,
        ]);

        // Build category filter for 1337x
        // 1337x uses category names: Movies, TV, Games, Music, Apps, Documentaries, Anime, Other, XXX
        $categoryParam = '';
        if (!empty($categories)) {
            // Get first category name if available (1337x doesn't support multiple categories in one search)
            $firstCategory = $categories[0] ?? null;
            if ($firstCategory && isset($firstCategory['name'])) {
                $categoryName = $this->mapCategoryTo1337x($firstCategory['name']);
                if ($categoryName) {
                    // 1337x format: /category-search/{query}/{category}/{page}/
                    $url = "{$baseUrl}/category-search/" . urlencode($query) . "/{$categoryName}/1/";
                    LogEngine::debug('torrent_search', '[X1337xScraper] Built category search URL', [
                        'url' => $url,
                        'category' => $categoryName,
                    ]);
                    return $url;
                }
            }
        }

        // Default: regular search without category filter
        // Format: /search/{query}/{page}/
        $url = "{$baseUrl}/search/" . urlencode($query) . "/1/";
        LogEngine::debug('torrent_search', '[X1337xScraper] Built regular search URL', [
            'url' => $url,
        ]);
        return $url;
    }

    public function parseResult($html): array
    {
        $results = [];

        // Check HTML structure indicators
        $hasTableList = strpos($html, 'table-list') !== false;
        $hasSearchPage = strpos($html, 'search-page') !== false;
        $hasResults = strpos($html, 'coll-1') !== false || strpos($html, 'seeds') !== false;

        LogEngine::debug('torrent_search', '[X1337xScraper] Starting parseResult', [
            'html_length' => strlen($html),
            'has_table_list' => $hasTableList,
            'has_search_page' => $hasSearchPage,
            'has_results_indicators' => $hasResults,
            'html_sample' => substr($html, 0, 500),
        ]);

        // Parse with DOMDocument to follow the table structure
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            libxml_clear_errors();
            LogEngine::warning('torrent_search', '[X1337xScraper] Failed to load HTML');
            return $results;
        }
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Query for table rows in the results table
        // Try multiple selectors to handle different page structures
        $nodes = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' table-list ')]//tbody//tr");

        LogEngine::debug('torrent_search', '[X1337xScraper] Query result (first attempt)', [
            'query_type' => 'table.table-list tbody tr',
            'nodes_found' => $nodes->length,
        ]);

        // If no results with first query, try alternative selectors
        if ($nodes->length === 0) {
            // Try without tbody requirement (some pages might not have tbody)
            $nodes = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' table-list ')]//tr");

            LogEngine::debug('torrent_search', '[X1337xScraper] Query result (second attempt)', [
                'query_type' => 'table.table-list tr (no tbody)',
                'nodes_found' => $nodes->length,
            ]);
        }

        if ($nodes->length === 0) {
            // Try any table with results
            $nodes = $xpath->query("//table//tr[td[@class='coll-1' or contains(@class, 'coll-1')]]");

            LogEngine::debug('torrent_search', '[X1337xScraper] Query result (third attempt)', [
                'query_type' => 'table tr with coll-1 td',
                'nodes_found' => $nodes->length,
            ]);
        }

        if ($nodes->length === 0) {
            // Check if page structure is completely different - log HTML structure for debugging
            $tables = $xpath->query("//table");
            $tableInfo = [];
            foreach ($tables as $idx => $table) {
                if ($table instanceof \DOMElement) {
                    $tableInfo[] = [
                        'index' => $idx,
                        'class' => $table->getAttribute('class'),
                        'id' => $table->getAttribute('id'),
                        'rows' => $xpath->query('.//tr', $table)->length,
                    ];
                }
            }

            LogEngine::warning('torrent_search', '[X1337xScraper] No results found with any selector', [
                'html_sample' => substr($html, 0, 2000),
                'html_length' => strlen($html),
                'tables_found' => count($tableInfo),
                'table_info' => $tableInfo,
            ]);
            return $results;
        }

        $getText = function (?\DOMNode $n): string {
            if (!$n) return '';
            return trim(preg_replace('/\s+/', ' ', $n->textContent ?? ''));
        };

        $getAttr = function (?\DOMElement $n, string $attr): ?string {
            if (!$n) return null;
            return $n->hasAttribute($attr) ? $n->getAttribute($attr) : null;
        };

        $baseUrl = rtrim($this->getBaseUrl(), '/');

        /** @var \DOMElement $element */
        foreach ($nodes as $element) {
            // Skip header row if present
            $firstCell = $xpath->query('.//td[1]', $element)->item(0);
            if ($firstCell instanceof \DOMElement) {
                $firstCellClass = $firstCell->getAttribute('class');
                $firstCellText = strtolower(trim($firstCell->textContent ?? ''));
                // Skip if this looks like a header row
                if (
                    strpos($firstCellClass, 'header') !== false ||
                    in_array($firstCellText, ['name', 'se', 'le', 'time', 'size', 'uploader'])
                ) {
                    LogEngine::debug('torrent_search', '[X1337xScraper] Skipping header row');
                    continue;
                }
            }

            // Extract data from table row
            // Structure: td.coll-1.name (title + link), td.coll-2.seeds, td.coll-3.leeches, td.coll-date, td.coll-4.size
            $cells = $xpath->query('.//td', $element);

            if ($cells->length < 5) {
                LogEngine::debug('torrent_search', '[X1337xScraper] Skipping row with insufficient cells', [
                    'cells_count' => $cells->length,
                ]);
                continue;
            }

            // Column 1: Name (title + link)
            $nameCell = $cells->item(0) ?? null;
            if (!$nameCell) {
                continue;
            }

            // Extract title and link from name cell
            $titleLink = $xpath->query('.//a[contains(@href, "/torrent/")]', $nameCell)->item(0);
            $title = $titleLink ? $getText($titleLink) : '';
            $sourceUrl = null;
            if ($titleLink instanceof \DOMElement) {
                $href = $getAttr($titleLink, 'href');
                if ($href) {
                    $sourceUrl = str_starts_with($href, 'http') ? $href : $baseUrl . '/' . ltrim($href, '/');
                }
            }

            if (empty($title)) {
                continue;
            }

            // Column 2: Seeds
            $seedsCell = $cells->item(1) ?? null;
            $seeders = $seedsCell ? (int) preg_replace('/[^0-9]/', '', $getText($seedsCell)) : 0;

            // Column 3: Leeches
            $leechesCell = $cells->item(2) ?? null;
            $leechers = $leechesCell ? (int) preg_replace('/[^0-9]/', '', $getText($leechesCell)) : 0;

            // Column 4: Date
            $dateCell = null;
            // Find td with class coll-date
            foreach ($cells as $cell) {
                if ($cell instanceof \DOMElement && strpos($cell->getAttribute('class'), 'coll-date') !== false) {
                    $dateCell = $cell;
                    break;
                }
            }
            $uploadDate = $dateCell ? $getText($dateCell) : '';

            // Column 5: Size
            $sizeCell = null;
            // Find td with class coll-4 or contains size
            foreach ($cells as $cell) {
                if ($cell instanceof \DOMElement) {
                    $class = $cell->getAttribute('class');
                    if (strpos($class, 'coll-4') !== false || strpos($class, 'size') !== false) {
                        $sizeCell = $cell;
                        break;
                    }
                }
            }
            $size = $sizeCell ? $getText($sizeCell) : '';
            // Remove any extra text like seeders count that might be in the size cell
            $size = preg_replace('/\s*[\d,]+\s*$/', '', $size);

            // Leave magnet_link empty - will be fetched asynchronously from frontend
            // This avoids blocking the search with multiple HTTP requests
            $magnetLink = '';

            LogEngine::debug('torrent_search', '[X1337xScraper] Extracted torrent data', [
                'title' => substr($title, 0, 50),
                'size' => $size,
                'date' => $uploadDate,
                'has_magnet' => !empty($magnetLink),
                'seeders' => $seeders,
                'leechers' => $leechers,
            ]);

            $results[] = TorrentSearchEngine::standardizeResult([
                'title' => $title,
                'size' => $size,
                'seeders' => $seeders,
                'leechers' => $leechers,
                'upload_date' => $uploadDate,
                'magnet_link' => $magnetLink,
                'torrent_link' => null,
                'source_url' => $sourceUrl,
            ], $this->getName());
        }

        LogEngine::info('torrent_search', '[X1337xScraper] Parsing completed', [
            'results_count' => count($results),
        ]);

        return $results;
    }

    /**
     * Fetch magnet link from detail page URL (option 2 - extended search)
     * This fetches the detail page and extracts the magnet link
     *
     * @param string $detailUrl
     * @return string
     */
    public function fetchMagnetFromDetailUrl(string $detailUrl): string
    {
        return $this->extractMagnetFromDetailUrl($detailUrl);
    }

    /**
     * Attempt to extract magnet link from detail page URL (option 3)
     * This tries to fetch the detail page and extract the magnet link
     *
     * @param string $detailUrl
     * @return string
     */
    protected function extractMagnetFromDetailUrl(string $detailUrl): string
    {
        try {
            LogEngine::debug('torrent_search', '[X1337xScraper] Attempting to extract magnet from detail URL', [
                'url' => $detailUrl,
            ]);

            // Fetch the detail page
            $html = TorrentSearchEngine::fetchUrl($detailUrl);

            if (!$html) {
                LogEngine::debug('torrent_search', '[X1337xScraper] Failed to fetch detail page', [
                    'url' => $detailUrl,
                ]);
                return '';
            }

            // Try to extract magnet link using regex (common pattern in 1337x)
            if (preg_match('/href=["\'](magnet:[^"\']+)["\']/i', $html, $matches)) {
                $magnetLink = $matches[1];
                LogEngine::debug('torrent_search', '[X1337xScraper] Successfully extracted magnet link', [
                    'url' => $detailUrl,
                    'magnet_preview' => substr($magnetLink, 0, 50) . '...',
                ]);
                return $magnetLink;
            }

            // Try DOMDocument approach as fallback
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            if ($dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
                $xpath = new \DOMXPath($dom);
                $magnetLinks = $xpath->query("//a[starts-with(@href, 'magnet:')]");

                if ($magnetLinks->length > 0) {
                    $firstMagnet = $magnetLinks->item(0);
                    if ($firstMagnet instanceof \DOMElement) {
                        $magnetLink = $firstMagnet->getAttribute('href');
                        LogEngine::debug('torrent_search', '[X1337xScraper] Successfully extracted magnet link via XPath', [
                            'url' => $detailUrl,
                            'magnet_preview' => substr($magnetLink, 0, 50) . '...',
                        ]);
                        return $magnetLink;
                    }
                }
            }
            libxml_clear_errors();

            LogEngine::debug('torrent_search', '[X1337xScraper] No magnet link found in detail page', [
                'url' => $detailUrl,
            ]);
            return '';
        } catch (\Exception $e) {
            LogEngine::warning('torrent_search', '[X1337xScraper] Error extracting magnet from detail URL', [
                'url' => $detailUrl,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    protected function extractText(string $html, string $type): string
    {
        // Not used in the DOM-based implementation; keep for interface completeness
        return '';
    }

    protected function extractLink(string $html, string $type): ?string
    {
        // Not used in the DOM-based implementation; keep for interface completeness
        return null;
    }
}
