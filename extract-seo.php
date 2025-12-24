<?php
/**
 * Extract SEO metadata from article HTML
 * Usage: php extract-seo.php /path/to/raw.html
 */

if ($argc < 2) {
    echo "Usage: php extract-seo.php <html_file_path>\n";
    exit(1);
}

$htmlFile = $argv[1];

if (!file_exists($htmlFile)) {
    echo "Error: File not found: $htmlFile\n";
    exit(1);
}

$html = file_get_contents($htmlFile);

$seo = [];

// Extract title
if (preg_match('/<title>([^<]*)<\/title>/', $html, $match)) {
    $seo['meta_title'] = trim($match[1]);
}

// Extract meta description
if (preg_match('/name="description"\s+content="([^"]*)"/', $html, $match)) {
    $seo['meta_description'] = $match[1];
} elseif (preg_match('/content="([^"]*)"\s+name="description"/', $html, $match)) {
    $seo['meta_description'] = $match[1];
}

// Extract canonical URL
if (preg_match('/rel="canonical"\s+href="([^"]*)"/', $html, $match)) {
    $seo['canonical'] = $match[1];
} elseif (preg_match('/href="([^"]*)"\s+rel="canonical"/', $html, $match)) {
    $seo['canonical'] = $match[1];
}

// Extract H1
if (preg_match('/<h1[^>]*>([^<]*)<\/h1>/', $html, $match)) {
    $seo['h1'] = trim($match[1]);
}

// Extract author
if (preg_match('/rel="author"[^>]*>([^<]*)/', $html, $match)) {
    $seo['author'] = trim($match[1]);
}

// Extract date from JSON-LD
if (preg_match('/"datePublished":\s*"([^"]*)"/', $html, $match)) {
    $seo['date_published'] = $match[1];
}

echo json_encode($seo, JSON_PRETTY_PRINT);
