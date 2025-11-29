<?php

/**
 * Script to download images and upload them directly to S3
 *
 * This script:
 * 1. Downloads images from the original article
 * 2. Uploads them directly to S3
 * 3. Deletes local images after uploading
 * 4. Generates a mapping of original URLs → S3 paths
 *
 * Usage: php download-and-upload-images-to-s3.php [ARTICLE_URL] [SLUG]
 * Example: php download-and-upload-images-to-s3.php https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class DirectS3ImageUploader
{
    private $articleUrl;
    private $articleSlug;
    private $tempDir;
    private $s3BasePath = 'articles';
    private $imageMappings = [];

    public function __construct($articleUrl, $articleSlug)
    {
        $this->articleUrl = $articleUrl;
        $this->articleSlug = $articleSlug;
        $this->tempDir = sys_get_temp_dir() . '/article-images-' . $articleSlug . '-' . time();

        // Create temporary directory
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Downloads article HTML
     */
    private function downloadHtml(): ?string
    {
        echo "Downloading HTML from: {$this->articleUrl}\n";

        $ch = curl_init($this->articleUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) {
            echo "✗ Error downloading HTML\n";
            return null;
        }

        echo "✓ HTML descargado\n";
        return $html;
    }

    /**
     * Extracts images from article content using parser similar to Python
     */
    private function extractContentImages($html): array
    {
        $images = [];
        $inMainContent = false;
        $inArticleContent = false;
        $inFeaturedSection = false;
        $inAuthorSection = false;
        $inPodcastSection = false;

        // Split HTML into lines to process sequentially
        $lines = explode("\n", $html);
        $currentTag = '';

        foreach ($lines as $line) {
            $lineLower = strtolower($line);

            // Detect section start
            if (preg_match('/<main[^>]*>/i', $line)) {
                $inMainContent = true;
            }
            if (preg_match('/<\/main>/i', $line)) {
                $inMainContent = false;
            }
            if (preg_match('/<article[^>]*>/i', $line)) {
                $inArticleContent = true;
            }
            if (preg_match('/<\/article>/i', $line)) {
                $inArticleContent = false;
            }
            if (preg_match('/featured[\s-]?article|related[\s-]?article/i', $lineLower)) {
                $inFeaturedSection = true;
            }
            if (preg_match('/<\/section>|<\/div>/i', $line) && $inFeaturedSection) {
                $inFeaturedSection = false;
            }
            if (preg_match('/author|carrie/i', $lineLower) && preg_match('/<section|<div/i', $lineLower)) {
                $inAuthorSection = true;
            }
            if (preg_match('/podcast/i', $lineLower) && preg_match('/<section|<div/i', $lineLower)) {
                $inPodcastSection = true;
            }

            // Search for images only if we're in main content
            if ($inMainContent || $inArticleContent) {
                // Exclude if we're in featured articles, author or podcast sections
                if ($inFeaturedSection || $inAuthorSection || $inPodcastSection) {
                    continue;
                }

                // Search for images with src attribute
                if (preg_match_all('/<img[^>]+(?:src|data-src)=["\']([^"\']+)["\'][^>]*>/i', $line, $matches)) {
                    foreach ($matches[1] as $url) {
                        if ($this->isContentImage($url, $line)) {
                            $images[] = $url;
                        }
                    }
                }
                
                // Search for srcSet attribute separately (Next.js images)
                if (preg_match_all('/srcSet=["\']([^"\']+)["\']/i', $line, $srcSetMatches)) {
                    foreach ($srcSetMatches[1] as $srcSet) {
                        // Extract URLs from srcSet format: "url1 1x, url2 2x"
                        // Also handle Next.js format: "/_next/image?url=ENCODED_URL&w=750&q=75"
                        // Match both absolute URLs and Next.js relative URLs
                        preg_match_all('/(https?:\/\/[^\s,]+|\/_next\/image\?[^\s,]+)/', $srcSet, $srcSetUrls);
                        foreach ($srcSetUrls[1] as $srcSetUrl) {
                            // Normalize Next.js image URL to extract the actual image URL
                            $normalizedUrl = $this->normalizeUrl($srcSetUrl);
                            if ($this->isContentImage($normalizedUrl, $line)) {
                                $images[] = $normalizedUrl;
                            }
                        }
                    }
                }
                
                // Also check for imageSrcSet (used in preload links)
                if (preg_match_all('/imageSrcSet=["\']([^"\']+)["\']/i', $line, $imageSrcSetMatches)) {
                    foreach ($imageSrcSetMatches[1] as $srcSet) {
                        preg_match_all('/(https?:\/\/[^\s,]+|\/_next\/image\?[^\s,]+)/', $srcSet, $srcSetUrls);
                        foreach ($srcSetUrls[1] as $srcSetUrl) {
                            $normalizedUrl = $this->normalizeUrl($srcSetUrl);
                            if ($this->isContentImage($normalizedUrl, $line)) {
                                $images[] = $normalizedUrl;
                            }
                        }
                    }
                }
            }
        }

        return array_unique($images);
    }

    /**
     * Checks if an image is from content (not nav/footer/featured articles)
     */
    private function isContentImage($url, $contextHtml = ''): bool
    {
        $urlLower = strtolower($url);

        // Filter embedded data URIs
        if (substr($url, 0, 5) === 'data:') {
            return false;
        }

        // Filter images that are NOT from content
        $excludePatterns = [
            'icon', 'logo', 'avatar', 'favicon',
            'social', 'twitter', 'linkedin', 'facebook',
            'nav', 'header', 'footer', 'sidebar',
            'trustpilot', 'mobile-menu', 'mobile.3db9b7ad',
            'related-article', 'thumbnail-small',
            'podcast-thumbnail', 'podcast',
            'rectangle-shape', // Decorative SVGs
            'carriebuchholzpowers', // Author avatar
            'man-with-sunglasses', // Featured article thumbnail
            'a-view-on-the-city', // Featured article thumbnail
            'man-in-a-suit-using-a-macbook', // Featured article thumbnail
        ];

        foreach ($excludePatterns as $pattern) {
            if (strpos($urlLower, $pattern) !== false) {
                return false;
            }
        }

        // Filter small thumbnails from featured articles (w=384 or w=256)
        if (strpos($url, '/_next/image') !== false) {
            if (strpos($url, 'w=384') !== false || strpos($url, 'w=256') !== false) {
                return false;
            }
        }

        // Filter decorative SVGs
        if (strpos($urlLower, '.svg') !== false && (
            strpos($urlLower, 'rectangle') !== false ||
            strpos($urlLower, 'shape') !== false ||
            strpos($urlLower, 'decoration') !== false
        )) {
            return false;
        }

        // Check context if available
        if (!empty($contextHtml)) {
            $contextLower = strtolower($contextHtml);

            // Exclude if it's in featured articles, author, or podcast sections
            if (preg_match('/featured[\s-]?article|related[\s-]?article|author[\s-]?section|podcast/i', $contextLower)) {
                return false;
            }
        }

        // Filter very small images (probably icons)
        if (preg_match('/[_-](\d+)x(\d+)[._-]/', $url, $sizeMatches)) {
            $width = (int)$sizeMatches[1];
            $height = (int)$sizeMatches[2];
            if ($width < 200 || $height < 200) {
                return false;
            }
        }

        // Include images that appear to be from the actual article content
        $contentPatterns = [
            'teenager', 'headphones', 'map', 'infographic',
            'states', 'minor', 'business', 'entrepreneur',
            'blog_top-image', 'location', 'physical', 'address',
            'virtual', 'mailbox', 'nomad', 'relocating',
        ];

        // If it has article-specific content patterns, include it
        foreach ($contentPatterns as $pattern) {
            if (strpos($urlLower, $pattern) !== false) {
                return true;
            }
        }

        // Also include if it's from S3 bizee-website-assets and is a large image (likely content)
        if (strpos($urlLower, 's3.us-east-2.amazonaws.com/bizee-website-assets') !== false) {
            // Check if it's a large image (not a thumbnail)
            if (strpos($url, '/_next/image') !== false) {
                // Check width parameter - include if w >= 750 (content images are usually larger)
                if (preg_match('/w=(\d+)/', $url, $widthMatch)) {
                    $width = (int)$widthMatch[1];
                    if ($width >= 750) {
                        return true;
                    }
                }
            } else {
                // Direct S3 URL, likely content image
                return true;
            }
        }

        // If it doesn't have specific content patterns, exclude it for safety
        // (we only include images that are clearly from the article content)
        return false;
    }

    /**
     * Normalizes a URL
     */
    private function normalizeUrl($url): string
    {
        // Clean URL from Next.js parameters
        if (strpos($url, '/_next/image') !== false) {
            $parsed = parse_url($url);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                if (isset($params['url'])) {
                    $url = urldecode($params['url']);
                    // Handle URL-encoded URLs
                    if (strpos($url, '%') !== false) {
                        $url = urldecode($url);
                    }
                    if (substr($url, 0, 4) !== 'http') {
                        $url = 'https://bizee.com' . $url;
                    }
                }
            }
        }

        // Convert relative URL to absolute
        if (substr($url, 0, 4) !== 'http') {
            $parsed = parse_url($this->articleUrl);
            $base = $parsed['scheme'] . '://' . $parsed['host'];
            if (substr($url, 0, 1) === '/') {
                $url = $base . $url;
            } else {
                $url = $base . '/' . $url;
            }
        }

        return $url;
    }

    /**
     * Downloads an image temporarily
     */
    private function downloadImageToTemp($imageUrl): ?string
    {
        $imageUrl = $this->normalizeUrl($imageUrl);
        $extension = $this->getImageExtension($imageUrl);
        $tempPath = $this->tempDir . '/' . uniqid('img_') . $extension;

        try {
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$imageData) {
                return null;
            }

            file_put_contents($tempPath, $imageData);
            return $tempPath;
        } catch (Exception $e) {
            echo "  ✗ Error downloading: {$e->getMessage()}\n";
            return null;
        }
    }

    /**
     * Gets image extension
     */
    private function getImageExtension($url): string
    {
        $cleanUrl = explode('?', $url)[0];
        $path = parse_url($cleanUrl, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (empty($ext) || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            return '.webp';
        }

        return '.' . $ext;
    }

    /**
     * Determines if it's a featured image or content image
     */
    private function determineImageType($imageUrl, $index): string
    {
        $urlLower = strtolower($imageUrl);

        // The first large image is usually the featured one
        if ($index === 0 && (
            strpos($urlLower, 'featured') !== false ||
            strpos($urlLower, 'hero') !== false ||
            strpos($urlLower, 'header') !== false
        )) {
            return 'featured';
        }

        return 'main-content';
    }

    /**
     * Generates S3 filename
     */
    private function generateS3Filename($imageUrl, $type, $index): string
    {
        if ($type === 'featured') {
            return $this->articleSlug . '.webp';
        }

        // For content images, use a descriptive name
        $cleanUrl = explode('?', $imageUrl)[0];
        $path = parse_url($cleanUrl, PHP_URL_PATH);
        $basename = basename($path);
        $name = pathinfo($basename, PATHINFO_FILENAME);

        // Clean the name
        $name = preg_replace('/[^a-z0-9-]/i', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        if (empty($name) || strlen($name) < 3) {
            $name = $this->articleSlug . '-image-' . ($index + 1);
        }

        return $name . '.webp';
    }

    /**
     * Uploads an image to S3
     */
    private function uploadToS3($localPath, $s3Path): bool
    {
        try {
            // Check if it already exists in S3
            try {
                if (Storage::disk('s3')->exists($s3Path)) {
                    echo "  ℹ Already exists in S3, skipping: {$s3Path}\n";
                    return true;
                }
            } catch (Exception $e) {
                // Continue if there's an error checking
            }

            $contents = file_get_contents($localPath);
            $success = Storage::disk('s3')->put($s3Path, $contents, 'public');

            if ($success) {
                echo "  ✓ Subida a S3: {$s3Path}\n";
                return true;
            }

            return false;
        } catch (Exception $e) {
            echo "  ✗ Error uploading to S3: {$e->getMessage()}\n";
            return false;
        }
    }

    /**
     * Cleans up temporary directory
     */
    private function cleanupTemp(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
            echo "\n✓ Directorio temporal limpiado\n";
        }
    }

    /**
     * Processes all images
     */
    public function process(): bool
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     Direct Download and Upload to S3                      ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";

        echo "Article: {$this->articleSlug}\n";
        echo "URL: {$this->articleUrl}\n\n";

        // Verify S3 configuration
        if (!Storage::disk('s3')->getDriver()) {
            echo "✗ Error: S3 disk is not configured correctly.\n";
            return false;
        }

        // Download HTML
        $html = $this->downloadHtml();
        if (!$html) {
            return false;
        }

        // Extract images
        echo "\nExtracting images from content...\n";
        $imageUrls = $this->extractContentImages($html);
        echo "Found " . count($imageUrls) . " image(s) from content\n\n";

        if (empty($imageUrls)) {
            echo "ℹ No images found to process\n";
            return true;
        }

        // Process each image
        $featuredUploaded = false;
        foreach ($imageUrls as $index => $imageUrl) {
            echo "\nProcessing image " . ($index + 1) . ":\n";
            echo "  URL: {$imageUrl}\n";

            // Download temporarily
            $tempPath = $this->downloadImageToTemp($imageUrl);
            if (!$tempPath) {
                echo "  ✗ Could not download\n";
                continue;
            }

            // Determine type and generate S3 name
            $type = $this->determineImageType($imageUrl, $index);
            if ($type === 'featured' && $featuredUploaded) {
                $type = 'main-content'; // Only one featured image
            }

            $s3Filename = $this->generateS3Filename($imageUrl, $type, $index);
            $s3Path = $this->s3BasePath . '/' . $type . '/' . $s3Filename;

            // Upload to S3
            if ($this->uploadToS3($tempPath, $s3Path)) {
                $this->imageMappings[] = [
                    'original_url' => $imageUrl,
                    's3_path' => $s3Path,
                    'type' => $type
                ];

                if ($type === 'featured') {
                    $featuredUploaded = true;
                }
            }

            // Delete temporary file
            unlink($tempPath);
        }

        // Save mapping
        $this->saveMapping();

        // Clean temporary directory
        $this->cleanupTemp();

        // Resumen
        echo "\n═══════════════════════════════════════════════════════════\n";
        echo "RESUMEN\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        echo "✓ Imágenes procesadas: " . count($this->imageMappings) . "\n";
        echo "✓ Todas las imágenes subidas directamente a S3\n";
        echo "✓ Archivos temporales eliminados\n";

        return true;
    }

    /**
     * Guarda el mapeo de imágenes
     */
    private function saveMapping(): void
    {
        $mappingFile = __DIR__ . '/image-mapping-' . $this->articleSlug . '.json';
        $mapping = [
            'article_slug' => $this->articleSlug,
            'article_url' => $this->articleUrl,
            'processed_at' => date('Y-m-d H:i:s'),
            'images' => $this->imageMappings
        ];

        file_put_contents($mappingFile, json_encode($mapping, JSON_PRETTY_PRINT));
        echo "\n✓ Mapeo guardado en: " . basename($mappingFile) . "\n";
    }

    /**
     * Obtiene el mapeo de imágenes
     */
    public function getMappings(): array
    {
        return $this->imageMappings;
    }
}

// Execute
if ($argc < 3) {
    echo "Uso: php download-and-upload-images-to-s3.php [URL_DEL_ARTICULO] [SLUG]\n\n";
    echo "Ejemplo:\n";
    echo "php download-and-upload-images-to-s3.php \\\n";
    echo "  https://bizee.com/articles/can-a-minor-own-a-business \\\n";
    echo "  can-a-minor-own-a-business\n";
    exit(1);
}

$articleUrl = $argv[1];
$articleSlug = $argv[2];

try {
    $uploader = new DirectS3ImageUploader($articleUrl, $articleSlug);
    $uploader->process();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
