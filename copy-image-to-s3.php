<?php

/**
 * Script to copy/download an image and upload to S3 with a new name
 *
 * Usage: php copy-image-to-s3.php [SOURCE_URL] [DESTINATION_PATH]
 * Example: php copy-image-to-s3.php "https://s3.us-east-2.amazonaws.com/bizee-website-assets/blog_top-image_0099.webp" "articles/featured/entrepreneur-workshop-documents.webp"
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

if ($argc < 3) {
    echo "Usage: php copy-image-to-s3.php [SOURCE_URL] [DESTINATION_PATH]\n";
    echo "Example: php copy-image-to-s3.php \"https://s3.us-east-2.amazonaws.com/bizee-website-assets/blog.webp\" \"articles/featured/name.webp\"\n";
    exit(1);
}

$sourceUrl = $argv[1];
$destPath = $argv[2];

echo "Source: $sourceUrl\n";
echo "Destination: $destPath\n\n";

// Check if destination already exists
if (Storage::disk('s3')->exists($destPath)) {
    echo "✓ Image already exists at destination: $destPath\n";
    exit(0);
}

// Download image
echo "Downloading image...\n";
$ch = curl_init($sourceUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || !$imageData) {
    echo "✗ Error downloading image (HTTP $httpCode)\n";
    exit(1);
}

echo "✓ Downloaded " . strlen($imageData) . " bytes\n";

// Determine content type
$mimeType = 'image/webp';
if (strpos($contentType, 'jpeg') !== false || strpos($contentType, 'jpg') !== false) {
    $mimeType = 'image/jpeg';
} elseif (strpos($contentType, 'png') !== false) {
    $mimeType = 'image/png';
} elseif (strpos($contentType, 'gif') !== false) {
    $mimeType = 'image/gif';
}

// Upload to S3
echo "Uploading to S3...\n";
try {
    $result = Storage::disk('s3')->put($destPath, $imageData, [
        'visibility' => 'public',
        'ContentType' => $mimeType,
    ]);

    if ($result) {
        echo "✓ Uploaded successfully to: $destPath\n";

        // Verify
        if (Storage::disk('s3')->exists($destPath)) {
            echo "✓ Verified: Image exists in S3\n";
        }
    } else {
        echo "✗ Upload failed\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone!\n";
