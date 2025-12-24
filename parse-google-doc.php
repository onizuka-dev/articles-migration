<?php
/**
 * Parse Google Docs content for new article creation
 *
 * Usage: php articles-migration/parse-google-doc.php "[GOOGLE_DOC_URL]"
 *
 * The document should have the following structure:
 * - Metadata section at the top (Title, Category, Author, Meta Title, Meta Description, etc.)
 * - Content section with the article body
 * - Optional Key Takeaways section at the end
 */

if ($argc < 2) {
    echo "Usage: php parse-google-doc.php \"[GOOGLE_DOC_URL]\"\n";
    echo "Example: php parse-google-doc.php \"https://docs.google.com/document/d/ABC123/edit\"\n";
    exit(1);
}

$docUrl = $argv[1];

// Extract document ID from URL
if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $docUrl, $matches)) {
    $docId = $matches[1];
} else {
    echo json_encode(['error' => 'Invalid Google Docs URL format']);
    exit(1);
}

// Construct export URL
$exportUrl = "https://docs.google.com/document/d/{$docId}/export?format=html";

// Download the document
$html = @file_get_contents($exportUrl);

if ($html === false) {
    echo json_encode([
        'error' => 'Could not download document. Make sure it is shared with "Anyone with the link can view"',
        'export_url' => $exportUrl
    ]);
    exit(1);
}

// Save raw HTML for debugging
file_put_contents('/tmp/gdoc-raw.html', $html);

// Parse the HTML
$result = parseGoogleDoc($html);

// Output as JSON
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

/**
 * Parse Google Doc HTML content
 */
function parseGoogleDoc(string $html): array {
    $result = [
        'metadata' => [
            'title' => null,
            'category' => null,
            'author' => null,
            'meta_title' => null,
            'meta_description' => null,
            'featured_image_url' => null,
            'date' => date('Y-m-d'),
            'slug' => null,
        ],
        'images' => [
            'featured' => null,
            'content' => [],
        ],
        'content' => [
            'intro' => null,
            'main_content' => [],
            'key_takeaways' => [],
        ],
        'links' => [],
        'extraction_success' => true,
        'warnings' => [],
    ];

    // Create DOM parser
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Get body content
    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        $result['extraction_success'] = false;
        $result['warnings'][] = 'Could not find body element';
        return $result;
    }

    // Extract all text content
    $elements = extractElements($body);

    // Parse metadata from the beginning
    $contentStartIndex = parseMetadata($elements, $result);

    // Parse content
    parseContent($elements, $contentStartIndex, $result);

    // Extract images
    extractImages($dom, $result);

    // Extract links
    extractLinks($dom, $result);

    // Generate slug if not provided
    if (empty($result['metadata']['slug']) && !empty($result['metadata']['title'])) {
        $result['metadata']['slug'] = generateSlug($result['metadata']['title']);
    }

    // Add warnings for missing required fields
    if (empty($result['metadata']['title'])) {
        $result['warnings'][] = 'Missing required field: title';
    }
    if (empty($result['metadata']['category'])) {
        $result['warnings'][] = 'Missing required field: category';
    }
    if (empty($result['metadata']['author'])) {
        $result['warnings'][] = 'Missing required field: author';
    }
    if (empty($result['metadata']['meta_title'])) {
        $result['warnings'][] = 'Missing field: meta_title';
    }
    if (empty($result['metadata']['meta_description'])) {
        $result['warnings'][] = 'Missing field: meta_description';
    }

    return $result;
}

/**
 * Extract all elements from the body
 */
function extractElements(DOMNode $body): array {
    $elements = [];

    foreach ($body->childNodes as $node) {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $elements[] = [
                'tag' => strtolower($node->nodeName),
                'text' => trim($node->textContent),
                'html' => $node->ownerDocument->saveHTML($node),
                'node' => $node,
            ];
        }
    }

    return $elements;
}

/**
 * Parse metadata from elements
 */
function parseMetadata(array $elements, array &$result): int {
    $metadataPatterns = [
        'title' => '/^(?:title|título)\s*[:\-]\s*(.+)$/i',
        'category' => '/^(?:category|categoría|categoria)\s*[:\-]\s*(.+)$/i',
        'author' => '/^(?:author|autor)\s*[:\-]\s*(.+)$/i',
        'meta_title' => '/^(?:meta\s*title|seo\s*title)\s*[:\-]\s*(.+)$/i',
        'meta_description' => '/^(?:meta\s*description|seo\s*description)\s*[:\-]\s*(.+)$/i',
        'featured_image_url' => '/^(?:featured\s*image|hero\s*image|imagen\s*destacada)\s*[:\-]\s*(.+)$/i',
        'date' => '/^(?:date|fecha)\s*[:\-]\s*(\d{4}-\d{2}-\d{2})$/i',
        'slug' => '/^(?:slug)\s*[:\-]\s*(.+)$/i',
    ];

    $validCategories = [
        'legal', 'business-formation', 'manage-your-company',
        'resources', 'start-a-business', 'side-hustles'
    ];

    $contentStartIndex = 0;
    $foundMetadata = false;

    foreach ($elements as $index => $element) {
        $text = $element['text'];
        $matched = false;

        foreach ($metadataPatterns as $field => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1]);

                // Validate category
                if ($field === 'category') {
                    $value = strtolower($value);
                    $value = str_replace(' ', '-', $value);
                    if (!in_array($value, $validCategories)) {
                        $result['warnings'][] = "Invalid category: {$value}. Valid options: " . implode(', ', $validCategories);
                        continue;
                    }
                }

                $result['metadata'][$field] = $value;
                $matched = true;
                $foundMetadata = true;
                break;
            }
        }

        // Check if this is the first h1 (could be title without label)
        if (!$matched && $element['tag'] === 'h1' && empty($result['metadata']['title'])) {
            $result['metadata']['title'] = $text;
            $matched = true;
            $foundMetadata = true;
        }

        // If we found metadata and now hit a non-metadata paragraph, content starts
        if ($foundMetadata && !$matched && !empty($text) && strlen($text) > 50) {
            $contentStartIndex = $index;
            break;
        }

        $contentStartIndex = $index + 1;
    }

    return $contentStartIndex;
}

/**
 * Parse content from elements
 */
function parseContent(array $elements, int $startIndex, array &$result): void {
    $isFirstParagraph = true;
    $inKeyTakeaways = false;

    for ($i = $startIndex; $i < count($elements); $i++) {
        $element = $elements[$i];
        $text = $element['text'];
        $tag = $element['tag'];

        // Skip empty elements
        if (empty($text)) {
            continue;
        }

        // Check for Key Takeaways section
        if (stripos($text, 'key takeaways') !== false || stripos($text, 'puntos clave') !== false) {
            $inKeyTakeaways = true;
            continue;
        }

        // If in Key Takeaways, add to that array
        if ($inKeyTakeaways) {
            // Check for list items
            if ($tag === 'ul' || $tag === 'ol') {
                $listItems = extractListItems($element['node']);
                $result['content']['key_takeaways'] = array_merge(
                    $result['content']['key_takeaways'],
                    $listItems
                );
            } elseif ($tag === 'p' || $tag === 'li') {
                $result['content']['key_takeaways'][] = $text;
            }
            continue;
        }

        // Determine content type
        $contentItem = null;

        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
            $level = (int) substr($tag, 1);
            $contentItem = [
                'type' => 'heading',
                'level' => $level,
                'content' => $text,
            ];
        } elseif ($tag === 'ul' || $tag === 'ol') {
            $listItems = extractListItems($element['node']);
            $contentItem = [
                'type' => 'list',
                'items' => $listItems,
            ];
        } elseif ($tag === 'p') {
            // Check if it's a CTA
            if (preg_match('/^(?:cta|button|botón)\s*[:\-]\s*(.+)/i', $text, $ctaMatch)) {
                $contentItem = [
                    'type' => 'cta',
                    'text' => trim($ctaMatch[1]),
                    'url' => '', // URL should be extracted from link in the element
                ];
            } else {
                $contentItem = [
                    'type' => 'paragraph',
                    'content' => $text,
                    'html' => $element['html'],
                ];
            }
        }

        if ($contentItem) {
            // First paragraph goes to intro
            if ($isFirstParagraph && $contentItem['type'] === 'paragraph') {
                $result['content']['intro'] = $text;
                $isFirstParagraph = false;
            } else {
                $result['content']['main_content'][] = $contentItem;
            }
        }
    }
}

/**
 * Extract list items from a list element
 */
function extractListItems(DOMNode $listNode): array {
    $items = [];

    foreach ($listNode->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName) === 'li') {
            $items[] = trim($child->textContent);
        }
    }

    return $items;
}

/**
 * Extract images from the document
 */
function extractImages(DOMDocument $dom, array &$result): void {
    $images = $dom->getElementsByTagName('img');
    $imageIndex = 0;

    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        $alt = $img->getAttribute('alt') ?: '';

        if (empty($src)) {
            continue;
        }

        // Skip tiny images (likely icons)
        $style = $img->getAttribute('style');
        if (preg_match('/width:\s*(\d+)px/', $style, $matches) && (int)$matches[1] < 50) {
            continue;
        }

        $imageData = [
            'url' => $src,
            'alt' => $alt,
            'suggested_name' => generateImageName($alt ?: "image-{$imageIndex}"),
        ];

        // First significant image is featured image
        if ($imageIndex === 0 && empty($result['images']['featured'])) {
            $result['images']['featured'] = $imageData;
        } else {
            $imageData['position'] = $imageIndex - 1;
            $result['images']['content'][] = $imageData;
        }

        $imageIndex++;
    }
}

/**
 * Extract links from the document
 */
function extractLinks(DOMDocument $dom, array &$result): void {
    $links = $dom->getElementsByTagName('a');

    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        $text = trim($link->textContent);

        if (empty($href) || empty($text)) {
            continue;
        }

        // Skip internal Google Docs links
        if (strpos($href, 'google.com/url') !== false) {
            // Extract actual URL from Google redirect
            if (preg_match('/[?&]q=([^&]+)/', $href, $matches)) {
                $href = urldecode($matches[1]);
            }
        }

        // Determine if internal or external
        $type = 'external';
        if (strpos($href, 'bizee.com') !== false) {
            $type = 'internal';
            // Convert to relative path
            $href = preg_replace('#^https?://(?:www\.)?bizee\.com#', '', $href);
        } elseif (strpos($href, '/') === 0 && strpos($href, '//') !== 0) {
            $type = 'internal';
        }

        $result['links'][] = [
            'text' => $text,
            'url' => $href,
            'type' => $type,
        ];
    }
}

/**
 * Generate a slug from title
 */
function generateSlug(string $title): string {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // Limit length
    if (strlen($slug) > 60) {
        $slug = substr($slug, 0, 60);
        $slug = preg_replace('/-[^-]*$/', '', $slug);
    }

    return $slug;
}

/**
 * Generate a descriptive image name
 */
function generateImageName(string $description): string {
    $name = strtolower($description);
    $name = preg_replace('/[^a-z0-9\s-]/', '', $name);
    $name = preg_replace('/[\s_]+/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');

    if (strlen($name) > 50) {
        $name = substr($name, 0, 50);
        $name = preg_replace('/-[^-]*$/', '', $name);
    }

    return $name . '.webp';
}
