<?php
/**
 * Extract images from article HTML content
 * Separates hero/featured image from content images
 * Identifies position for content images
 *
 * Usage: php extract-images.php /path/to/article-raw.html
 */

if ($argc < 2) {
    echo "Usage: php extract-images.php <html_file_path>\n";
    exit(1);
}

$htmlFile = $argv[1];

if (!file_exists($htmlFile)) {
    echo "Error: File not found: $htmlFile\n";
    exit(1);
}

$html = file_get_contents($htmlFile);

// Use DOMDocument for reliable extraction
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();

$result = [
    'featured_image' => null,
    'content_images' => [],
    'counts' => [
        'featured' => 0,
        'content' => 0,
        'total' => 0
    ]
];

$images = $dom->getElementsByTagName('img');
$foundFeatured = false;
$contentImageIndex = 0;

foreach ($images as $index => $img) {
    $src = $img->getAttribute('src');
    $alt = $img->getAttribute('alt');
    $width = $img->getAttribute('width');

    // Skip small images (icons, thumbnails in sidebar)
    if ($width && intval($width) < 200) {
        continue;
    }

    // Skip SVG icons
    if (strpos($src, '.svg') !== false) {
        continue;
    }

    // Skip known non-content images
    $skipPatterns = [
        'twitter', 'linkedin', 'facebook', 'social',
        'podcast-thumbnail', 'trustpilot', 'footer',
        'icon', 'logo', 'avatar'
    ];
    $srcLower = strtolower($src);
    $skip = false;
    foreach ($skipPatterns as $pattern) {
        if (strpos($srcLower, $pattern) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    // Skip images without S3 URL
    if (strpos($src, 'bizee-website-assets') === false && strpos($src, 's3.us-east') === false) {
        // Check srcSet for S3 URL
        $srcSet = $img->getAttribute('srcset');
        if (strpos($srcSet, 'bizee-website-assets') === false && strpos($srcSet, 's3.us-east') === false) {
            continue;
        }
        // Extract from srcSet
        if (preg_match('/url=([^&\s]+)/', $srcSet, $match)) {
            $src = urldecode($match[1]);
        }
    }

    // Extract actual S3 URL from Next.js image URL
    if (preg_match('/url=([^&]+)/', $src, $urlMatch)) {
        $src = urldecode($urlMatch[1]);
    }

    // Skip related articles images (usually in sidebar)
    $parentClass = '';
    $parent = $img->parentNode;
    while ($parent && $parent->nodeType === XML_ELEMENT_NODE) {
        $class = $parent->getAttribute('class');
        if (strpos($class, 'related') !== false || strpos($class, 'sidebar') !== false || strpos($class, 'featured-articles') !== false) {
            continue 2; // Skip this image
        }
        $parent = $parent->parentNode;
    }

    // First large image is the featured/hero image
    if (!$foundFeatured && $width && intval($width) >= 800) {
        $result['featured_image'] = [
            'src' => $src,
            'alt' => $alt,
            'suggested_name' => generateImageName($alt, 'featured')
        ];
        $result['counts']['featured'] = 1;
        $foundFeatured = true;
        continue;
    }

    // Rest are content images
    if ($foundFeatured) {
        $result['content_images'][] = [
            'src' => $src,
            'alt' => $alt,
            'position' => $contentImageIndex,
            'suggested_name' => generateImageName($alt, 'content')
        ];
        $contentImageIndex++;
    }
}

$result['counts']['content'] = count($result['content_images']);
$result['counts']['total'] = $result['counts']['featured'] + $result['counts']['content'];

echo json_encode($result, JSON_PRETTY_PRINT);

/**
 * Generate a descriptive kebab-case filename from alt text
 */
function generateImageName($alt, $type) {
    if (empty($alt)) {
        return $type . '-image-' . uniqid() . '.webp';
    }

    // Clean and convert to kebab-case
    $name = strtolower($alt);
    $name = preg_replace('/[^a-z0-9\s-]/', '', $name);
    $name = preg_replace('/[\s_]+/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');

    // Limit length
    if (strlen($name) > 50) {
        $name = substr($name, 0, 50);
        $name = preg_replace('/-[^-]*$/', '', $name); // Remove partial word
    }

    return $name . '.webp';
}
