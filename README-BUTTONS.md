# Guide for Creating Buttons (article_button)

## Standard Format

All buttons must be created with the following format:
- **No bold** (no `marks` with `type: bold`)
- **Left-aligned** (`textAlign: left`)

## Using the Helper

To ensure consistency, use the `generateArticleButton()` helper function:

```php
require_once 'articles-migration/button-helper.php';

// Simple button
$button = generateArticleButton(
    'mcanminor1',  // Unique ID
    'Take a quiz now',  // Button text
    'https://bizee.com/business-entity-quiz/explain'  // URL
);

// Multi-line button
$button = generateArticleButton(
    'mcanminor10',
    "Form Your LLC \$0 + State Fee.\nIncludes Free Registered Agent Service for a Full Year.\n\nGet Started Today",
    'https://bizee.com/business-formation/start-an-llc'
);

// Button that opens in new tab
$button = generateArticleButton(
    'external-link-1',
    'Visit External Site',
    'https://example.com',
    true  // open_in_new_tab
);
```

## Generated YAML Structure

The helper generates the following structure:

```yaml
-
  id: mcanminor1
  version: article_button_1
  label:
    -
      type: paragraph
      attrs:
        textAlign: left
      content:
        -
          type: text
          text: 'Take a quiz to decide: LLC, S Corp, C Corp, or Nonprofit?'
        -
          type: hardBreak
        -
          type: hardBreak
        -
          type: text
          text: 'Take a quiz now'
  url: 'https://bizee.com/business-entity-quiz/explain'
  open_in_new_tab: false
  type: article_button
  enabled: true
```

## Important Notes

1. **No bold**: The helper automatically removes any bold formatting
2. **Left alignment**: All buttons are left-aligned by default
3. **HardBreaks**: Line breaks (`\n`) are automatically converted to `hardBreak`
4. **Unique IDs**: Make sure to use unique IDs within the article

## Manual Migration

If you're creating buttons manually, make sure to follow this format:

```yaml
-
  id: [unique-id]
  version: article_button_1
  label:
    -
      type: paragraph
      attrs:
        textAlign: left  # ← Important: left, not center
      content:
        -
          type: text
          # ← Important: DO NOT include marks with type: bold
          text: 'Button text'
        -
          type: hardBreak  # For line breaks
        -
          type: text
          text: 'More text'
  url: 'https://example.com'
  open_in_new_tab: false
  type: article_button
  enabled: true
```

## Common Errors to Avoid

❌ **DON'T do this:**
```yaml
attrs:
  textAlign: center  # ← Incorrect
content:
  -
    type: text
    marks:
      -
        type: bold  # ← Incorrect: no bold
    text: 'Text'
```

✅ **Do this:**
```yaml
attrs:
  textAlign: left  # ← Correct
content:
  -
    type: text
    # No marks
    text: 'Text'  # ← Correct
```
