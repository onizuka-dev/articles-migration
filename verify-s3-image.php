<?php
/**
 * Verify if an image exists in S3
 * Usage: php articles-migration/verify-s3-image.php <s3-path>
 * Example: php articles-migration/verify-s3-image.php articles/featured/my-image.webp
 */

if ($argc < 2) {
    echo "Usage: php articles-migration/verify-s3-image.php <s3-path>\n";
    echo "Example: php articles-migration/verify-s3-image.php articles/featured/my-image.webp\n";
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;

$path = $argv[1];
$disk = Storage::disk('s3');

if ($disk->exists($path)) {
    echo "✓ Image exists in S3: " . $path . PHP_EOL;
    echo "  URL: " . $disk->url($path) . PHP_EOL;
    exit(0);
} else {
    echo "✗ ERROR: Image NOT found in S3: " . $path . PHP_EOL;
    exit(1);
}
