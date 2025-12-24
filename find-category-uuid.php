<?php
/**
 * Find category UUID by slug
 * Usage: php find-category-uuid.php "category-slug"
 */

if ($argc < 2) {
    echo "Usage: php find-category-uuid.php <category_slug>\n";
    exit(1);
}

$categorySlug = $argv[1];
$categoriesDir = __DIR__ . '/../content/collections/categories/';

if (!is_dir($categoriesDir)) {
    echo "Error: Categories directory not found\n";
    exit(1);
}

$categoryFile = $categoriesDir . $categorySlug . '.md';

if (file_exists($categoryFile)) {
    $content = file_get_contents($categoryFile);
    if (preg_match('/^id:\s*([a-f0-9-]+)/m', $content, $match)) {
        // Also get title
        preg_match('/^title:\s*[\'"]?([^\r\n\'"\-]+)/m', $content, $titleMatch);
        echo json_encode([
            'file' => basename($categoryFile),
            'uuid' => $match[1],
            'slug' => $categorySlug,
            'title' => trim($titleMatch[1] ?? $categorySlug)
        ], JSON_PRETTY_PRINT);
        exit(0);
    }
}

echo json_encode(['error' => 'Category not found: ' . $categorySlug], JSON_PRETTY_PRINT);
exit(1);
