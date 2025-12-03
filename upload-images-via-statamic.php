<?php

/**
 * Script to upload images via Statamic API/CP
 *
 * This script uses Statamic's AssetContainer to create assets,
 * which should trigger thumbnail generation automatically.
 *
 * Usage: php upload-images-via-statamic.php [ARTICLE_URL] [SLUG] [CP_URL]
 * Example: php upload-images-via-statamic.php https://bizee.com/articles/test-article test-article https://bizee.test/cp
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;

// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class StatamicImageUploader
{
    private $articleUrl;
    private $articleSlug;
    private $cpUrl;
    private $tempDir;
    private $s3BasePath = 'articles';
    private $imageMappings = [];
    private $container;

    public function __construct($articleUrl, $articleSlug, $cpUrl = 'https://bizee.test/cp')
    {
        $this->articleUrl = $articleUrl;
        $this->articleSlug = $articleSlug;
        $this->cpUrl = $cpUrl;
        $this->tempDir = sys_get_temp_dir() . '/article-images-' . $articleSlug . '-' . time();

        // Get Statamic asset container
        $this->container = AssetContainer::findByHandle('assets');

        if (!$this->container) {
            throw new Exception("Asset container 'assets' not found");
        }

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

        echo "✓ HTML downloaded\n";
        return $html;
    }

    /**
     * Extracts images from article content (reusing logic from original script)
     */
    private function extractContentImages($html): array
    {
        $images = [];
        $inMainContent = false;
        $inArticleContent = false;
        $inFeaturedSection = false;
        $inAuthorSection = false;
        $inPodcastSection = false;

        $lines = explode("\n", $html);

        foreach ($lines as $line) {
            $lineLower = strtolower($line);

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

            if ($inMainContent || $inArticleContent) {
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

                // Search for srcSet attribute
                if (preg_match_all('/srcSet=["\']([^"\']+)["\']/i', $line, $srcSetMatches)) {
                    foreach ($srcSetMatches[1] as $srcSet) {
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
     * Checks if an image is from content
     */
    private function isContentImage($url, $contextHtml = ''): bool
    {
        $urlLower = strtolower($url);

        if (substr($url, 0, 5) === 'data:') {
            return false;
        }

        $excludePatterns = [
            'icon', 'logo', 'avatar', 'favicon',
            'social', 'twitter', 'linkedin', 'facebook',
            'nav', 'header', 'footer', 'sidebar',
            'trustpilot', 'mobile-menu', 'mobile.3db9b7ad',
            'related-article', 'thumbnail-small',
            'podcast-thumbnail', 'podcast',
            'rectangle-shape',
            'carriebuchholzpowers',
            'man-with-sunglasses',
            'a-view-on-the-city',
            'man-in-a-suit-using-a-macbook',
        ];

        foreach ($excludePatterns as $pattern) {
            if (strpos($urlLower, $pattern) !== false) {
                return false;
            }
        }

        if (strpos($url, '/_next/image') !== false) {
            if (strpos($url, 'w=384') !== false || strpos($url, 'w=256') !== false) {
                return false;
            }
        }

        if (strpos($urlLower, '.svg') !== false && (
            strpos($urlLower, 'rectangle') !== false ||
            strpos($urlLower, 'shape') !== false ||
            strpos($urlLower, 'decoration') !== false
        )) {
            return false;
        }

        $contentPatterns = [
            'teenager', 'headphones', 'map', 'infographic',
            'states', 'minor', 'business', 'entrepreneur',
            'blog_top-image', 'location', 'physical', 'address',
            'virtual', 'mailbox', 'nomad', 'relocating',
            'memorability', 'domain',
        ];

        foreach ($contentPatterns as $pattern) {
            if (strpos($urlLower, $pattern) !== false) {
                return true;
            }
        }

        if (strpos($urlLower, 's3.us-east-2.amazonaws.com/bizee-website-assets') !== false) {
            if (strpos($url, '/_next/image') !== false) {
                if (preg_match('/w=(\d+)/', $url, $widthMatch)) {
                    $width = (int)$widthMatch[1];
                    if ($width >= 750) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalizes a URL
     */
    private function normalizeUrl($url): string
    {
        if (strpos($url, '/_next/image') !== false) {
            $parsed = parse_url($url);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                if (isset($params['url'])) {
                    $url = urldecode($params['url']);
                    if (strpos($url, '%') !== false) {
                        $url = urldecode($url);
                    }
                    if (substr($url, 0, 4) !== 'http') {
                        $url = 'https://bizee.com' . $url;
                    }
                }
            }
        }

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

        if ($index === 0 && (
            strpos($urlLower, 'featured') !== false ||
            strpos($urlLower, 'hero') !== false ||
            strpos($urlLower, 'header') !== false ||
            strpos($urlLower, 'blog_top-image') !== false
        )) {
            return 'featured';
        }

        return 'main-content';
    }

    /**
     * Generates descriptive filename based on image content and context
     */
    private function generateS3Filename($imageUrl, $type, $index): string
    {
        if ($type === 'featured') {
            // For featured images, generate descriptive name based on image URL and article context
            $descriptiveName = $this->generateDescriptiveName($imageUrl);
            return $descriptiveName . '.webp';
        }

        // For content images, use original filename or generate descriptive name
        $cleanUrl = explode('?', $imageUrl)[0];
        $path = parse_url($cleanUrl, PHP_URL_PATH);
        $basename = basename($path);
        $name = pathinfo($basename, PATHINFO_FILENAME);

        $name = preg_replace('/[^a-z0-9-]/i', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        if (empty($name) || strlen($name) < 3) {
            $name = $this->articleSlug . '-image-' . ($index + 1);
        }

        return $name . '.webp';
    }

    /**
     * Generates a descriptive filename based on image URL and article context
     * Analyzes the original filename and article title to create semantic names
     */
    private function generateDescriptiveName($imageUrl): string
    {
        // Extract base filename from URL
        $cleanUrl = explode('?', $imageUrl)[0];
        $path = parse_url($cleanUrl, PHP_URL_PATH);
        $basename = basename($path);
        $originalName = pathinfo($basename, PATHINFO_FILENAME);

        // Common image description keywords found in filenames
        $keywordPatterns = [
            // People
            'woman' => ['woman', 'female', 'lady', 'girl', 'afro-woman'],
            'man' => ['man', 'male', 'guy', 'gentleman'],
            'person' => ['person', 'people', 'individual', 'professional'],
            'business' => ['business', 'professional', 'entrepreneur', 'executive'],

            // Actions/Activities
            'working' => ['working', 'work', 'at-work', 'desk', 'office', 'late-night', 'late'],
            'smiling' => ['smiling', 'smile', 'happy', 'cheerful'],
            'drinking' => ['drinking', 'coffee', 'cofee', 'cafe'],
            'using' => ['using', 'with'],

            // Objects/Items
            'laptop' => ['laptop', 'computer', 'notebook', 'macbook'],
            'glasses' => ['glasses', 'eyeglasses', 'spectacles'],
            'phone' => ['phone', 'smartphone', 'mobile', 'calling'],
            'document' => ['document', 'paper', 'contract', 'agreement'],

            // Settings
            'office' => ['office', 'desk', 'workspace'],
            'meeting' => ['meeting', 'conference', 'discussion'],
            'team' => ['team', 'group', 'collaboration'],
        ];

        // Try to extract descriptive words from original filename
        $originalLower = strtolower($originalName);
        $descriptiveWords = [];

        // Check for keyword patterns in filename
        foreach ($keywordPatterns as $key => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($originalLower, $pattern) !== false) {
                    $descriptiveWords[] = $key;
                    break; // Only add once per category
                }
            }
        }

        // Also check article slug for context clues
        $articleContext = $this->getArticleContextKeywords();
        if (!empty($articleContext)) {
            $descriptiveWords = array_merge($descriptiveWords, $articleContext);
        }

        // Remove duplicates and build name
        $descriptiveWords = array_unique($descriptiveWords);

        if (!empty($descriptiveWords)) {
            $name = implode('-', $descriptiveWords);
        } else {
            // Fallback: try to extract meaningful parts from original filename
            // Remove common prefixes/suffixes like "blog_top-image", "0013", etc.
            $cleaned = preg_replace('/^(blog[-_]?top[-_]?image|image|img|photo|pic)[-_]?/i', '', $originalName);
            $cleaned = preg_replace('/[-_]?\d+$/', '', $cleaned); // Remove trailing numbers
            $cleaned = preg_replace('/[-_]+/', '-', $cleaned);

            if (strlen($cleaned) > 3) {
                $name = strtolower($cleaned);
            } else {
                // Last resort: use article context
                $name = $this->generateNameFromArticleContext();
            }
        }

        // Clean the name
        $name = preg_replace('/[^a-z0-9-]/i', '-', strtolower($name));
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        // Ensure minimum length
        if (empty($name) || strlen($name) < 3) {
            $name = $this->articleSlug;
        }

        return $name;
    }

    /**
     * Gets contextual keywords from article slug that might describe the image
     */
    private function getArticleContextKeywords(): array
    {
        $slugLower = strtolower($this->articleSlug);
        $keywords = [];

        // Map article topics to image description keywords
        $contextMappings = [
            'add-members' => ['business', 'team', 'partnership'],
            'add-member' => ['business', 'team', 'partnership'],
            'llc' => ['business', 'professional'],
            'business' => ['business', 'professional'],
            'domain' => ['website', 'online', 'laptop'],
            'email' => ['communication', 'laptop', 'working'],
            'address' => ['location', 'office'],
            'virtual' => ['virtual', 'office', 'remote'],
            'registered-agent' => ['business', 'service'],
            'legal' => ['business', 'document'],
            'tax' => ['document', 'business'],
        ];

        foreach ($contextMappings as $pattern => $words) {
            if (strpos($slugLower, $pattern) !== false) {
                $keywords = array_merge($keywords, $words);
            }
        }

        return array_unique($keywords);
    }

    /**
     * Generates descriptive name based on article title and context (fallback)
     */
    private function generateNameFromArticleContext(): string
    {
        $slugLower = strtolower($this->articleSlug);

        // Map article topics to image descriptions
        $topicMappings = [
            'add-members-llc' => 'business-partnership',
            'add-member-llc' => 'business-partnership',
            'llc' => 'business-formation',
            'business' => 'business-professional',
            'domain' => 'website-online',
            'email' => 'email-communication',
            'address' => 'location-office',
            'virtual' => 'virtual-office',
            'registered-agent' => 'registered-agent-service',
        ];

        foreach ($topicMappings as $pattern => $description) {
            if (strpos($slugLower, $pattern) !== false) {
                return $description;
            }
        }

        // Default: use article slug
        return $this->articleSlug;
    }

    /**
     * Uploads an image using Statamic AssetContainer
     * This should trigger thumbnail generation automatically
     *
     * The process:
     * 1. Upload file to S3 via Laravel Storage
     * 2. Create Asset object using Statamic's makeAsset()
     * 3. Save the asset to trigger Statamic events (including thumbnail generation)
     */
    private function uploadViaStatamic($localPath, $s3Path): bool
    {
        try {
            // Check if asset already exists in Statamic
            $existingAsset = $this->container->asset($s3Path);
            if ($existingAsset && $existingAsset->exists()) {
                echo "  ℹ Already exists in Statamic, skipping: {$s3Path}\n";
                return true;
            }

            // Read file contents
            $contents = file_get_contents($localPath);

            if (!$contents) {
                echo "  ✗ Could not read local file\n";
                return false;
            }

            // Get the disk from the container
            $disk = $this->container->disk();

            // Step 1: Upload to S3 via Laravel Storage (which Statamic uses)
            // This puts the file in S3 but doesn't register it in Statamic yet
            $success = Storage::disk($disk->handle())->put($s3Path, $contents, 'public');

            if (!$success) {
                echo "  ✗ Failed to upload to S3\n";
                return false;
            }

            echo "  ✓ File uploaded to S3\n";

            // Step 2: Create the asset in Statamic's system using makeAsset()
            // This creates an Asset object that references the file in S3
            $asset = $this->container->makeAsset($s3Path);

            if (!$asset) {
                echo "  ✗ Failed to create Statamic asset object\n";
                // Clean up: delete from S3 if asset creation failed
                Storage::disk($disk->handle())->delete($s3Path);
                return false;
            }

            echo "  ✓ Statamic asset object created\n";

            // Step 3: Save the asset to trigger Statamic events
            // This should trigger thumbnail generation and other asset processing
            try {
                $asset->save();
                echo "  ✓ Asset saved in Statamic (thumbnails should be generated)\n";
            } catch (Exception $saveException) {
                // If save fails, the file is still in S3 but not registered in Statamic
                echo "  ⚠ Warning: Asset saved to S3 but Statamic save failed: {$saveException->getMessage()}\n";
                echo "    The file exists in S3 but may not appear in CP until manually refreshed\n";
                // Don't return false here - the file is uploaded, just not fully registered
            }

            echo "  ✓ Uploaded via Statamic: {$s3Path}\n";
            echo "    → Check CP at {$this->cpUrl}/assets to verify thumbnails\n";
            return true;

        } catch (Exception $e) {
            echo "  ✗ Error uploading via Statamic: {$e->getMessage()}\n";
            if (strpos($e->getMessage(), 'Stack trace') === false) {
                echo "    Stack trace: {$e->getTraceAsString()}\n";
            }
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
            echo "\n✓ Temporary directory cleaned\n";
        }
    }

    /**
     * Processes all images
     */
    public function process(): bool
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     Upload Images via Statamic API                       ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";

        echo "Article: {$this->articleSlug}\n";
        echo "URL: {$this->articleUrl}\n";
        echo "CP URL: {$this->cpUrl}\n\n";

        // Verify container
        if (!$this->container) {
            echo "✗ Error: Asset container 'assets' not found.\n";
            return false;
        }

        echo "✓ Asset container found: {$this->container->handle()}\n";
        echo "✓ Disk: {$this->container->diskHandle()}\n\n";

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
                $type = 'main-content';
            }

            $s3Filename = $this->generateS3Filename($imageUrl, $type, $index);
            $s3Path = $this->s3BasePath . '/' . $type . '/' . $s3Filename;

            // Upload via Statamic
            if ($this->uploadViaStatamic($tempPath, $s3Path)) {
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

        // Summary
        echo "\n═══════════════════════════════════════════════════════════\n";
        echo "SUMMARY\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        echo "✓ Images processed: " . count($this->imageMappings) . "\n";
        echo "✓ All images uploaded via Statamic API\n";
        echo "✓ Thumbnails should be generated automatically\n";
        echo "✓ Temporary files deleted\n";

        return true;
    }

    /**
     * Saves the image mapping
     */
    private function saveMapping(): void
    {
        $mappingFile = __DIR__ . '/image-mapping-' . $this->articleSlug . '.json';
        $mapping = [
            'article_slug' => $this->articleSlug,
            'article_url' => $this->articleUrl,
            'processed_at' => date('Y-m-d H:i:s'),
            'method' => 'statamic_api',
            'images' => $this->imageMappings
        ];

        file_put_contents($mappingFile, json_encode($mapping, JSON_PRETTY_PRINT));
        echo "\n✓ Mapping saved to: " . basename($mappingFile) . "\n";
    }

    /**
     * Gets the image mappings
     */
    public function getMappings(): array
    {
        return $this->imageMappings;
    }
}

// Execute
if ($argc < 3) {
    echo "Usage: php upload-images-via-statamic.php [ARTICLE_URL] [SLUG] [CP_URL]\n\n";
    echo "Example:\n";
    echo "php upload-images-via-statamic.php \\\n";
    echo "  https://bizee.com/articles/test-article \\\n";
    echo "  test-article \\\n";
    echo "  https://bizee.test/cp\n";
    exit(1);
}

$articleUrl = $argv[1];
$articleSlug = $argv[2];
$cpUrl = $argv[3] ?? 'https://bizee.test/cp';

try {
    $uploader = new StatamicImageUploader($articleUrl, $articleSlug, $cpUrl);
    $uploader->process();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
