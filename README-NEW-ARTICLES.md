# Crear Nuevos Artículos desde Google Docs

Este documento describe cómo crear nuevos artículos usando el orchestrator `new-article-orchestrator`.

## Requisitos Previos

1. Tener una URL de Google Docs con el contenido del artículo
2. El documento debe tener permisos de "Cualquier persona con el enlace puede ver"
3. El documento debe contener la información requerida (ver estructura abajo)

## Estructura del Documento de Google Docs

El documento debe contener la siguiente información (puede estar en cualquier orden):

### Campos Requeridos

```
Title: [Título del artículo]
Category: [business-formation|legal|manage-your-company|resources|start-a-business|side-hustles]
Author: [Nombre del autor]
Meta Title: [Título para SEO - máx 60 caracteres]
Meta Description: [Descripción para SEO - máx 160 caracteres]

[Contenido del artículo aquí...]
```

### Campos Opcionales

```
Featured Image: [URL de la imagen hero]
Date: [YYYY-MM-DD] (si no se especifica, usa fecha actual)
Slug: [url-slug] (si no se especifica, se genera del título)
```

### Ejemplo de Documento

```
Title: How to Start an LLC in Texas
Category: business-formation
Author: John Smith
Meta Title: Start an LLC in Texas | Step-by-Step Guide 2024
Meta Description: Learn how to start an LLC in Texas with our comprehensive guide. We cover everything from choosing a name to filing your Certificate of Formation.
Featured Image: https://example.com/texas-llc-image.jpg

Starting a business in Texas is an exciting venture. The Lone Star State offers numerous advantages for entrepreneurs...

## Benefits of an LLC in Texas

Texas is one of the most business-friendly states in the nation. Here are some key benefits:

- No state income tax
- Strong legal protections
- Flexible management structure

## Step 1: Choose a Business Name

Your LLC name must be unique and include "Limited Liability Company" or its abbreviations...

[más contenido...]

## Key Takeaways

- Texas LLCs enjoy no state income tax
- Filing costs approximately $300
- Processing takes 2-3 business days
```

## Cómo Usar el Orchestrator

### Opción 1: Usando el Agente Directamente

```
Usa el agente new-article-orchestrator con la URL del Google Doc:
https://docs.google.com/document/d/[DOC_ID]/edit
```

### Opción 2: Paso a Paso Manual

1. **Parsear el Google Doc:**
```bash
php articles-migration/parse-google-doc.php "https://docs.google.com/document/d/[DOC_ID]/edit"
```

2. **Procesar imágenes:** Descargar y subir a S3

3. **Buscar UUIDs:**
```bash
php articles-migration/find-author-uuid.php "[nombre_autor]"
php articles-migration/find-category-uuid.php "[categoria]"
```

4. **Crear el archivo:** Usar el assembler para generar el .md

5. **Agregar routing:**
```bash
# Agregar a released-articles.php
'/articles/[category]/[slug]',
```

## Diferencias con Migración

| Aspecto | Migración | Nuevo Artículo |
|---------|-----------|----------------|
| Fuente | URL de producción | Google Docs |
| hold | `false` | `true` |
| Redirects | Sí | No |
| Verificación | Contra producción | Visual solamente |

## Archivos Creados

- **Artículo:** `content/collections/articles/YYYY-MM-DD.slug.md`
- **Routing:** Entrada en `app/Routing/migration/released-articles.php`

## Quotes Intercaladas (MUY IMPORTANTE)

Cuando el documento fuente contiene citas textuales de personas (quotes), estas **DEBEN** ser bloques `quote_box` intercalados dentro de `main_blocks`, **NO** solo al final en `after_blocks`.

### Cómo Identificar Quotes en el Documento

Las quotes típicamente aparecen como:
- Texto entre comillas atribuido a una persona: `Lacerte says: "It takes intentionality..."`
- Citas destacadas o pull quotes
- Frases de expertos o fundadores mencionados en el artículo

### Estructura Correcta de quote_box

```yaml
main_blocks:
  -
    id: mb001intro
    version: rich_text_1
    content:
      # ... contenido introductorio ...
    type: rich_text
    enabled: true
  -
    id: quote001
    version: quote_box_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'La cita textual va aquí sin las comillas.'
    type: quote_box
    enabled: true
  -
    id: mb002next
    version: rich_text_1
    content:
      # ... siguiente sección de contenido ...
    type: rich_text
    enabled: true
```

### Reglas para Quotes Intercaladas

1. **Extraer la quote del texto narrativo** - Si dice `Scott emphasizes: "People buy from people"`, crear un quote_box con "People buy from people" y dejar el texto narrativo como "Scott emphasizes that trust comes from personal connection."

2. **Posicionar después del contexto** - La quote_box debe ir DESPUÉS del párrafo que la introduce, no antes.

3. **Una quote por quote_box** - Cada cita importante debe tener su propio bloque.

4. **IDs únicos** - Usar formato `quote001`, `quote002`, etc.

5. **Sin comillas en el texto** - El texto dentro del quote_box NO debe tener comillas, el componente ya las añade visualmente.

### Ejemplo Real

**Documento fuente:**
```
Lacerte emphasizes: "It takes intentionality around product, purpose, people, and culture strategies" to build companies lasting decades.
```

**Resultado en YAML:**
```yaml
  -
    id: mb002trust
    version: rich_text_1
    content:
      - type: paragraph
        content:
          - type: text
            text: 'Lacerte emphasizes the importance of intentionality to build companies lasting decades.'
    type: rich_text
    enabled: true
  -
    id: quote001lacerte
    version: quote_box_1
    content:
      - type: paragraph
        content:
          - type: text
            text: 'It takes intentionality around product, purpose, people, and culture strategies.'
    type: quote_box
    enabled: true
```

## Notas Importantes

1. Los artículos nuevos se crean con `hold: true` para revisión antes de publicar
2. NO se crean redirects (el artículo es nuevo, no hay URL antigua)
3. Las imágenes deben subirse a S3 antes de publicar
4. **Las quotes deben ser quote_box intercalados, NO texto inline con comillas**
5. El artículo NO será visible hasta que:
   - Se cambie `hold: true` a `hold: false` en el archivo .md
   - O se publique desde el panel de Statamic

## Categorías Válidas

- `legal` - Artículos legales
- `business-formation` - Formación de empresas
- `manage-your-company` - Gestión empresarial
- `resources` - Recursos generales
- `start-a-business` - Iniciar un negocio
- `side-hustles` - Negocios secundarios

## Agentes Involucrados

| Agente | Función |
|--------|---------|
| `new-article-orchestrator` | Coordina todo el proceso |
| `new-article-gdoc-extractor` | Extrae contenido del Google Doc |
| `migration-image-processor` | Procesa y sube imágenes a S3 |
| `migration-bard-converter` | Convierte contenido a formato Bard |
| `migration-special-blocks` | Procesa CTAs, quotes, key takeaways |
| `new-article-assembler` | Ensambla el archivo .md final |
| `migration-visual-verifier` | Verifica visualmente (opcional) |

## Solución de Problemas

### El documento no se puede descargar
- Verificar que el documento esté compartido como "Cualquier persona con el enlace puede ver"
- Verificar que la URL sea correcta

### Falta información en el documento
- El script reportará qué campos faltan
- Agregar la información faltante al documento y volver a ejecutar

### Las imágenes no se suben
- Verificar que las URLs de las imágenes sean accesibles
- Las imágenes de Google Docs pueden requerir descarga manual

### El autor no se encuentra
- Buscar el autor exacto: `php articles-migration/find-author-uuid.php "[nombre]"`
- Si no existe, crear el autor primero en Statamic
