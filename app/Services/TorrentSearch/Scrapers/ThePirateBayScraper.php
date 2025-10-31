<?php

namespace App\Services\TorrentSearch\Scrapers;

use App\Services\TorrentSearch\TorrentSearchEngine;
use App\Services\LogEngine;

class ThePirateBayScraper extends BaseScraper
{
    protected function buildSearchUrl(string $query): string
    {
        $baseUrl = rtrim($this->getBaseUrl(), '/');
        // The current TPB structure uses an iframe wrapper. Try to extract iframe URL or use known working alternatives
        // Common TPB proxy URLs: 1.piratebays.to, thepiratebay.org, etc.
        // If baseUrl contains known proxy patterns, use direct search endpoint
        if (strpos($baseUrl, 'piratebays.to') !== false || strpos($baseUrl, 'thepiratebay') !== false) {
            // Try to use a known working proxy URL directly
            $directUrl = str_replace(['www2.', 'www3.', 'www4.'], '1.', $baseUrl);
            $directUrl = str_replace('thepiratebay3.co', 'piratebays.to', $directUrl);
            $directUrl = str_replace('thepiratebay.org', 'piratebays.to', $directUrl);
            return rtrim($directUrl, '/') . "/s/?q=" . urlencode($query);
        }
        return "{$baseUrl}/s/?q=" . urlencode($query);
    }

    public function parseResult($html): array
    {
        $results = [];

        LogEngine::debug('torrent_search', '[ThePirateBayScraper] Starting parseResult', [
            'html_length' => strlen($html),
        ]);

        // First, check if this is the iframe wrapper page and extract the iframe src
        $iframeUrl = $this->extractIframeUrl($html);
        if ($iframeUrl) {
            LogEngine::info('torrent_search', '[ThePirateBayScraper] Found iframe, fetching content', [
                'iframe_url' => $iframeUrl,
            ]);
            // Fetch the actual content from the iframe URL
            $html = TorrentSearchEngine::fetchUrl($iframeUrl) ?? $html;
            LogEngine::debug('torrent_search', '[ThePirateBayScraper] Iframe content fetched', [
                'html_length' => strlen($html),
            ]);
        } else {
            LogEngine::debug('torrent_search', '[ThePirateBayScraper] No iframe detected, using direct HTML');
        }

        // Parse with DOMDocument to follow the structure used in the provided raw script (li.list-entry with children)
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            libxml_clear_errors();
            return $results;
        }
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        // The raw script targeted: li.list-entry (with children indexes mapping fields)
        // Try both list-entry class and other common TPB structures
        $nodes = $xpath->query("//li[contains(concat(' ', normalize-space(@class), ' '), ' list-entry ')]");

        LogEngine::debug('torrent_search', '[ThePirateBayScraper] First query result', [
            'query_type' => 'li.list-entry',
            'nodes_found' => $nodes->length,
        ]);

        // If no results with list-entry, try table rows (some TPB proxies use tables)
        if ($nodes->length === 0) {
            $nodes = $xpath->query("//table[@id='searchResult']//tr[position()>1]");
            LogEngine::debug('torrent_search', '[ThePirateBayScraper] Second query result', [
                'query_type' => 'table#searchResult tr',
                'nodes_found' => $nodes->length,
            ]);
        }

        // Try alternative table structures
        if ($nodes->length === 0) {
            $nodes = $xpath->query("//table//tr[position()>1]");
            LogEngine::debug('torrent_search', '[ThePirateBayScraper] Third query result', [
                'query_type' => 'table tr',
                'nodes_found' => $nodes->length,
            ]);
        }

        if ($nodes->length === 0) {
            LogEngine::warning('torrent_search', '[ThePirateBayScraper] No results found with any selector', [
                'html_sample' => substr($html, 0, 1000),
            ]);
        }

        $getText = function (?\DOMNode $n): string {
            if (!$n) return '';
            return trim(preg_replace('/\s+/', ' ', $n->textContent ?? ''));
        };

        $getAttr = function (?\DOMElement $n, string $attr): ?string {
            if (!$n) return null;
            return $n->hasAttribute($attr) ? $n->getAttribute($attr) : null;
        };

        /** @var \DOMElement $element */
        foreach ($nodes as $element) {
            // Handle list-entry structure (from original script)
            if (strpos($element->getAttribute('class'), 'list-entry') !== false) {
                $children = [];
                foreach ($element->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $children[] = $child;
                    }
                }

                $title = isset($children[1]) ? $getText($children[1]) : '';
                $detailLink = null;
                if (isset($children[1])) {
                    $a = $children[1]->getElementsByTagName('a')->item(0);
                    if ($a instanceof \DOMElement) {
                        $href = $getAttr($a, 'href');
                        if ($href) {
                            $base = rtrim($this->getBaseUrl(), '/');
                            $detailLink = str_starts_with($href, 'http') ? $href : $base . '/' . ltrim($href, '/');
                        }
                    }
                }

                $magnet = '';
                if (isset($children[3])) {
                    $magnetA = $children[3]->getElementsByTagName('a')->item(0);
                    if ($magnetA instanceof \DOMElement) {
                        $m = $getAttr($magnetA, 'href');
                        if ($m && str_starts_with($m, 'magnet:')) {
                            $magnet = $m;
                        }
                    }
                }

                $size = isset($children[4]) ? $getText($children[4]) : '';
                $seeders = isset($children[5]) ? (int) preg_replace('/[^0-9]/', '', $getText($children[5])) : 0;
                $leechers = isset($children[6]) ? (int) preg_replace('/[^0-9]/', '', $getText($children[6])) : 0;
                $date = isset($children[2]) ? $getText($children[2]) : '';

                if ($title !== '') {
                    $results[] = TorrentSearchEngine::standardizeResult([
                        'title' => $title,
                        'size' => $size,
                        'seeders' => $seeders,
                        'leechers' => $leechers,
                        'upload_date' => $date,
                        'magnet_link' => $magnet,
                        'torrent_link' => null,
                        'source_url' => $detailLink,
                    ], $this->getName());
                }
            } else {
                // Handle table row structure (alternative TPB structure)
                $cells = $xpath->query('.//td', $element);
                if ($cells->length >= 6) {
                    $titleCell = $cells->item(1) ?? null;
                    $title = $titleCell ? $getText($titleCell) : '';

                    // Get detail link
                    $detailLink = null;
                    if ($titleCell) {
                        $a = $xpath->query('.//a[1]', $titleCell)->item(0);
                        if ($a instanceof \DOMElement) {
                            $href = $getAttr($a, 'href');
                            if ($href) {
                                $base = rtrim($this->getBaseUrl(), '/');
                                $detailLink = str_starts_with($href, 'http') ? $href : $base . '/' . ltrim($href, '/');
                            }
                        }
                    }

                    // Magnet link
                    $magnet = '';
                    $magnetCell = $cells->item(2) ?? null;
                    if ($magnetCell) {
                        $magnetA = $xpath->query('.//a[contains(@href, "magnet:")]', $magnetCell)->item(0);
                        if ($magnetA instanceof \DOMElement) {
                            $m = $getAttr($magnetA, 'href');
                            if ($m && str_starts_with($m, 'magnet:')) {
                                $magnet = $m;
                            }
                        }
                    }

                    $size = $cells->item(3) ? $getText($cells->item(3)) : '';
                    $seeders = $cells->item(4) ? (int) preg_replace('/[^0-9]/', '', $getText($cells->item(4))) : 0;
                    $leechers = $cells->item(5) ? (int) preg_replace('/[^0-9]/', '', $getText($cells->item(5))) : 0;
                    $date = $cells->item(0) ? $getText($cells->item(0)) : '';

                    if ($title !== '') {
                        $results[] = TorrentSearchEngine::standardizeResult([
                            'title' => $title,
                            'size' => $size,
                            'seeders' => $seeders,
                            'leechers' => $leechers,
                            'upload_date' => $date,
                            'magnet_link' => $magnet,
                            'torrent_link' => null,
                            'source_url' => $detailLink,
                        ], $this->getName());
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Extract iframe URL from wrapper page
     *
     * @param string $html
     * @return string|null
     */
    protected function extractIframeUrl(string $html): ?string
    {
        if (preg_match('/<iframe[^>]*src=["\']([^"\']+)["\']/i', $html, $matches)) {
            $iframeSrc = $matches[1];
            // Make absolute URL if relative
            if (!str_starts_with($iframeSrc, 'http')) {
                $base = rtrim($this->getBaseUrl(), '/');
                $iframeSrc = $base . '/' . ltrim($iframeSrc, '/');
            }
            return $iframeSrc;
        }
        return null;
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
