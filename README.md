# Articles - Migration Guide

This directory contains scripts and documentation for migrating articles to the Statamic content system.

## ⚠️ Important Rules

Before migrating, ensure you follow these structure rules:

1. **Intro:** Only the first paragraph goes in `intro`. All remaining content goes in `main_blocks`.
2. **Rich Text Blocks:** Combine consecutive `rich_text` blocks into one, unless separated by another component (button, image, etc.).
3. **Lists:** All lists (including numbered lists) should be migrated as `bulletList`.

See `README-STRUCTURE.md` for complete structure guidelines and `README-LISTS.md` for list handling.

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

4. **Add metadata**
   - Assign author(s)
   - Assign category
   - Configure SEO
   - Configure page settings

5. **Verify**
   - Check that all required fields are present
   - Verify valid YAML format
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

- UUIDs must be unique for each article
- Images must be in the correct asset container
- Bard format is sensitive to YAML indentation
- Blocks must have unique IDs within the article
- Each block must have `enabled: true` to be visible
- **Always use `bulletList` for all lists, even if they were numbered in HTML**
