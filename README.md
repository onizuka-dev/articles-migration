# Articles - Migration Guide

This directory contains scripts and documentation for migrating articles to the Statamic content system.

## 🚀 Quick Start

**Para empezar rápidamente, lee primero:** [`QUICK-START.md`](./QUICK-START.md)

Este es el entry point principal que contiene:
- Proceso rápido de migración (3 pasos)
- Reglas críticas que nunca olvidar
- Referencias a toda la documentación
- Solución de problemas comunes

## ⚠️ Important Rules

Before migrating, ensure you follow these structure and formatting rules:

0. **UUID:** ⚠️ **CRITICAL** - Each article MUST have a unique UUID v4. **NEVER copy the UUID from another article.** If two articles share the same UUID, Statamic will only recognize one of them, causing the other to not appear in the dashboard. Always generate a new UUID for each article.

1. **Intro:** Only the first paragraph goes in `intro`. All remaining content goes in `main_blocks`.
2. **Rich Text Blocks:** ⚠️ **CRITICAL:** Combine consecutive `rich_text` blocks into one, unless separated by another component (button, image, etc.). This rule must be applied automatically during migration.
3. **Lists:** All lists (including numbered lists) should be migrated as `bulletList`.
4. **Quotes:** ⚠️ **CRITICAL:** **ALWAYS use double quotes (`"`) for ALL string values in YAML. NEVER use single quotes (`'`).** This prevents issues with apostrophes and contractions (like `you'll`, `won't`, `Bizee's`, etc.) inside the text. **CRITICAL:** If a double-quoted string contains double quotes inside (like quoted words), escape them with `\"`. **⚠️ CRITICAL:** When using double quotes as wrapper, do NOT escape single quotes inside the text - leave them as-is.
5. **Links:** All links in `rich_text` content must use Bard format with `marks` and `attrs`. See formatting guide below.
6. **Line Breaks:** There must be exactly 1 line break (`hardBreak`) between paragraphs, headings, and lists.
7. **Images:** ⚠️ **MANDATORY** - All images MUST be downloaded and uploaded to S3, and referenced correctly in the article. This includes both `featured_image` and `article_image` blocks. **Never skip this step.** ⚠️ **CRITICAL:** Images must be in S3, NOT stored locally. Always use `download-and-upload-images-to-s3.php` which uploads directly to S3. Never save images in `public/assets/` locally.
8. **Links:** ⚠️ **MANDATORY** - All links from the original content MUST be included in the migrated article using Bard format with `marks` and `attrs`. **⚠️ CRITICAL:** A final link verification MUST be performed at the end of each migration as part of the main checklist. **This verification CANNOT be skipped or omitted.** **Always verify that no links are missing by comparing the production page with the migrated article.** Links must be properly formatted with correct `href`, `rel`, `target`, and `title` attributes. See `README-LINKS.md` for complete guidelines.
9. **Videos:** ⚠️ **MANDATORY** - All videos from Wistia present in the original article MUST be included in the migrated article as `video` blocks in `main_blocks`. Videos must use the correct Wistia URL format (`https://incfile.wistia.com/medias/[VIDEO_ID]`). **Never skip videos.** Videos can appear after the intro or anywhere in the main content. See `README-VIDEOS.md` for complete guidelines.
10. **SEO Fields:** ⚠️ **MANDATORY** - All migrated articles MUST include SEO fields in the frontmatter. These fields must be extracted from the production page: `seo_title`, `seo_meta_description`, `seo_custom_meta_title` (from `<title>` tag), `seo_custom_meta_description` (from meta description), `seo_canonical`, `seo_og_description`, `seo_og_title`, `seo_tw_title`, `seo_tw_description`, `seo_og_image`. ⚠️ **CRITICAL:** `seo_og_image` MUST always be the same image as `featured_image` (the hero image). **Never skip SEO fields.** See `README-SEO.md` for complete guidelines.
11. **Routing:** ⚠️ **MANDATORY** - All migrated articles MUST have their routes added to `app/Routing/migration/released-articles.php` and redirects added to `app/Routing/redirects.php`. The route format is `/articles/{slug_category}/{slug}`. The redirect format is `/articles/{old-slug}` => `/articles/{slug_category}/{slug}`. **Always verify if routes already exist before adding.** **Never skip routing.** This step is part of the main checklist.

**📚 Documentación Completa:**
- **`QUICK-START.md`** - 🚀 Entry point principal (empieza aquí)
- **`README-STRUCTURE.md`** - Reglas de estructura de contenido
- **`README-LISTS.md`** - Manejo de listas
- **`README-FORMATTING.md`** - Reglas de formato (quotes, links, line breaks)
- **`README-IMAGES.md`** - ⚠️ **CRÍTICO:** Procesamiento obligatorio de imágenes (deben estar en S3, NO localmente)
- **`README-LINKS.md`** - ⚠️ **CRÍTICO:** Verificación obligatoria de links
- **`README-VIDEOS.md`** - ⚠️ **CRÍTICO:** Migración obligatoria de videos con Wistia
- **`README-SEO.md`** - ⚠️ **CRÍTICO:** Campos SEO obligatorios en todos los artículos migrados
- **`README-ROUTING.md`** - ⚠️ **CRÍTICO:** Routing obligatorio (released-articles.php y redirects.php)
- **`SCRIPTS-REFERENCE.md`** - Referencia de todos los scripts

## Article Structure

Articles are stored in `content/collections/articles/` with the filename format:
```
YYYY-MM-DD.slug.md
```

## Main Components

### Blueprint
- Location: `resources/blueprints/collections/articles/article.yaml`
- Defines the article field structure

### Fieldsets Used

Articles use the following fieldsets (located in `resources/fieldsets/`):

1. **article_heading.yaml** - Article header
   - `subtitle`: Subtitle
   - `featured_image`: Featured image
   - `note_under_image`: Note under image (for disclaimers)

2. **article_blocks.yaml** - Article block builder
   - Contains a replicator with multiple block types:
     - `rich_text`: Rich formatted text
     - `article_image`: Images
     - `quote_box`: Quote boxes
     - `article_key_takeaways`: Article key points
     - `article_button`: Buttons/CTAs
     - `bordered_container`: Bordered containers
     - `info_table`: Information tables
     - `video`: Videos

3. **rich_text.yaml** - Rich text field (Bard)
   - Supports: headings (h2-h6), bold, italic, lists, links, images, tables

4. **article_image.yaml** - Article images
   - `image`: Image path
   - `caption`: Optional caption

5. **quote_box.yaml** - Quote boxes
   - `content`: Quote content (Bard)

6. **article_key_takeaways.yaml** - Key points
   - `heading`: Section title
   - `article_key_takeaways_content`: Content (Bard)

7. **article_button.yaml** - Buttons
   - `label`: Button label (Bard)
   - `url`: Destination URL
   - `open_in_new_tab`: Open in new tab

## Frontmatter Structure

```yaml
---
id: [UUID v4]
blueprint: article
title: 'Article Title'
subtitle: 'Subtitle (optional)'
featured_image: articles/featured/image-name.png
note_under_image: [Bard content - optional, for disclaimers]
article_author:
  - [Author UUID]
article_category: [Category UUID]
is_featured_article: false
likes: 0
slug_category: legal-and-compliance
intro: [Bard content]
main_blocks: [Array of blocks]
after_blocks: [Array of blocks]
footer_title: 'GET BIZEE PODCAST'
footer_description: '...'
footer_button_text: 'READ MORE'
footer_button_link: /get-bizee-podcast
footer_image: raw-real-unfiltered.jpg
page_settings_*: [Page settings]
seo_*: [SEO settings]
date: 'YYYY-MM-DD'
slug: article-slug
---
```

## Required Fields

- `id`: Unique UUID for the article
- `blueprint`: Always "article"
- `title`: Article title
- `article_author`: Array with author UUID(s) (maximum 2)
- `article_category`: Category UUID
- `date`: Publication date (format YYYY-MM-DD)
- `slug`: Article slug

## Bard Format (Rich Text)

Text content uses Statamic's Bard format, which is a structured JSON format:

```yaml
content:
  -
    type: paragraph
    content:
      -
        type: text
        text: 'Normal text'
  -
    type: heading
    attrs:
      level: 2
    content:
      -
        type: text
        text: 'H2 Title'
  -
    type: bulletList
    content:
      -
        type: listItem
        content:
          -
            type: paragraph
            content:
              -
                type: text
                marks:
                  -
                    type: bold
                text: 'Bold text'
  -
    type: bulletList
    content:
      -
        type: listItem
        content:
          -
            type: paragraph
            content:
              -
                type: text
                text: 'First item'
      -
        type: listItem
        content:
          -
            type: paragraph
            content:
              -
                type: text
                text: 'Second item'
```

## Available Block Types

### rich_text
Rich text block with full formatting.

### article_image
Image with optional caption.

### quote_box
Highlighted quote box.

### article_key_takeaways
Article key points section.

### article_button
Button/CTA with label and URL.

### bordered_container
Bordered container.

### info_table
Information table.

### video
Embedded video.

## Reference IDs

### Common Authors
- Carrie Buchholz-Powers: `64daab56-842c-4efb-8002-b496df244091`

### Common Categories
- Legal and Compliance: `aaed7ffe-ddd1-4a17-a623-dee2dbea7750`
- Strategies: `162d407d-f019-4291-9e53-6bc0888ea598`

## Migration Process

⚠️ **IMPORTANT:** Always follow ALL steps in order. Skipping any step (especially image processing) will result in an incomplete migration.

1. **Prepare content**
   - Extract text from original article
   - Identify sections, headings, lists, images
   - Identify CTAs and buttons

2. **Create file**
   - Generate unique UUID for the article
   - Create file with format: `YYYY-MM-DD.slug.md`
   - Set publication date

3. **Structure content**
   - Convert HTML/plain text to Bard format
   - Create appropriate blocks (rich_text, article_image, etc.)
   - **IMPORTANT:** Only the first paragraph goes in `intro`
   - All remaining content goes in `main_blocks`
   - **IMPORTANT:** Combine consecutive `rich_text` blocks into one, unless separated by another component (button, image, etc.)

4. **Process images** ⚠️ **MANDATORY STEP**
   - **ALWAYS** download and upload images to S3 using `download-and-upload-images-to-s3.php`
   - ⚠️ **CRITICAL:** This script uploads images **directly to S3**. Never save images locally in `public/assets/`
   - Update `featured_image` field with the correct S3 path (`articles/featured/[slug].webp`)
   - Add `article_image` blocks for content images with correct S3 paths (`articles/main-content/[slug]-[desc].webp`)
   - **This step MUST be done for every migration, no exceptions**
   - **Never use local paths or URLs - always use S3 paths**

5. **Add metadata**
   - Assign author(s)
   - Assign category
   - Configure SEO
   - Configure page settings

6. **Verify**
   - Check that all required fields are present
   - Verify valid YAML format
   - Verify all images are correctly referenced from S3
   - ⚠️ **MANDATORY:** Verify that ALL links from the original content are included and properly formatted
   - Test in Statamic CMS

## Scripts and Helpers

- `migrate-article.php`: Base script to generate article structure (template)
- `button-helper.php`: Helper to generate `article_button` blocks with standard format (no bold, left-aligned)
- `list-helper.php`: Helper to generate `bulletList` blocks (all lists should be bulletList, even if numbered in HTML)
- `upload-images-to-s3.php`: Script to upload local images to S3
- `download-images-improved.py`: Improved script to download content images
- `migrate-urls.php`: Script to analyze and migrate URLs
- `migrate-complete.php`: Complete script that runs the entire migration process

### Button Helper

To create buttons with the correct format (no bold, left-aligned):

```php
require_once 'articles-migration/button-helper.php';

$button = generateArticleButton(
    'mcanminor1',  // Unique ID
    "Take a quiz to decide: LLC, S Corp, C Corp, or Nonprofit?\n\nTake a quiz now",
    'https://bizee.com/business-entity-quiz/explain',
    false  // open_in_new_tab
);
```

See `README-BUTTONS.md` for more details.

### List Types

**IMPORTANT:** All lists (including numbered lists from HTML) should be migrated as `bulletList`. This is the project standard.

See `README-LISTS.md` for details and use `list-helper.php` for generating lists.

### Article Structure

**IMPORTANT Rules:**
- Only the first paragraph goes in `intro`
- All remaining content goes in `main_blocks`
- Combine consecutive `rich_text` blocks into one, unless separated by another component (button, image, etc.)

See `README-STRUCTURE.md` for complete structure guidelines.

## List Types

### bulletList (Lista con viñetas) ✅ USAR SIEMPRE
Use `bulletList` for **all lists**, including numbered lists from HTML:
- Features
- Benefits
- Options
- Steps in a process
- Instructions
- Sequential items
- **Any list, regardless of whether it was numbered in HTML**

**IMPORTANT:** All lists should be migrated as `bulletList`, even if they were numbered (`<ol>`) in the original HTML.

## Important Notes

- ⚠️ **CRITICAL:** UUIDs must be unique for each article. **NEVER copy a UUID from another article.** Each article must have its own unique UUID v4. If two articles share the same UUID, Statamic will only recognize one of them, and the other will not appear in the dashboard. Always generate a new UUID using `generateUUID()` or a UUID generator tool.
- Images must be in the correct asset container
- Bard format is sensitive to YAML indentation
- Blocks must have unique IDs within the article
- Each block must have `enabled: true` to be visible
- **Always use `bulletList` for all lists, even if they were numbered in HTML**
