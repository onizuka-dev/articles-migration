# Guide for Creating Buttons (article_button)

## ⚠️ REGLA CRÍTICA: CTAs Deben Ser Migrados

**Todos los CTAs (Call-to-Action buttons) del contenido del artículo DEBEN ser migrados como bloques `article_button`.** Los CTAs pueden aparecer en dos variantes:
- **CTAs negros** (`bg-black`) con texto blanco
- **CTAs blancos/naranjas** (`bg-primary-600`) con texto blanco

**⚠️ IMPORTANTE:** Solo migrar CTAs que están en el **contenido principal del artículo**, NO los que están en el layout (header, footer, sidebar, featured articles).

**⚠️ POSICIÓN CRÍTICA:** Los CTAs deben estar en las **mismas posiciones relativas** que en producción. Cada artículo puede tener los CTAs en posiciones diferentes según su contenido:
- Analiza el HTML de producción para determinar después de qué bloque de contenido está cada CTA.
- Coloca cada CTA en el artículo migrado en la misma posición relativa que en producción.
- El script `verify-migration.php` compara automáticamente las posiciones y reporta si hay diferencias.

**⚠️ VERIFICACIÓN OBLIGATORIA:** Al final de CADA migración, DEBES revisar el contenido original y asegurar que TODOS los CTAs del contenido estén migrados como bloques `article_button` y en las posiciones correctas. Esta verificación es parte del checklist principal.

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
