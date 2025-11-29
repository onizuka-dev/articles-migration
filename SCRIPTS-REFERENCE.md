# Migration Scripts Reference

This guide documents all available scripts and their parameters.

## Available Scripts

### 1. `verify-and-fix-article.php`
**Purpose:** Verifies and fixes migrated articles (buttons, images, category, URLs and redirects)

**IMPORTANT:** Before running this script, ensure your article follows these structure rules:
- Only the first paragraph should be in `intro`
- All remaining content should be in `main_blocks`
- Consecutive `rich_text` blocks should be combined into one, unless separated by another component

See `README-STRUCTURE.md` for complete structure guidelines.

**Parameters:**
```bash
php verify-and-fix-article.php [ARTICLE_FILE_PATH] [CATEGORY_SLUG] [OLD_URL]
```

**Example:**
```bash
php verify-and-fix-article.php \
  ../content/collections/articles/2020-12-15.why-a-series-llc-is-the-best-for-your-real-estate-investment-business.md \
  business-formation \
  /articles/why-a-series-llc-is-the-best-for-your-real-estate-investment-business
```

**Detailed parameters:**
- `ARTICLE_FILE_PATH`: Path to the article `.md` file
- `CATEGORY_SLUG`: Category slug (must exist in `content/collections/categories/`)
- `OLD_URL`: Previous article URL to create redirect (optional)

**What it does:**
- Validates that the category exists in `content/collections/categories/`
- Verifies that buttons have the correct format (no bold, left-aligned)
- Automatically fixes buttons if necessary
- Verifies if images already exist in S3
- Updates `slug_category` in the article if necessary
- Generates the new path: `/articles/{category}/{slug}`
- Adds the new path to `app/Routing/migration/released-articles.php`
- Adds redirect from old path to new in `app/Routing/redirects.php`
- Only saves changes if corrections were made

---

### 2. `download-and-upload-images-to-s3.php` ⭐ RECOMMENDED
**Purpose:** Downloads images and uploads them directly to S3 (without saving locally)

**Parameters:**
```bash
php download-and-upload-images-to-s3.php [ARTICLE_URL] [ARTICLE_SLUG]
```

**Example:**
```bash
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business
```

**What it does:**
- Temporarily downloads images from the original article
- Uploads them directly to S3
- Automatically deletes temporary files
- **Does NOT save images in `public/assets/`**
- Checks if they already exist in S3 before uploading
- Generates mapping of original URLs → S3 paths

**Requirements:**
- AWS environment variables configured in `.env`

---

### 2b. `upload-images-to-s3.php`
**Purpose:** Uploads local images to S3 (only if you already have them downloaded)

**Parameters:**
```bash
php upload-images-to-s3.php [ARTICLE_SLUG]
```

**Example:**
```bash
php upload-images-to-s3.php can-a-minor-own-a-business
```

**What it does:**
- Searches for images in `public/assets/articles/featured/[slug].webp`
- Searches for images in `public/assets/articles/main-content/[slug]*`
- Checks if they already exist in S3 before uploading
- Only uploads if they don't exist

**When to use:** Only if you already have images downloaded locally and need to upload them to S3.

---

### 3. `migrate-complete.php`
**Purpose:** Complete automated migration (downloads images and migrates URLs)

**Parameters:**
```bash
php migrate-complete.php [ARTICLE_URL] [SLUG] [ARTICLE_FILE_PATH]
```

**Example:**
```bash
php migrate-complete.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business \
  content/collections/articles/2024-11-21.can-a-minor-own-a-business.md
```

**What it does:**
1. Downloads images from the original article
2. Analyzes and maps URLs
3. Updates the article file with correct paths

**Detailed parameters:**
- `ARTICLE_URL`: Full URL of the original article (e.g., `https://bizee.com/articles/...`)
- `SLUG`: Article slug (e.g., `can-a-minor-own-a-business`)
- `ARTICLE_FILE_PATH`: Relative or absolute path to the article `.md` file

---

### 4. `download-images-improved.py`
**Purpose:** Downloads images from article content (filters navigation/footer)

**Parameters:**
```bash
python3 download-images-improved.py [ARTICLE_URL] [SLUG]
```

**Example:**
```bash
python3 download-images-improved.py \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business
```

**What it does:**
- Downloads only content images (excludes navigation, footer, thumbnails)
- Saves to `public/assets/articles/featured/` and `public/assets/articles/main-content/`
- Generates JSON file with URL mapping

**Detailed parameters:**
- `ARTICLE_URL`: Full URL of the original article
- `SLUG`: Article slug (used to name files)

---

### 5. `migrate-urls.php`
**Purpose:** Analyzes and migrates URLs in the article

**Parameters:**
```bash
php migrate-urls.php [ARTICLE_FILE_PATH] [IMAGE_MAPPING_FILE]
```

**Example:**
```bash
php migrate-urls.php \
  ../content/collections/articles/2024-11-21.can-a-minor-own-a-business.md \
  image-mapping-can-a-minor-own-a-business.json
```

**What it does:**
- Analyzes all URLs in the article
- Classifies internal vs external URLs
- Updates image references using the mapping

**Detailed parameters:**
- `ARTICLE_FILE_PATH`: Path to the article `.md` file
- `IMAGE_MAPPING_FILE`: JSON file with image URL mapping (optional)

---

### 6. `migrate-article.php`
**Purpose:** Base script/template to generate article structure

**Parameters:**
```bash
php migrate-article.php
```

**What it does:**
- Shows information about the migration process
- Generates base article structure
- Includes `generateArticleButton()` helper function to create buttons

**Note:** This script is primarily a template and needs to be modified for each specific article.

---

## Recommended Migration Flow

### Option 1: Complete Automated Migration ⭐ RECOMMENDED
```bash
# 1. Complete content migration (downloads images, uploads to S3 and migrates URLs)
php migrate-complete.php \
  https://bizee.com/articles/[slug] \
  [slug] \
  content/collections/articles/[date].[slug].md

# 2. Verify and fix button format, category and redirects
php verify-and-fix-article.php \
  content/collections/articles/[date].[slug].md \
  [category] \
  [old_url]
```

**Note:** `migrate-complete.php` now uploads images directly to S3 without saving them locally.

### Option 2: Step-by-Step Migration
```bash
# 1. Download and upload images directly to S3
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]

# 2. Migrate URLs
php migrate-urls.php \
  content/collections/articles/[date].[slug].md \
  image-mapping-[slug].json

# 3. Verify and fix format, category and redirects
php verify-and-fix-article.php \
  content/collections/articles/[date].[slug].md \
  [category] \
  [old_url]
```

## Available Helpers

### `button-helper.php`
**Function:** `generateArticleButton($id, $label, $url, $openInNewTab = false)`

**Usage:**
```php
require_once 'articles-migration/button-helper.php';

$button = generateArticleButton(
    'mcanminor1',
    "Take a quiz to decide: LLC, S Corp, C Corp, or Nonprofit?\n\nTake a quiz now",
    'https://bizee.com/business-entity-quiz/explain',
    false
);
```

**Generates buttons with standard format:**
- No bold
- Left-aligned

## Required Environment Variables

For scripts that interact with S3 (`upload-images-to-s3.php`):

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-2
AWS_BUCKET=your_bucket_name
AWS_URL=https://s3.us-east-2.amazonaws.com/your_bucket_name
```

## Generated Files

Scripts generate the following files:

- `image-mapping-[slug].json`: Mapping of original URLs → local paths
- `url-report-[slug].json`: Report of all URLs found
- `public/assets/articles/featured/[slug].webp`: Featured image
- `public/assets/articles/main-content/[slug]-*.webp`: Content images

## Important Notes

1. **Article slug:** Obtained from the filename (e.g., `2024-11-21.can-a-minor-own-a-business.md` → slug: `can-a-minor-own-a-business`)

2. **Relative paths:** Scripts assume they are run from `articles-migration/`, so paths must be relative to that directory or absolute.

3. **Image verification:** Scripts check if images already exist in S3 before uploading to avoid duplicates.

4. **Button format:** All buttons must follow the standard format (no bold, left-aligned). The `verify-and-fix-article.php` script automatically fixes this.
