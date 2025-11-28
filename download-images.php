<?php

/**
 * Script to download article images from URLs
 *
 * Usage: php download-images.php [ARTICLE_URL] [ARTICLE_SLUG]
 * Example: php download-images.php https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business
 */

class ImageDownloader
{
    private $articleUrl;
    private $articleSlug;
    private $assetsBasePath;
    private $featuredPath = 'articles/featured';
    private $mainContentPath = 'articles/main-content';

    public function __construct($articleUrl, $articleSlug)
    {
        $this->articleUrl = $articleUrl;
        $this->articleSlug = $articleSlug;
        $this->assetsBasePath = __DIR__ . '/../public/assets';

        // Create directories if they don't exist
        $this->ensureDirectories();
    }

    /**
     * Ensures directories exist
     */
    private function ensureDirectories()
    {
        $dirs = [
            $this->assetsBasePath . '/' . $this->featuredPath,
            $this->assetsBasePath . '/' . $this->mainContentPath,
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "✓ Creado directorio: $dir\n";
            }
        }
    }

    /**
     * Downloads an image from a URL
     */
    public function downloadImage($imageUrl, $destinationPath)
    {
        if (empty($imageUrl)) {
            return false;
        }

        // If URL is relative, convert it to absolute
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = $this->makeAbsoluteUrl($imageUrl);
        }

        $fullPath = $this->assetsBasePath . '/' . $destinationPath;

        // Get image content
        $imageData = @file_get_contents($imageUrl);

        if ($imageData === false) {
            echo "✗ Error downloading: $imageUrl\n";
            return false;
        }

        // Save the image
        if (file_put_contents($fullPath, $imageData) !== false) {
            echo "✓ Descargada: $destinationPath\n";
            return true;
        }

        echo "✗ Error saving: $destinationPath\n";
        return false;
    }

    /**
     * Converts a relative URL to absolute
     */
    private function makeAbsoluteUrl($relativeUrl)
    {
        $baseUrl = parse_url($this->articleUrl);
        $base = $baseUrl['scheme'] . '://' . $baseUrl['host'];

        if (strpos($relativeUrl, '/') === 0) {
            return $base . $relativeUrl;
        }

        return $base . '/' . $relativeUrl;
    }

    /**
     * Extracts images from article HTML
     */
    public function extractImagesFromHtml($html)
    {
        $images = [];

        // Find images in <img> tags
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $imgSrc) {
                $images[] = $imgSrc;
            }
        }

        // Find images in data-src attributes (lazy loading)
        preg_match_all('/data-src=["\']([^"\']+)["\']/i', $html, $dataMatches);

        if (!empty($dataMatches[1])) {
            foreach ($dataMatches[1] as $imgSrc) {
                if (!in_array($imgSrc, $images)) {
                    $images[] = $imgSrc;
                }
            }
        }

        return array_unique($images);
    }

    /**
     * Gets article HTML from URL
     */
    public function fetchArticleHtml()
    {
        $html = @file_get_contents($this->articleUrl);

        if ($html === false) {
            echo "✗ Error getting article HTML\n";
            return null;
        }

        return $html;
    }

    /**
     * Generates a filename based on slug and image type
     */
    public function generateImageFilename($imageUrl, $type = 'featured')
    {
        $extension = $this->getImageExtension($imageUrl);
        $filename = $this->articleSlug . '.' . $extension;

        if ($type === 'featured') {
            return $this->featuredPath . '/' . $filename;
        }

        return $this->mainContentPath . '/' . $filename;
    }

    /**
     * Gets image extension from URL
     */
    private function getImageExtension($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // If no extension, try to detect from MIME type or use png as default
        if (empty($extension)) {
            $extension = 'png';
        }

        // Normalize common extensions
        $extension = strtolower($extension);
        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }

        return $extension;
    }

    /**
     * Processes and downloads all article images
     */
    public function processArticle()
    {
        echo "=== Downloading images for: {$this->articleSlug} ===\n\n";
        echo "URL del artículo: {$this->articleUrl}\n\n";

        $html = $this->fetchArticleHtml();
        if (!$html) {
            return;
        }

        $images = $this->extractImagesFromHtml($html);

        if (empty($images)) {
            echo "No images found in article.\n";
            return;
        }

        echo "Encontradas " . count($images) . " imagen(es):\n\n";

        $downloadedImages = [];
        $imageIndex = 0;

        foreach ($images as $imageUrl) {
            echo "Processing: $imageUrl\n";

            // Determine if it's featured image (first image) or content
            $type = ($imageIndex === 0) ? 'featured' : 'main-content';
            $destinationPath = $this->generateImageFilename($imageUrl, $type);

            if ($this->downloadImage($imageUrl, $destinationPath)) {
                $downloadedImages[] = [
                    'original_url' => $imageUrl,
                    'local_path' => $destinationPath,
                    'type' => $type
                ];
                $imageIndex++;
            }

            echo "\n";
        }

        // Generate report
        $this->generateReport($downloadedImages);
    }

    /**
     * Generates a report of downloaded images
     */
    private function generateReport($downloadedImages)
    {
        $reportPath = __DIR__ . '/image-mapping-' . $this->articleSlug . '.json';

        $report = [
            'article_slug' => $this->articleSlug,
            'article_url' => $this->articleUrl,
            'downloaded_at' => date('Y-m-d H:i:s'),
            'images' => $downloadedImages
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        echo "✓ Reporte guardado en: $reportPath\n";
    }
}

// Execute script
if ($argc < 3) {
    echo "Usage: php download-images.php [ARTICLE_URL] [ARTICLE_SLUG]\n";
    echo "Example: php download-images.php https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business\n";
    exit(1);
}

$articleUrl = $argv[1];
$articleSlug = $argv[2];

$downloader = new ImageDownloader($articleUrl, $articleSlug);
$downloader->processArticle();
