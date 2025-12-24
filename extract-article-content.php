<?php
/**
 * Extract main article content from HTML
 * Usage: php extract-article-content.php /path/to/raw.html /path/to/output.html
 */

if ($argc < 3) {
    echo "Usage: php extract-article-content.php <input_html> <output_html>\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];

if (!file_exists($inputFile)) {
    echo "Error: File not found: $inputFile\n";
    exit(1);
}

$html = file_get_contents($inputFile);

// Try to extract article content
if (preg_match('/<article[^>]*>(.*?)<\/article>/s', $html, $match)) {
    file_put_contents($outputFile, $match[1]);
    echo "Article content extracted: " . strlen($match[1]) . " bytes\n";
} elseif (preg_match('/<main[^>]*>(.*?)<\/main>/s', $html, $match)) {
    file_put_contents($outputFile, $match[1]);
    echo "Main content extracted: " . strlen($match[1]) . " bytes\n";
} else {
    echo "Error: No article/main tag found\n";
    exit(1);
}
