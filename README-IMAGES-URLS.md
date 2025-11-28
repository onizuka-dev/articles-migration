# Image and URL Migration Guide

This guide explains how to migrate images and URLs when migrating articles to the Statamic system.

## Migration Process

### Option 1: Complete Automated Migration

The `migrate-complete.php` script automates the entire process:

```bash
php migrate-complete.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business \
  content/collections/articles/2024-11-21.can-a-minor-own-a-business.md
```

This script:
1. Downloads all images from the original article
2. Saves them in appropriate folders (`articles/featured/` and `articles/main-content/`)
3. Analyzes all URLs in the article
4. Updates image references in the article file

### Option 2: Step-by-Step Migration

#### Step 1: Download Images

```bash
php download-images.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business
```

This script:
- Extracts all images from the article HTML
- Downloads and saves them to `public/assets/articles/featured/` and `public/assets/articles/main-content/`
- Generates a JSON file with mapping of original URLs to local paths

**Expected output:**
```
=== Downloading images for: can-a-minor-own-a-business ===

Article URL: https://bizee.com/articles/can-a-minor-own-a-business

Found 2 image(s):

Processing: https://bizee.com/images/article-featured.png
✓ Downloaded: articles/featured/can-a-minor-own-a-business.png

Processing: https://bizee.com/images/article-map.png
✓ Downloaded: articles/main-content/map-minor-business-ownership-states.png

✓ Report saved to: image-mapping-can-a-minor-own-a-business.json
```

#### Step 2: Migrate URLs

```bash
php migrate-urls.php \
  content/collections/articles/2024-11-21.can-a-minor-own-a-business.md \
  image-mapping-can-a-minor-own-a-business.json
```

This script:
- Analyzes all URLs in the article
- Classifies internal vs external URLs
- Updates image references using the generated mapping
- Generates a report of all URLs found

**Expected output:**
```
=== Analyzing URLs in: content/collections/articles/2024-11-21.can-a-minor-own-a-business.md ===

Internal URLs found: 5
Image URLs found: 2

Internal URLs:
  - https://bizee.com/articles/business-formation/choosing-the-right-business-structure (article)
  - https://bizee.com/business-formation/free-llc (business-formation)
  ...

Image URLs:
  - https://bizee.com/images/article-featured.png
  - https://bizee.com/images/article-map.png

Updating image references...
✓ File updated: content/collections/articles/2024-11-21.can-a-minor-own-a-business.md

✓ Report saved to: url-report-2024-11-21.can-a-minor-own-a-business.json
```

## Generated File Structure

### image-mapping-[slug].json

JSON file that maps original URLs to local paths:

```json
{
  "article_slug": "can-a-minor-own-a-business",
  "article_url": "https://bizee.com/articles/can-a-minor-own-a-business",
  "downloaded_at": "2024-11-21 10:30:00",
  "images": [
    {
      "original_url": "https://bizee.com/images/article-featured.png",
      "local_path": "articles/featured/can-a-minor-own-a-business.png",
      "type": "featured"
    },
    {
      "original_url": "https://bizee.com/images/article-map.png",
      "local_path": "articles/main-content/map-minor-business-ownership-states.png",
      "type": "main-content"
    }
  ]
}
```

### url-report-[slug].json

JSON file with analysis of all URLs:

```json
{
  "article_path": "content/collections/articles/2024-11-21.can-a-minor-own-a-business.md",
  "analyzed_at": "2024-11-21 10:35:00",
  "internal_urls": [
    {
      "original": "https://bizee.com/articles/business-formation/choosing-the-right-business-structure",
      "type": "article",
      "suggested": "https://bizee.com/articles/business-formation/choosing-the-right-business-structure"
    }
  ],
  "image_urls": [
    "https://bizee.com/images/article-featured.png"
  ],
  "total_internal_urls": 5,
  "total_image_urls": 2
}
```

## Image Directory Structure

Images are stored in `public/assets/` with the following structure:

```
public/assets/
├── articles/
│   ├── featured/          # Featured images (first image of the article)
│   │   └── [slug].png
│   └── main-content/      # Main content images
│       └── [slug]-[description].png
```

## URL Handling

### Internal URLs

Internal URLs from `bizee.com` are kept complete in the format:
```yaml
href: 'https://bizee.com/articles/business-formation/choosing-the-right-business-structure'
```

This allows:
- Maintaining link functionality
- Facilitating future migration if needed
- Preserving SEO and existing links

### External URLs

External URLs are kept as-is and automatically open in a new tab (handled by the frontend).

### Image URLs

Image URLs are converted from:
```
https://bizee.com/images/article-featured.png
```

To relative paths from the asset container:
```yaml
image: articles/featured/can-a-minor-own-a-business.png
```

## Troubleshooting

### Error: "No images found"

- Verify that the article URL is accessible
- Some sites may require specific headers or have anti-scraping protection
- In that case, download images manually

### Error: "Error downloading image"

- Check internet connectivity
- Some images may be protected or require authentication
- Verify that directories have write permissions

### Images don't update in article

- Verify that the JSON mapping file exists and has the correct format
- Check that URLs in the article exactly match those in the mapping
- Some URLs may have additional parameters (e.g., `?v=123`) that must be handled

## Manual Image Migration

If automated scripts don't work, you can migrate images manually:

1. **Identify images from the original article:**
   - Open the article in the browser
   - Inspect images (right click > Inspect)
   - Download each image

2. **Save in the correct structure:**
   - Featured image → `public/assets/articles/featured/[slug].png`
   - Content images → `public/assets/articles/main-content/[slug]-[description].png`

3. **Update article:**
   - Edit the article `.md` file
   - Replace image URLs with local paths

## Best Practices

1. **File names:**
   - Use the article slug as the base
   - Add description for content images
   - Keep original extensions (.png, .jpg, .webp)

2. **Optimization:**
   - Consider optimizing images before uploading
   - Use modern formats like WebP when possible
   - Compress large images

3. **Verification:**
   - Always verify that images display correctly in the CMS
   - Check that links work
   - Test on different devices

4. **Backup:**
   - Keep backups of original images
   - Save JSON mapping files for future reference
