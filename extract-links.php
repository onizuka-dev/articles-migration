<?php
/**
 * Extract all links from article HTML content
 * Automatically resolves redirects from redirects.php
 *
 * Usage: php extract-links.php /path/to/article-content.html
 */

if ($argc < 2) {
    echo "Usage: php extract-links.php <html_file_path>\n";
    exit(1);
}

$htmlFile = $argv[1];

if (!file_exists($htmlFile)) {
    echo "Error: File not found: $htmlFile\n";
    exit(1);
}

// Load redirects
$redirectsFile = __DIR__ . '/../app/Routing/redirects.php';
$redirects = file_exists($redirectsFile) ? require $redirectsFile : [];

/**
 * Resolve URL using redirects.php
 */
function resolveUrl($url, $redirects) {
    // Normalize - extract path from full URL
    $path = $url;
    if (preg_match('/^https?:\/\/bizee\.com(.+)$/', $url, $match)) {
        $path = $match[1];
    }

    // Check redirects (exact match)
    if (isset($redirects[$path])) {
        return $redirects[$path];
    }

    // Check redirects (without trailing slash)
    $pathWithoutSlash = rtrim($path, '/');
    if (isset($redirects[$pathWithoutSlash])) {
        return $redirects[$pathWithoutSlash];
    }

    // Check redirects (with trailing slash)
    $pathWithSlash = rtrim($path, '/') . '/';
    if (isset($redirects[$pathWithSlash])) {
        return $redirects[$pathWithSlash];
    }

    // Return normalized path for internal links
    if (preg_match('/^https?:\/\/bizee\.com/', $url)) {
        return $path;
    }

    return $url;
}

$html = file_get_contents($htmlFile);

// Use DOMDocument for more reliable extraction
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();

$links = [];
$anchors = $dom->getElementsByTagName('a');

foreach ($anchors as $anchor) {
    $href = $anchor->getAttribute('href');
    $text = trim($anchor->textContent);

    // Skip empty links, hash links, and navigation links
    if (empty($href) || $href === '#' || empty($text) || strlen($text) < 2) {
        continue;
    }

    // Skip common navigation patterns
    if (preg_match('/^(Home|Menu|Skip|Search|Login|Sign)/i', $text)) {
        continue;
    }

    // Classify as internal or external
    $type = 'internal';
    if (preg_match('/^https?:\/\//', $href) && !preg_match('/bizee\.com/', $href)) {
        $type = 'external';
    }

    // Resolve redirects for internal links
    $resolvedUrl = $href;
    $wasRedirected = false;
    if ($type === 'internal') {
        $resolvedUrl = resolveUrl($href, $redirects);
        $wasRedirected = ($resolvedUrl !== $href && $resolvedUrl !== preg_replace('/^https?:\/\/bizee\.com/', '', $href));
    }

    $links[] = [
        'url' => $resolvedUrl,
        'original_url' => $href,
        'text' => $text,
        'type' => $type,
        'was_redirected' => $wasRedirected
    ];
}

echo json_encode($links, JSON_PRETTY_PRINT);
