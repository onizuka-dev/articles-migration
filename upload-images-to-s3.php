<?php

/**
 * Script to upload local images to S3
 *
 * This script takes locally downloaded images in public/assets/articles/
 * and uploads them to S3 maintaining the same folder structure.
 *
 * Usage: php upload-images-to-s3.php [ARTICLE_SLUG]
 * Example: php upload-images-to-s3.php can-a-minor-own-a-business
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class S3ImageUploader
{
    private $articleSlug;
    private $localBasePath;
    private $s3BasePath = 'articles';

    public function __construct($articleSlug)
    {
        $this->articleSlug = $articleSlug;
        $this->localBasePath = public_path('assets/articles');
    }

    /**
     * Checks if an image already exists in S3
     */
    private function imageExistsInS3($s3Path): bool
    {
        try {
            return Storage::disk('s3')->exists($s3Path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Uploads all article images to S3
     */
    public function uploadImages()
    {
        echo "=== Uploading images to S3 for: {$this->articleSlug} ===\n\n";

        // Verify that S3 disk is configured
        if (!Storage::disk('s3')->getDriver()) {
            echo "✗ Error: S3 disk is not configured correctly.\n";
            echo "  Verifica las variables de entorno:\n";
            echo "  - AWS_ACCESS_KEY_ID\n";
            echo "  - AWS_SECRET_ACCESS_KEY\n";
            echo "  - AWS_DEFAULT_REGION\n";
            echo "  - AWS_BUCKET\n";
            return false;
        }

        $uploaded = [];
        $errors = [];

        // Upload featured image
        $featuredPath = $this->localBasePath . '/featured/' . $this->articleSlug . '.webp';
        if (file_exists($featuredPath)) {
            $s3Path = $this->s3BasePath . '/featured/' . $this->articleSlug . '.webp';
            if ($this->imageExistsInS3($s3Path)) {
                echo "ℹ Image already exists in S3, skipping: {$s3Path}\n";
                $uploaded[] = $s3Path . ' (already existed)';
            } elseif ($this->uploadFile($featuredPath, $s3Path)) {
                $uploaded[] = $s3Path;
            } else {
                $errors[] = $featuredPath;
            }
        } else {
            // Try with other extensions
            foreach (['.png', '.jpg', '.jpeg'] as $ext) {
                $altPath = str_replace('.webp', $ext, $featuredPath);
                if (file_exists($altPath)) {
                    $s3Path = $this->s3BasePath . '/featured/' . $this->articleSlug . $ext;
                    if ($this->imageExistsInS3($s3Path)) {
                        echo "ℹ Image already exists in S3, skipping: {$s3Path}\n";
                        $uploaded[] = $s3Path . ' (ya existía)';
                        break;
                    } elseif ($this->uploadFile($altPath, $s3Path)) {
                        $uploaded[] = $s3Path;
                        break;
                    }
                }
            }
        }

        // Upload main content images
        // Find all images related to the article
        $mainContentDir = $this->localBasePath . '/main-content/';
        if (is_dir($mainContentDir)) {
            // Find files containing the slug or from the same article
            $allFiles = glob($mainContentDir . '*');
            $articleFiles = [];

            // First search for files starting with the slug
            $slugFiles = glob($mainContentDir . $this->articleSlug . '*');
            $articleFiles = array_merge($articleFiles, $slugFiles);

            // Also search for files that might be related (e.g., maps, infographics)
            // If there are no files with the slug, upload all recent files
            if (empty($articleFiles)) {
                // Find recently modified files (last 24 hours)
                foreach ($allFiles as $file) {
                    if (is_file($file) && filemtime($file) > (time() - 86400)) {
                        $articleFiles[] = $file;
                    }
                }
            }

            foreach ($articleFiles as $file) {
                $filename = basename($file);
                $s3Path = $this->s3BasePath . '/main-content/' . $filename;
                if ($this->imageExistsInS3($s3Path)) {
                    echo "ℹ Imagen ya existe en S3, omitiendo: {$s3Path}\n";
                    $uploaded[] = $s3Path . ' (ya existía)';
                } elseif ($this->uploadFile($file, $s3Path)) {
                    $uploaded[] = $s3Path;
                } else {
                    $errors[] = $file;
                }
            }
        }

        // Summary
        echo "\n=== Summary ===\n";
        echo "✓ Successfully uploaded images: " . count($uploaded) . "\n";
        if (!empty($uploaded)) {
            echo "\nImages in S3:\n";
            foreach ($uploaded as $path) {
                echo "  - {$path}\n";
            }
        }

        if (!empty($errors)) {
            echo "\n✗ Upload errors: " . count($errors) . "\n";
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }

        return empty($errors);
    }

    /**
     * Uploads a file to S3
     */
    private function uploadFile($localPath, $s3Path)
    {
        try {
            echo "Uploading: {$localPath} → s3://{$s3Path}\n";

            $contents = file_get_contents($localPath);
            $success = Storage::disk('s3')->put($s3Path, $contents, 'public');

            if ($success) {
                echo "  ✓ Subida exitosa\n";
                return true;
            } else {
                echo "  ✗ Error uploading\n";
                return false;
            }
        } catch (\Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Verifies that images are in S3
     */
    public function verifyUploads()
    {
        echo "\n=== Verifying images in S3 ===\n";

        $featuredPath = $this->s3BasePath . '/featured/' . $this->articleSlug . '.webp';
        $exists = Storage::disk('s3')->exists($featuredPath);

        if ($exists) {
            echo "✓ Featured image found: {$featuredPath}\n";
            $url = Storage::disk('s3')->url($featuredPath);
            echo "  URL: {$url}\n";
        } else {
            echo "✗ Featured image not found: {$featuredPath}\n";
        }

        // Verify content images
        $mainContentFiles = Storage::disk('s3')->files($this->s3BasePath . '/main-content/');
        // Find files that might be related to the article
        $articleFiles = array_filter($mainContentFiles, function($file) {
            $filename = basename($file);
            // Search by slug or recent files
            return strpos($filename, $this->articleSlug) !== false ||
                   strpos($filename, 'map-') !== false ||
                   strpos($filename, 'minor') !== false;
        });

        if (!empty($articleFiles)) {
            echo "\n✓ Content images found: " . count($articleFiles) . "\n";
            foreach ($articleFiles as $file) {
                echo "  - {$file}\n";
            }
        } else {
            echo "\n✗ No content images found\n";
        }
    }
}

// Execute script
if ($argc < 2) {
    echo "Usage: php upload-images-to-s3.php [ARTICLE_SLUG]\n";
    echo "Example: php upload-images-to-s3.php can-a-minor-own-a-business\n";
    exit(1);
}

$articleSlug = $argv[1];
$uploader = new S3ImageUploader($articleSlug);

if ($uploader->uploadImages()) {
    $uploader->verifyUploads();
    echo "\n✅ Process completed successfully\n";
} else {
    echo "\n❌ There were errors during the process\n";
    exit(1);
}
