<?php
/**
 * Find author UUID by name
 * Usage: php find-author-uuid.php "Author Name"
 */

if ($argc < 2) {
    echo "Usage: php find-author-uuid.php <author_name>\n";
    exit(1);
}

$authorName = $argv[1];
$authorsDir = __DIR__ . '/../content/collections/authors/';

if (!is_dir($authorsDir)) {
    echo "Error: Authors directory not found\n";
    exit(1);
}

$files = glob($authorsDir . '*.md');

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (stripos($content, $authorName) !== false) {
        // Extract UUID
        if (preg_match('/^id:\s*([a-f0-9-]+)/m', $content, $match)) {
            echo json_encode([
                'file' => basename($file),
                'uuid' => $match[1],
                'name' => $authorName
            ], JSON_PRETTY_PRINT);
            exit(0);
        }
    }
}

echo json_encode(['error' => 'Author not found: ' . $authorName], JSON_PRETTY_PRINT);
exit(1);
