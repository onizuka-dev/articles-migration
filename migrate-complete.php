<?php

/**
 * Complete article migration script
 *
 * This script automates the entire migration process:
 * 1. Downloads images from the original article
 * 2. Analyzes and maps URLs
 * 3. Updates the article file with correct paths
 *
 * Usage: php migrate-complete.php [ARTICLE_URL] [SLUG] [ARTICLE_FILE_PATH]
 *
 * Example:
 * php migrate-complete.php https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business content/collections/articles/2024-11-21.can-a-minor-own-a-business.md
 */

require_once __DIR__ . '/migrate-urls.php';

class CompleteMigrator
{
    private $articleUrl;
    private $articleSlug;
    private $articlePath;

    public function __construct($articleUrl, $articleSlug, $articlePath)
    {
        $this->articleUrl = $articleUrl;
        $this->articleSlug = $articleSlug;
        $this->articlePath = $articlePath;
    }

    /**
     * Executes the complete migration process
     */
    public function migrate()
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     Migración Completa de Artículo                        ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";

        echo "Artículo: {$this->articleSlug}\n";
        echo "URL: {$this->articleUrl}\n";
        echo "Archivo: {$this->articlePath}\n\n";

        // Step 1: Download and upload images directly to S3
        echo "═══════════════════════════════════════════════════════════\n";
        echo "STEP 1: Downloading and uploading images to S3...\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        require_once __DIR__ . '/download-and-upload-images-to-s3.php';
        $s3Uploader = new DirectS3ImageUploader($this->articleUrl, $this->articleSlug);
        $s3Uploader->process();

        $imageMappingFile = __DIR__ . '/image-mapping-' . $this->articleSlug . '.json';

        echo "\n";

        // Step 2: Analyze and migrate URLs
        echo "═══════════════════════════════════════════════════════════\n";
        echo "STEP 2: Analyzing and migrating URLs...\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        $urlMigrator = new UrlMigrator($this->articlePath);

        // Update image URLs if there's a mapping
        if (file_exists($imageMappingFile)) {
            $imageMappings = $urlMigrator->mapImageUrls($imageMappingFile);

            if (!empty($imageMappings)) {
                echo "Updating image references in article...\n";
                $updatedContent = $urlMigrator->updateUrls($imageMappings);
                $urlMigrator->saveUpdatedContent($updatedContent);
            }
        }

        // Generate URL report
        $urlReport = $urlMigrator->generateUrlReport();
        $reportPath = __DIR__ . '/url-report-' . basename($this->articlePath, '.md') . '.json';
        file_put_contents($reportPath, json_encode($urlReport, JSON_PRETTY_PRINT));

        echo "\n";

        // Final summary
        echo "═══════════════════════════════════════════════════════════\n";
        echo "MIGRATION SUMMARY\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "✓ Images downloaded: " . (file_exists($imageMappingFile) ? "Yes" : "No") . "\n";
        echo "✓ Internal URLs found: {$urlReport['total_internal_urls']}\n";
        echo "✓ Image URLs found: {$urlReport['total_image_urls']}\n";
        echo "\n";

        echo "Generated files:\n";
        if (file_exists($imageMappingFile)) {
            echo "  - $imageMappingFile\n";
        }
        echo "  - $reportPath\n";
        echo "\n";

        echo "✓ Migration completed!\n";
    }
}

// Execute script
if ($argc < 4) {
    echo "Usage: php migrate-complete.php [ARTICLE_URL] [SLUG] [ARTICLE_FILE_PATH]\n\n";
    echo "Example:\n";
    echo "php migrate-complete.php \\\n";
    echo "  https://bizee.com/articles/can-a-minor-own-a-business \\\n";
    echo "  can-a-minor-own-a-business \\\n";
    echo "  content/collections/articles/2024-11-21.can-a-minor-own-a-business.md\n";
    exit(1);
}

$articleUrl = $argv[1];
$articleSlug = $argv[2];
$articlePath = $argv[3];

try {
    $migrator = new CompleteMigrator($articleUrl, $articleSlug, $articlePath);
    $migrator->migrate();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
