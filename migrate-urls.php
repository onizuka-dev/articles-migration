<?php

/**
 * Script to migrate and convert URLs in articles
 *
 * This script:
 * 1. Converts internal URLs from bizee.com to relative format or keeps full format
 * 2. Identifies and maps image URLs
 * 3. Updates references in the article file
 *
 * Usage: php migrate-urls.php [ARTICLE_PATH]
 * Example: php migrate-urls.php content/collections/articles/2024-11-21.can-a-minor-own-a-business.md
 */

class UrlMigrator
{
    private $articlePath;
    private $articleContent;
    private $urlMappings = [];

    public function __construct($articlePath)
    {
        $this->articlePath = $articlePath;

        if (!file_exists($articlePath)) {
            throw new Exception("El archivo no existe: $articlePath");
        }

        $this->articleContent = file_get_contents($articlePath);
    }

    /**
     * Maps internal URLs from bizee.com
     */
    public function mapInternalUrls()
    {
        // Common patterns for internal URLs
        $patterns = [
            '/https:\/\/bizee\.com\/articles\/([^"\']+)/',
            '/https:\/\/bizee\.com\/([^"\']+)/',
        ];

        $replacements = [];

        // Keep full URLs but document them
        preg_match_all('/https:\/\/bizee\.com\/([^"\'\s\)]+)/', $this->articleContent, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $url) {
                if (!isset($this->urlMappings[$url])) {
                    $this->urlMappings[$url] = [
                        'original' => $url,
                        'type' => $this->classifyUrl($url),
                        'suggested' => $url, // Keep full URL for now
                    ];
                }
            }
        }

        return $this->urlMappings;
    }

    /**
     * Classifies the URL type
     */
    private function classifyUrl($url)
    {
        if (strpos($url, '/articles/') !== false) {
            return 'article';
        }

        if (strpos($url, '/business-formation/') !== false) {
            return 'business-formation';
        }

        if (strpos($url, '/business-management/') !== false) {
            return 'business-management';
        }

        if (strpos($url, '/legal/') !== false) {
            return 'legal';
        }

        if (strpos($url, '/manage-your-company/') !== false) {
            return 'management';
        }

        return 'other';
    }

    /**
     * Searches for image URLs in content
     */
    public function findImageUrls()
    {
        $imageUrls = [];

        // Search for image URLs in HTML/YAML
        preg_match_all('/(https?:\/\/[^\s"\']+\.(jpg|jpeg|png|gif|webp|svg))/i', $this->articleContent, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $url) {
                if (!in_array($url, $imageUrls)) {
                    $imageUrls[] = $url;
                }
            }
        }

        return $imageUrls;
    }

    /**
     * Generates a mapping of URLs to local image paths
     */
    public function mapImageUrls($imageMappingFile = null)
    {
        $imageUrls = $this->findImageUrls();
        $mappings = [];

        if ($imageMappingFile && file_exists($imageMappingFile)) {
            $mappingData = json_decode(file_get_contents($imageMappingFile), true);

            if (!empty($mappingData['images'])) {
                foreach ($mappingData['images'] as $image) {
                    $mappings[$image['original_url']] = $image['local_path'];
                }
            }
        }

        return $mappings;
    }

    /**
     * Updates URLs in article content
     */
    public function updateUrls($imageMappings = [])
    {
        $updatedContent = $this->articleContent;

        // Update image URLs if there's a mapping
        if (!empty($imageMappings)) {
            foreach ($imageMappings as $originalUrl => $localPath) {
                // Search and replace references to original image
                $patterns = [
                    '/["\']' . preg_quote($originalUrl, '/') . '["\']/',
                    '/:\s*' . preg_quote($originalUrl, '/') . '/',
                ];

                foreach ($patterns as $pattern) {
                    $updatedContent = preg_replace($pattern, "'$localPath'", $updatedContent);
                }
            }
        }

        return $updatedContent;
    }

    /**
     * Generates a report of found URLs
     */
    public function generateUrlReport()
    {
        $urls = $this->mapInternalUrls();
        $images = $this->findImageUrls();

        $report = [
            'article_path' => $this->articlePath,
            'analyzed_at' => date('Y-m-d H:i:s'),
            'internal_urls' => array_values($urls),
            'image_urls' => $images,
            'total_internal_urls' => count($urls),
            'total_image_urls' => count($images),
        ];

        return $report;
    }

    /**
     * Saves updated content
     */
    public function saveUpdatedContent($content, $outputPath = null)
    {
        $path = $outputPath ?: $this->articlePath;
        file_put_contents($path, $content);
        echo "✓ Archivo actualizado: $path\n";
    }
}

// Execute script
if ($argc < 2) {
    echo "Usage: php migrate-urls.php [ARTICLE_PATH] [IMAGE_MAPPING_FILE]\n";
    echo "Example: php migrate-urls.php content/collections/articles/2024-11-21.can-a-minor-own-a-business.md image-mapping-can-a-minor-own-a-business.json\n";
    exit(1);
}

$articlePath = $argv[1];
$imageMappingFile = $argv[2] ?? null;

try {
    $migrator = new UrlMigrator($articlePath);

    echo "=== Analizando URLs en: $articlePath ===\n\n";

    // Generate report
    $report = $migrator->generateUrlReport();

    echo "Internal URLs found: " . $report['total_internal_urls'] . "\n";
    echo "Image URLs found: " . $report['total_image_urls'] . "\n\n";

    if ($report['total_internal_urls'] > 0) {
        echo "Internal URLs:\n";
        foreach ($report['internal_urls'] as $urlData) {
            echo "  - {$urlData['original']} ({$urlData['type']})\n";
        }
        echo "\n";
    }

    if ($report['total_image_urls'] > 0) {
        echo "Image URLs:\n";
        foreach ($report['image_urls'] as $imgUrl) {
            echo "  - $imgUrl\n";
        }
        echo "\n";
    }

    // If there's an image mapping file, update the article
    if ($imageMappingFile) {
        $imageMappings = $migrator->mapImageUrls($imageMappingFile);

        if (!empty($imageMappings)) {
            echo "Updating image references...\n";
            $updatedContent = $migrator->updateUrls($imageMappings);
            $migrator->saveUpdatedContent($updatedContent);
        }
    }

    // Save report
    $reportPath = __DIR__ . '/url-report-' . basename($articlePath, '.md') . '.json';
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    echo "\n✓ Reporte guardado en: $reportPath\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
