# Upload Images via Statamic API

## Overview

This script uploads images using Statamic's AssetContainer API instead of directly uploading to S3. This approach ensures that Statamic's events are triggered, which should automatically generate thumbnails for the Control Panel dashboard.

## Why Use This Method?

When uploading images directly to S3 using Laravel's Storage facade, Statamic doesn't know about the new files and won't generate thumbnails. By using Statamic's `AssetContainer::makeAsset()` and `$asset->save()`, we trigger Statamic's asset processing pipeline, which includes:

- Asset metadata generation
- Thumbnail generation for CP dashboard
- Asset indexing in Statamic's Stache cache
- Event dispatching for asset creation

## Usage

```bash
php articles-migration/upload-images-via-statamic.php [ARTICLE_URL] [SLUG] [CP_URL]
```

**Parameters:**
- `ARTICLE_URL`: Full URL of the article (e.g., `https://bizee.com/articles/business-domain-name-email`)
- `SLUG`: Article slug (e.g., `business-domain-name-email`)
- `CP_URL`: (Optional) Control Panel URL, defaults to `https://bizee.test/cp`

**Example:**
```bash
php articles-migration/upload-images-via-statamic.php \
  https://bizee.com/articles/business-domain-name-email \
  business-domain-name-email \
  https://bizee.test/cp
```

## How It Works

1. **Download HTML**: Fetches the article HTML from production
2. **Extract Images**: Identifies featured and content images from the HTML
3. **Generate Descriptive Filenames**: Analyzes image URLs and article context to create semantic filenames (e.g., "woman-working-laptop" instead of just using article slug)
4. **Download Temporarily**: Downloads each image to a temporary directory
5. **Upload to S3**: Uploads the file to S3 using Laravel Storage
6. **Create Statamic Asset**: Uses `AssetContainer::makeAsset()` to create an Asset object
7. **Save Asset**: Calls `$asset->save()` to trigger Statamic events and thumbnail generation
8. **Cleanup**: Removes temporary files

## Descriptive Filename Generation

The script automatically generates descriptive filenames for featured images based on:

1. **Image URL Analysis**: Extracts keywords from the original filename (e.g., "blog_top-image_0013.jpg" → analyzes for descriptive words)
2. **Keyword Detection**: Recognizes common patterns:
   - People: woman, man, person, business
   - Actions: working, smiling, drinking
   - Objects: laptop, glasses, phone, document
   - Settings: office, meeting, team
3. **Article Context**: Uses article slug to infer image context (e.g., "add-members-llc" → business-partnership keywords)
4. **Fallback Logic**: If no keywords found, cleans original filename or uses article context

**Example:**
- Original URL: `blog_top-image_0013.jpg`
- Article: "add-members-llc"
- Generated name: `business-partnership.webp` or `woman-working-laptop.webp` (depending on detected keywords)

This ensures images have semantic, SEO-friendly names that are easier to identify and manage.

## Key Differences from Direct S3 Upload

### Direct S3 Upload (`download-and-upload-images-to-s3.php`)
- ✅ Fast
- ✅ Simple
- ❌ No thumbnail generation
- ❌ Assets don't appear in CP until manual refresh
- ❌ No Statamic events triggered

### Statamic API Upload (`upload-images-via-statamic.php`)
- ✅ Thumbnails generated automatically
- ✅ Assets immediately visible in CP
- ✅ Statamic events triggered
- ✅ Proper asset indexing
- ⚠️ Slightly slower (due to Statamic processing)

## Technical Details

### Asset Creation Process

```php
// 1. Upload file to S3
Storage::disk('s3')->put($s3Path, $contents, 'public');

// 2. Create Asset object
$asset = $container->makeAsset($s3Path);

// 3. Save to trigger events
$asset->save();
```

### Asset Container Configuration

The script uses the `assets` container configured in `content/assets/assets.yaml`:
- **Handle**: `assets`
- **Disk**: `s3`
- **Allow uploads**: `true`

### Thumbnail Generation

Statamic automatically generates thumbnails when:
1. An asset is created via `makeAsset()` and `save()`
2. The asset is an image type (jpg, png, webp, etc.)
3. The image dimensions are within limits (configured in `config/statamic/assets.php`)

Thumbnails are stored in Statamic's cache and displayed in the CP dashboard.

## Verification

After running the script:

1. **Check CP Dashboard**: Navigate to `{CP_URL}/assets/articles/featured/` or `{CP_URL}/assets/articles/main-content/`
2. **Verify Thumbnails**: Images should show thumbnails in the CP
3. **Check S3**: Files should be in S3 at the expected paths
4. **Check Mapping**: Review `image-mapping-{slug}.json` for processed images

## Troubleshooting

### Issue: Thumbnails not generating

**Possible causes:**
- Image dimensions too large (check `config/statamic/assets.php` thumbnail limits)
- GD or Imagick not properly configured
- Statamic cache needs clearing

**Solutions:**
```bash
# Clear Statamic cache
php artisan statamic:stache:clear

# Check image manipulation driver
# In config/statamic/assets.php, verify 'driver' => 'gd' or 'imagick'
```

### Issue: Assets not appearing in CP

**Possible causes:**
- Stache cache not refreshed
- Asset container permissions issue

**Solutions:**
```bash
# Clear and rebuild Stache
php artisan statamic:stache:clear
php artisan statamic:stache:refresh
```

### Issue: Save() method fails

**Possible causes:**
- File permissions on S3
- Asset container configuration issue
- Missing metadata

**Note**: Even if `save()` fails, the file is still in S3. You may need to manually refresh the CP or use Statamic's CP to "discover" the asset.

## Integration with Migration Process

This script can replace `download-and-upload-images-to-s3.php` in the migration workflow:

**Current workflow:**
```bash
php download-and-upload-images-to-s3.php [URL] [SLUG]
```

**New workflow:**
```bash
php upload-images-via-statamic.php [URL] [SLUG] [CP_URL]
```

Both scripts generate the same `image-mapping-{slug}.json` file, so the rest of the migration process remains unchanged.

## Future Improvements

- [ ] Add option to use CP API authentication (if needed)
- [ ] Add progress bar for multiple images
- [ ] Add option to regenerate thumbnails for existing assets
- [ ] Add validation for image dimensions before upload
- [ ] Add support for batch processing multiple articles

## Related Documentation

- `README-IMAGES.md` - General image processing guidelines
- `download-and-upload-images-to-s3.php` - Direct S3 upload script (for comparison)
- Statamic Assets Documentation: https://statamic.dev/assets
