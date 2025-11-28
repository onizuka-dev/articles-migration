# Quick Article Migration Guide

## Complete Process in 4 Steps

### 1. Migrate Article Content

Create the article `.md` file following the structure of existing articles.

### 2. Download and Upload Images to S3

**IMPORTANT:** Images are uploaded directly to S3 without saving them locally.

```bash
cd articles-migration
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business
```

This script:
- Downloads images temporarily
- Uploads them directly to S3
- Automatically deletes temporary files
- **Does NOT save images in `public/assets/`**
- Checks if images already exist in S3 before uploading

**Requirements:** Make sure you have AWS environment variables configured in your `.env`:
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`

### 3. Update References in Article

**Option A: Complete Automated Migration**
```bash
cd articles-migration
php migrate-complete.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business \
  ../content/collections/articles/2024-11-21.can-a-minor-own-a-business.md
```

**Option B: Step by Step**
```bash
# 1. Analyze and update URLs
php migrate-urls.php \
  ../content/collections/articles/2024-11-21.can-a-minor-own-a-business.md \
  image-mapping-can-a-minor-own-a-business.json
```

## Generated File Structure

After running the scripts, you'll have:

```
articles-migration/
├── image-mapping-[slug].json      # Mapping of original URLs → local paths
├── url-report-[slug].json         # Report of all URLs found
└── ...

public/assets/articles/
├── featured/
│   └── [slug].png                 # Featured image
└── main-content/
    └── [slug]-[n].png             # Content images
```

## Verification

1. **Verify local images:**
   - Open `public/assets/articles/featured/` and `public/assets/articles/main-content/`
   - Confirm that all images were downloaded correctly

2. **Verify images in S3:**
   - The `upload-images-to-s3.php` script will show the URLs of uploaded images
   - You can verify manually in the Statamic panel or by accessing the URLs

3. **Verify article:**
   - Open the article `.md` file
   - Confirm that image paths are correct (e.g., `articles/featured/[slug].webp`)
   - Paths should be relative, Statamic will resolve them from S3

4. **Test in CMS:**
   - Open Statamic
   - Navigate to the migrated article
   - Verify that images display correctly

## Quick Troubleshooting

### Images don't download
- Check internet connectivity
- Some sites may block automated downloads
- Download images manually and save them in the correct folders

### Images don't upload to S3
- Verify that AWS environment variables are configured correctly
- Make sure you have write permissions on the S3 bucket
- Check script logs for specific errors

### Images don't display after uploading
- Verify that paths in the Markdown article match paths in S3
- Make sure images have public permissions in S3
- Check asset container configuration in `content/assets/assets.yaml`

### URLs don't update
- Verify that the JSON mapping file exists
- Check that paths in JSON match those in the article
- Manually update paths in the `.md` file

### Permission error
- Make sure you have write permissions on `public/assets/`
- Run: `chmod -R 755 public/assets/articles/`

## Complete Example

```bash
# 1. Navigate to migration directory
cd articles-migration

# 2. Download images
python3 download-images.py \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business

# 3. Download and upload images to S3 (directly, without saving locally)
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business

# 4. Migrate URLs and update article
php migrate-complete.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business \
  ../content/collections/articles/2024-11-21.can-a-minor-own-a-business.md

# 5. Verify results
cat image-mapping-can-a-minor-own-a-business.json
# Note: Images are already in S3, not saved locally
```

## Important Notes

- **Backup:** Always backup the article before running scripts
- **Image format:** Scripts maintain original format (.png, .jpg, .webp)
- **Internal URLs:** Kept complete to preserve functionality and SEO
- **File names:** Generated automatically based on article slug
