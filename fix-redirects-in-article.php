<?php

/**
 * Fix redirects in migrated article
 *
 * Iterates through all href: values in the article and replaces
 * any that have a redirect in redirects.php OR in production
 *
 * Usage: php fix-redirects-in-article.php <article_file>
 * Example: php fix-redirects-in-article.php content/collections/articles/2022-06-23.instructions-form-ss-4.md
 */

if ($argc < 2) {
    echo "Usage: php fix-redirects-in-article.php <article_file>\n";
    exit(1);
}

$articleFile = $argv[1];

if (!file_exists($articleFile)) {
    echo "Error: File not found: $articleFile\n";
    exit(1);
}

// Load redirects
$redirectsFile = __DIR__ . '/../app/Routing/redirects.php';
if (!file_exists($redirectsFile)) {
    echo "Error: redirects.php not found\n";
    exit(1);
}

$redirects = require $redirectsFile;
echo "Loaded " . count($redirects) . " local redirects\n";
echo "Will also check production for redirects...\n\n";

/**
 * Remove link and keep only text when redirect is generic /articles
 * Converts from:
 *   - type: text
 *     marks:
 *       - type: link
 *         attrs:
 *           href: /articles
 *           rel: null
 *           target: null
 *           title: null
 *     text: "link text"
 * To:
 *   - type: text
 *     text: "link text"
 */
function removeLinkKeepText(&$content) {
    $removed = 0;

    // Pattern to match link blocks with href: /articles (generic)
    // Captures the indentation and the text value
    $pattern = '/^(\s*)- type: text\n\1  marks:\n\1    - type: link\n\1      attrs:\n\1        href: \/articles\n\1        rel: null\n\1        target: null\n\1        title: null\n\1  text: ([^\n]+)/m';

    $content = preg_replace_callback($pattern, function($matches) use (&$removed) {
        $indent = $matches[1];
        $text = $matches[2];
        $removed++;
        echo "LINK REMOVED (generic /articles redirect):\n";
        echo "  Text kept: $text\n\n";
        return $indent . "- type: text\n" . $indent . "  text: " . $text;
    }, $content);

    return $removed;
}

/**
 * Check production for redirect
 */
function checkProductionRedirect($path) {
    $url = "https://bizee.com" . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    // If redirect (301, 302, 307, 308)
    if ($httpCode >= 301 && $httpCode <= 308 && $redirectUrl) {
        // Extract path from redirect URL
        if (preg_match('/^https?:\/\/bizee\.com(.+)$/', $redirectUrl, $match)) {
            return $match[1];
        }
        // Relative redirect
        if (strpos($redirectUrl, '/') === 0) {
            return $redirectUrl;
        }
    }

    return null;
}

// Read article content
$content = file_get_contents($articleFile);
$originalContent = $content;

// Find all href: values
$pattern = '/href: ([^\n]+)/';
preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

$fixed = 0;
$checked = 0;

foreach ($matches as $match) {
    $fullMatch = $match[0];
    $hrefValue = trim($match[1]);

    // Skip external links
    if (preg_match('/^https?:\/\/(?!bizee\.com)/', $hrefValue)) {
        continue;
    }

    // Skip already quoted strings that might be complex
    if (preg_match('/^"https?:/', $hrefValue)) {
        continue;
    }

    $checked++;

    // Normalize path
    $path = $hrefValue;

    // Remove quotes if present
    $path = trim($path, '"\'');

    // Extract path from full URL if needed
    if (preg_match('/^https?:\/\/bizee\.com(.+)$/', $path, $urlMatch)) {
        $path = $urlMatch[1];
    }

    // Check redirects (exact, without trailing slash, with trailing slash)
    $resolvedPath = null;
    $source = 'local';

    if (isset($redirects[$path])) {
        $resolvedPath = $redirects[$path];
    } elseif (isset($redirects[rtrim($path, '/')])) {
        $resolvedPath = $redirects[rtrim($path, '/')];
    } elseif (isset($redirects[$path . '/'])) {
        $resolvedPath = $redirects[$path . '/'];
    }

    // If not found locally, check production
    if (!$resolvedPath) {
        $productionRedirect = checkProductionRedirect($path);
        if ($productionRedirect && $productionRedirect !== $path) {
            $resolvedPath = $productionRedirect;
            $source = 'production';
        }
    }

    if ($resolvedPath && $resolvedPath !== $path && $resolvedPath !== rtrim($path, '/')) {
        echo "REDIRECT FOUND ($source):\n";
        echo "  Original: $path\n";
        echo "  Resolved: $resolvedPath\n\n";

        // Replace in content
        $oldHref = "href: " . $hrefValue;
        $newHref = "href: " . $resolvedPath;

        $content = str_replace($oldHref, $newHref, $content);
        $fixed++;
    }
}

// After fixing redirects, remove links that point to generic /articles
$removed = removeLinkKeepText($content);

echo "============================================================\n";
echo "Checked: $checked internal links\n";
echo "Fixed: $fixed redirects\n";
echo "Removed: $removed generic /articles links\n";

if ($fixed > 0 || $removed > 0) {
    // Write updated content
    file_put_contents($articleFile, $content);
    echo "\n✓ Article updated: $articleFile\n";
} else {
    echo "\n✓ No changes needed\n";
}
