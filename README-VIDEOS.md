# Guía de Migración de Videos con Wistia

## ⚠️ REGLA CRÍTICA: Videos Deben Ser Migrados Correctamente

**Todos los videos de Wistia presentes en los artículos originales DEBEN ser incluidos en el artículo migrado.** Esto no es opcional. Nunca omitas este paso.

## ¿Dónde Pueden Aparecer los Videos?

Los videos pueden aparecer en dos lugares:

1. **En `main_blocks`:** La mayoría de los videos aparecen en el contenido principal del artículo
2. **Después del `intro`:** Algunos videos aparecen inmediatamente después del primer párrafo (intro), pero técnicamente están en `main_blocks` como el primer bloque

## Estructura del Bloque de Video

Los videos se estructuran como bloques `video` con el siguiente formato:

```yaml
main_blocks:
  # ... otros bloques ...
  -
    id: [unique-id]
    version: article_video_1
    video_url: 'https://incfile.wistia.com/medias/[VIDEO_ID]'
    show_video_object: false
    type: video
    enabled: true
  # ... otros bloques ...
```

### Campos del Bloque de Video

- **`id`:** UUID único para el bloque
- **`version`:** Siempre `article_video_1` para videos en artículos
- **`video_url`:** URL completa del video en Wistia (formato: `https://incfile.wistia.com/medias/[VIDEO_ID]`)
- **`show_video_object`:** Generalmente `false` (no mostrar objeto de video estructurado)
- **`type`:** Siempre `video`
- **`enabled`:** Siempre `true`

## Formato de URLs de Wistia

### URLs de Wistia en el HTML Original

En el HTML original, las URLs de Wistia pueden aparecer en diferentes formatos:

1. **URL de embed:**
   ```
   https://incfile.wistia.com/embed/iframe/[VIDEO_ID]?&autoPlay=true&playerColor=ff4a00
   ```

2. **URL de medias (formato correcto para Statamic):**
   ```
   https://incfile.wistia.com/medias/[VIDEO_ID]
   ```

### Conversión de URLs

**⚠️ IMPORTANTE:** Siempre usa el formato `https://incfile.wistia.com/medias/[VIDEO_ID]` en el artículo migrado.

**Proceso de conversión:**
1. Si encuentras una URL como `https://incfile.wistia.com/embed/iframe/fd8f308kih?&autoPlay=true&playerColor=ff4a00`
2. Extrae el ID del video: `fd8f308kih`
3. Convierte a formato medias: `https://incfile.wistia.com/medias/fd8f308kih`

**Ejemplo:**

**❌ Incorrecto:**
```yaml
video_url: 'https://incfile.wistia.com/embed/iframe/fd8f308kih?&autoPlay=true&playerColor=ff4a00'
```

**✅ Correcto:**
```yaml
video_url: 'https://incfile.wistia.com/medias/fd8f308kih'
```

## Cómo Encontrar Videos en el HTML Original

### Método 1: Buscar en el HTML

```bash
curl -s "https://bizee.com/articles/[slug]" | grep -o 'incfile.wistia.com[^"]*' | head -5
```

### Método 2: Buscar por ID de Video

Si conoces el ID del video (ej: `fd8f308kih`), busca:
```bash
curl -s "https://bizee.com/articles/[slug]" | grep -o '[VIDEO_ID]'
```

### Método 3: Buscar en el JSON del HTML

Los videos de Wistia también pueden estar en el JSON embebido del HTML. Busca por:
- `videoLink`
- `wistia`
- `incfile.wistia.com`

## Ubicación del Video en el Artículo

### Video Después del Intro

Si el video aparece inmediatamente después del primer párrafo (intro), debe ser el **primer bloque** en `main_blocks`:

```yaml
intro:
  -
    type: paragraph
    content:
      -
        type: text
        text: 'Primer párrafo del artículo...'

main_blocks:
  -
    id: [unique-id]
    version: article_video_1
    video_url: 'https://incfile.wistia.com/medias/[VIDEO_ID]'
    show_video_object: false
    type: video
    enabled: true
  -
    id: [other-block-id]
    version: rich_text_1
    content:
      # ... resto del contenido ...
```

### Video en el Contenido Principal

Si el video aparece en medio del contenido, debe estar en `main_blocks` en la posición correspondiente:

```yaml
main_blocks:
  -
    id: [rich-text-before]
    version: rich_text_1
    content:
      # ... contenido antes del video ...
  -
    id: [video-id]
    version: article_video_1
    video_url: 'https://incfile.wistia.com/medias/[VIDEO_ID]'
    show_video_object: false
    type: video
    enabled: true
  -
    id: [rich-text-after]
    version: rich_text_1
    content:
      # ... contenido después del video ...
```

## Ejemplo Completo

```yaml
---
id: [article-id]
blueprint: article
title: "Article Title"
# ... otros campos ...

intro:
  -
    type: paragraph
    content:
      -
        type: text
        text: 'First paragraph of the article...'

main_blocks:
  -
    id: video-intro-001
    version: article_video_1
    video_url: 'https://incfile.wistia.com/medias/fd8f308kih'
    show_video_object: false
    type: video
    enabled: true
  -
    id: content-001
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Main Content Heading'
      # ... resto del contenido ...
```

## Checklist de Videos

Antes de considerar una migración completa, verifica:

- [ ] ¿Identifiqué todos los videos del contenido original?
- [ ] ¿Todos los videos están incluidos en el artículo migrado?
- [ ] ¿Las URLs de Wistia usan el formato correcto (`https://incfile.wistia.com/medias/[VIDEO_ID]`)?
- [ ] ¿Los videos están en la posición correcta (después del intro o en el contenido principal)?
- [ ] ¿Cada bloque de video tiene `version: article_video_1`?
- [ ] ¿Cada bloque de video tiene `show_video_object: false`?
- [ ] ¿Cada bloque de video tiene `type: video` y `enabled: true`?

## Errores Comunes

### ❌ Error: Usar URL de embed en lugar de URL de medias
**Solución:** Siempre convierte las URLs de embed a formato medias:
- De: `https://incfile.wistia.com/embed/iframe/[ID]?params`
- A: `https://incfile.wistia.com/medias/[ID]`

### ❌ Error: Olvidar videos en el contenido
**Solución:** Siempre revisa el HTML original completo para identificar todos los videos presentes.

### ❌ Error: Colocar video en `intro` en lugar de `main_blocks`
**Solución:** El campo `intro` solo acepta contenido Bard (texto). Los videos deben ir en `main_blocks`, incluso si aparecen visualmente después del intro.

### ❌ Error: Usar `version` incorrecta
**Solución:** Para videos en artículos, siempre usa `version: article_video_1`.

## Integración con Scripts de Migración

Los scripts de migración (`migrate-article.php`, `migrate-complete.php`) deberían detectar automáticamente los videos de Wistia en el HTML y agregarlos como bloques `video` en `main_blocks`. Sin embargo, siempre verifica manualmente que todos los videos estén presentes y en el formato correcto.

## Referencias

- Ver `SCRIPTS-REFERENCE.md` para detalles de los scripts
- Ver `README.md` para el proceso completo de migración
- Ver `README-STRUCTURE.md` para estructura de bloques
- Ver `resources/views/sets/video/article_video_1.antlers.html` para el template de renderizado
