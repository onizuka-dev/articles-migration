<?php
/**
 * Resolve links using redirects.php
 * Converts old paths to new paths based on redirect rules
 *
 * Usage: php resolve-redirects.php /path/to/links.json
 * Or:    php resolve-redirects.php "/old/path"
 */

if ($argc < 2) {
    echo "Usage: php resolve-redirects.php <links_json_file_or_path>\n";
    exit(1);
}

$input = $argv[1];

// Load redirects
$redirectsFile = __DIR__ . '/../app/Routing/redirects.php';
if (!file_exists($redirectsFile)) {
    echo "Error: redirects.php not found\n";
    exit(1);
}

$redirects = require $redirectsFile;

/**
 * Resolve a single URL using redirects
 */
function resolveUrl($url, $redirects) {
    // Normalize URL - remove domain if present
    $path = $url;
    if (preg_match('/^https?:\/\/bizee\.com(.+)$/', $url, $match)) {
        $path = $match[1];
    }

    // Check if path exists in redirects
    if (isset($redirects[$path])) {
        return [
            'original' => $url,
            'resolved' => $redirects[$path],
            'changed' => true
        ];
    }

    // Return original if no redirect found
    return [
        'original' => $url,
        'resolved' => $path,
        'changed' => false
    ];
}

// Check if input is a file or a single path
if (file_exists($input)) {
    // Process JSON file with array of links
    $links = json_decode(file_get_contents($input), true);
    if (!$links) {
        echo "Error: Invalid JSON file\n";
        exit(1);
    }

    $resolved = [];
    foreach ($links as $link) {
        $url = $link['url'] ?? $link;
        $result = resolveUrl($url, $redirects);
        $resolved[] = array_merge($link, [
            'resolved_url' => $result['resolved'],
            'was_redirected' => $result['changed']
        ]);
    }

    echo json_encode($resolved, JSON_PRETTY_PRINT);
} else {
    // Process single path
    $result = resolveUrl($input, $redirects);
    echo json_encode($result, JSON_PRETTY_PRINT);
}
