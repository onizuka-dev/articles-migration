# 📋 Proceso Paso a Paso de Migración de Artículos

Este documento describe el proceso completo que se sigue cuando se migra un artículo de producción a Statamic CMS.

## 🎯 Resumen del Proceso

El proceso de migración consta de 10 pasos principales que deben ejecutarse en orden:

1. **Preparación y lectura de documentación**
2. **Extracción de contenido de producción**
3. **Identificación y procesamiento de imágenes**
4. **Extracción de metadatos y SEO**
5. **Creación de la estructura del artículo**
6. **Conversión de contenido a formato Bard**
7. **Procesamiento de elementos especiales**
8. **Configuración de routing y redirects**
9. **Validaciones finales**
10. **Verificación del checklist crítico**

---

## 📖 Paso 1: Preparación y Lectura de Documentación

**Antes de empezar cualquier migración:**

1. **Leer el checklist crítico:**
   ```bash
   # Leer: articles-migration/CRITICAL-CHECKLIST.md
   ```
   Este documento contiene los 7 puntos críticos que DEBEN verificarse en cada migración.

2. **Revisar la documentación relevante:**
   - `README.md` - Estructura general
   - `README-IMAGES.md` - Procesamiento de imágenes
   - `README-LINKS.md` - Manejo de links
   - `README-SEO.md` - Campos SEO
   - `README-VIDEOS.md` - Videos de Wistia
   - `README-ROUTING.md` - Routing y redirects

3. **Verificar que los scripts estén disponibles:**
   - `download-and-upload-images-to-s3.php` o `upload-images-via-statamic.php`
   - Scripts de verificación si están disponibles

---

## 🔍 Paso 2: Extracción de Contenido de Producción

**Objetivo:** Obtener el HTML completo del artículo desde producción.

### Scripts/Comandos Ejecutados:

```bash
# Opción 1: Usando curl (más confiable)
curl -s "https://bizee.com/articles/[slug]" > /tmp/article.html

# Opción 2: Usando pup (si está disponible)
curl -s "https://bizee.com/articles/[slug]" | pup > /tmp/article.html
```

### Qué Extraer:

1. **Título del artículo** - Del `<title>` o `<h1>`
2. **Subtitle** - Si existe, aparece después del título
3. **Meta description** - Del `<meta name="description">`
4. **Contenido principal** - Todo el texto, headings, listas, etc.
5. **Imágenes** - Featured image y content images
6. **Links** - Todos los links del contenido principal
7. **Videos** - Si hay videos de Wistia
8. **CTAs** - Botones de call-to-action
9. **Key Takeaways** - Si existe al final del artículo

### Decisiones cuando algo no mapea directamente:

- **Si `pup` falla:** Usar `curl` directamente y procesar el HTML manualmente
- **Si el contenido está en JavaScript:** Extraer del HTML renderizado, no del source
- **Si hay contenido dinámico:** Verificar en el navegador y extraer el HTML final

---

## 🖼️ Paso 3: Identificación y Procesamiento de Imágenes

**Objetivo:** Identificar todas las imágenes y subirlas a S3.

### Scripts/Comandos Ejecutados:

```bash
# Opción 1: Script automático (recomendado)
php articles-migration/upload-images-via-statamic.php \
  https://bizee.com/articles/[slug] \
  [slug] \
  https://bizee.test/cp

# Opción 2: Script de descarga y subida directa
php articles-migration/download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]

# Opción 3: Manual (si los scripts fallan)
# 1. Descargar imagen localmente
curl -s "[IMAGE_URL]" -o /tmp/image-name.webp

# 2. Subir a S3 usando Statamic
php -r "
require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;

\$container = AssetContainer::findByHandle('assets');
\$disk = Storage::disk('s3');
\$localPath = '/tmp/image-name.webp';
\$s3Path = 'articles/featured/image-name.webp'; // o articles/main-content/

\$disk->put(\$s3Path, file_get_contents(\$localPath));
\$asset = \$container->makeAsset(\$s3Path);
\$asset->save();
"
```

### Qué Identificar:

1. **Featured Image (Hero):**
   - Buscar en el header del artículo
   - Path: `articles/featured/[descriptive-name].webp`
   - Nombre descriptivo basado en el contenido de la imagen (no solo el slug del artículo)

2. **Content Images:**
   - Buscar todas las `<img>` tags en el contenido principal
   - Excluir imágenes de sidebar, header, footer, featured articles
   - Path: `articles/main-content/[descriptive-name].webp`

### Decisiones cuando algo no mapea directamente:

- **Si el script no encuentra imágenes:** Buscar manualmente en el HTML usando `grep` o procesamiento de texto
- **Si la imagen tiene un nombre genérico:** Generar un nombre descriptivo basado en el `alt` text o el contexto
- **Si la imagen no está en formato .webp:** Convertir o mantener el formato original si es necesario
- **Si el script falla con URLs directas:** Descargar localmente primero y luego subir

### Validaciones:

- ✅ Todas las imágenes están en S3 (verificar con `Storage::disk('s3')->exists()`)
- ✅ Los paths son correctos (`articles/featured/` o `articles/main-content/`)
- ✅ Las imágenes están registradas como assets en Statamic

---

## 📊 Paso 4: Extracción de Metadatos y SEO

**Objetivo:** Extraer todos los campos SEO y metadatos del artículo.

### Scripts/Comandos Ejecutados:

```bash
# Extraer título
curl -s "https://bizee.com/articles/[slug]" | grep -o '<title>[^<]*</title>'

# Extraer meta description
curl -s "https://bizee.com/articles/[slug]" | grep -o 'name="description" content="[^"]*"'

# Extraer canonical URL
curl -s "https://bizee.com/articles/[slug]" | grep -o 'rel="canonical" href="[^"]*"'

# Extraer Open Graph tags
curl -s "https://bizee.com/articles/[slug]" | grep -o 'property="og:[^"]*" content="[^"]*"'

# Extraer Twitter tags
curl -s "https://bizee.com/articles/[slug]" | grep -o 'name="twitter:[^"]*" content="[^"]*"'
```

### Qué Extraer:

1. **SEO Fields:**
   - `seo_custom_meta_title` - Del `<title>` tag (EXACTO de producción)
   - `seo_custom_meta_description` - Del meta description (EXACTO de producción)
   - `seo_canonical` - URL canónica
   - `seo_og_title`, `seo_og_description`, `seo_og_image`
   - `seo_tw_title`, `seo_tw_description`

2. **Metadatos del Artículo:**
   - `title` - Título del artículo
   - `subtitle` - Si existe
   - `date` - Fecha de publicación (formato YYYY-MM-DD)
   - `slug` - Slug del artículo
   - `slug_category` - Categoría del slug (legal, taxes, strategies, etc.)

3. **Autor y Categoría:**
   - Identificar el autor del artículo
   - Buscar UUID del autor en la base de datos o documentación
   - Identificar la categoría y su UUID

### Decisiones cuando algo no mapea directamente:

- **Si no hay meta description:** Usar el primer párrafo del artículo como fallback
- **Si el título tiene formato especial:** Extraer exactamente como está, sin modificar
- **Si hay múltiples autores:** Usar el primero o los primeros dos (máximo 2 autores)
- **Si la categoría no existe:** Verificar las categorías disponibles y usar la más cercana

### Validaciones:

- ✅ Todos los campos SEO están presentes
- ✅ Los valores son EXACTOS de producción (no inventados)
- ✅ El formato de fecha es correcto (YYYY-MM-DD)
- ✅ El UUID del autor y categoría son correctos

---

## 🏗️ Paso 5: Creación de la Estructura del Artículo

**Objetivo:** Crear el archivo markdown con la estructura básica.

### Scripts/Comandos Ejecutados:

```bash
# Generar UUID único (NUNCA copiar de otro artículo)
# Usar un generador de UUID v4 o:
php -r "echo Ramsey\Uuid\Uuid::uuid4()->toString();"
```

### Estructura a Crear:

```yaml
---
id: [UUID v4 ÚNICO]
blueprint: article
title: "[Título exacto de producción]"
subtitle: "[Subtitle si existe]"
featured_image: articles/featured/[nombre-descriptivo].webp
article_author:
  - [UUID del autor]
article_category: [UUID de la categoría]
slug_category: [categoría]
hold: true
published: true
date: 'YYYY-MM-DD'
slug: [slug-del-articulo]
seo_custom_meta_title: "[Título SEO exacto de producción]"
seo_custom_meta_description: "[Descripción SEO exacta de producción]"
seo_canonical: https://bizee.com/articles/[slug]
# ... otros campos SEO
intro: [Bard content - solo primer párrafo]
main_blocks: []
after_blocks: []
---
```

### Decisiones cuando algo no mapea directamente:

- **Si no hay subtitle:** Omitir el campo (no poner `subtitle: null`)
- **Si la fecha no está clara:** Usar la fecha de publicación más reciente o la fecha actual
- **Si el slug tiene caracteres especiales:** Mantener exactamente como está en producción

### Validaciones:

- ✅ UUID es único (verificar que no existe en otros artículos)
- ✅ Todos los campos requeridos están presentes
- ✅ El formato YAML es válido
- ✅ Las comillas son dobles (`"`) para todos los strings

---

## ✍️ Paso 6: Conversión de Contenido a Formato Bard

**Objetivo:** Convertir el contenido HTML/texto a formato Bard de Statamic.

### Proceso Manual (No hay script automático):

1. **Identificar el primer párrafo:**
   - Va en `intro` (solo el primer párrafo)
   - Resto del contenido va en `main_blocks`

2. **Crear bloques `rich_text`:**
   - Combinar contenido consecutivo en un solo bloque `rich_text`
   - Separar solo cuando hay imágenes, botones, videos, etc.

3. **Convertir elementos HTML a Bard:**
   - `<h2>` → `type: heading, attrs: { level: 2 }`
   - `<p>` → `type: paragraph`
   - `<ul>`, `<ol>` → `type: bulletList` (SIEMPRE bulletList, nunca orderedList)
   - `<strong>`, `<b>` → `marks: [{ type: bold }]`
   - `<em>`, `<i>` → `marks: [{ type: italic }]`
   - `<a>` → `marks: [{ type: link, attrs: { href, rel, target, title } }]`

### Estructura de Bloques:

```yaml
main_blocks:
  -
    id: main1
    version: rich_text_1
    content:
      - type: paragraph
      - type: heading
        attrs:
          level: 2
        content:
          - type: text
            text: "Heading text"
      - type: paragraph
        content:
          - type: text
            text: "Paragraph text"
    type: rich_text
    enabled: true
  -
    id: img1
    version: article_image_1
    image: articles/main-content/[nombre].webp
    alt: "[alt text]"
    type: article_image
    enabled: true
  -
    id: main2
    version: rich_text_1
    content: [...]
    type: rich_text
    enabled: true
```

### Decisiones cuando algo no mapea directamente:

- **Si hay listas numeradas:** Convertir a `bulletList` (regla del proyecto)
- **Si hay múltiples párrafos consecutivos:** Combinar en un solo bloque `rich_text`
- **Si hay saltos de línea:** Usar `type: paragraph` vacío o `hardBreak`
- **Si hay texto con formato complejo:** Mantener la estructura exacta de producción

### Validaciones:

- ✅ Solo el primer párrafo está en `intro`
- ✅ Los bloques `rich_text` consecutivos están combinados
- ✅ Todas las listas son `bulletList`
- ✅ El formato Bard es válido (indentación correcta)

---

## 🎨 Paso 7: Procesamiento de Elementos Especiales

**Objetivo:** Identificar y convertir elementos especiales (CTAs, videos, quotes, Key Takeaways).

### CTAs (article_button):

**Cómo identificar:**
- Botones con texto como "Get Started", "Learn More", "Take Quiz"
- Pueden ser negros (`bg-black`) o naranjas (`bg-primary-600`)
- Típicamente tienen título, subtítulo y botón

**Estructura:**
```yaml
-
  id: [unique-id]
  version: article_button_1
  label:
    -
      type: paragraph
      content:
        -
          type: text
          text: "[Button text]"
  url: "[URL]"
  open_in_new_tab: false
  type: article_button
  enabled: true
```

**Decisiones:**
- Si el CTA tiene múltiples líneas: Combinar en un solo párrafo con `\n`
- Si el botón abre en nueva pestaña: `open_in_new_tab: true`
- Posicionar el CTA exactamente donde está en producción

### Videos (Wistia):

**Cómo identificar:**
- Buscar `<script>` tags con `wistia.com` o `incfile.wistia.com`
- Buscar `data-wistia-id` o similar en el HTML
- El video ID está en la URL: `https://incfile.wistia.com/medias/[VIDEO_ID]`

**Estructura:**
```yaml
-
  id: [unique-id]
  version: video_1
  video_url: https://incfile.wistia.com/medias/[VIDEO_ID]
  type: video
  enabled: true
```

**Decisiones:**
- Si el video está al inicio: Colocarlo como primer bloque en `main_blocks` después del `intro`
- Si el video está en medio: Colocarlo exactamente donde está en producción

### Quotes (quote_box):

**⚠️ IMPORTANTE: Los quotes DEBEN ser bloques `quote_box` INTERCALADOS en `main_blocks`, NO solo al final.**

**Cómo identificar:**
- Buscar elementos con `style="--quote-box-color:var(--primary-600)"`
- Texto destacado en cajas especiales
- Texto entre comillas atribuido a personas: `Lacerte says: "..."`
- Frases de expertos, fundadores o entrevistados

**Estructura:**
```yaml
-
  id: [unique-id]
  version: quote_box_1
  content:
    -
      type: paragraph
      content:
        -
          type: text
          text: "[Quote text SIN comillas]"
  type: quote_box
  enabled: true
```

**Reglas de Posicionamiento:**
1. **INTERCALAR en main_blocks** - El quote_box va DESPUÉS del párrafo que lo introduce
2. **Extraer del texto narrativo** - Si dice `Scott says: "People buy from people"`, crear:
   - Un rich_text con "Scott emphasizes that trust comes from personal connection."
   - Un quote_box con "People buy from people."
3. **Sin comillas en el texto** - El componente añade las comillas visualmente
4. **IDs únicos** - Usar formato `quote001`, `quote002`, etc.

**Ejemplo correcto:**
```yaml
main_blocks:
  -
    id: mb002trust
    type: rich_text
    content:
      - type: paragraph
        content:
          - type: text
            text: 'Lacerte emphasizes the importance of building lasting companies.'
  -
    id: quote001lacerte
    type: quote_box
    version: quote_box_1
    content:
      - type: paragraph
        content:
          - type: text
            text: 'It takes intentionality around product, purpose, people, and culture strategies.'
  -
    id: mb003next
    type: rich_text
    # siguiente sección...
```

**Decisiones:**
- Si el quote está dentro de un párrafo: Extraerlo y crear un bloque separado INTERCALADO
- Mantener el texto exacto del quote pero SIN las comillas
- **NUNCA** poner todos los quotes solo en `after_blocks`

### Key Takeaways:

**Cómo identificar:**
- Buscar sección "Key Takeaways:" al final del artículo
- Lista de puntos importantes

**Estructura:**
```yaml
after_blocks:
  -
    id: [unique-id]
    version: article_key_takeaways_1
    heading: 'Key Takeaways'
    article_key_takeaways_version: rich_text_1
    article_key_takeaways_content:
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
                    text: "[Takeaway text]"
    type: article_key_takeaways
    enabled: true
```

**Decisiones:**
- SIEMPRE va en `after_blocks`, nunca en `main_blocks`
- Convertir la lista a formato `bulletList` en Bard

### Validaciones:

- ✅ Todos los CTAs están migrados como `article_button`
- ✅ Todos los videos están migrados como `video`
- ✅ Todos los quotes están migrados como `quote_box`
- ✅ Key Takeaways está en `after_blocks` usando `article_key_takeaways`

---

## 🔗 Paso 8: Procesamiento de Links

**Objetivo:** Extraer, verificar y formatear todos los links del contenido.

### Scripts/Comandos Ejecutados:

```bash
# Extraer todos los links del contenido principal
curl -s "https://bizee.com/articles/[slug]" | \
  grep -o 'href="[^"]*"' | \
  grep -vE '(header|footer|sidebar|featured|podcast)' | \
  sort -u
```

### Proceso:

1. **Extraer links de producción:**
   - Filtrar solo links del contenido principal
   - Excluir header, footer, sidebar, featured articles, podcast

2. **Verificar cada link:**
   - Comparar con el artículo migrado
   - Verificar que el texto del link sea correcto
   - Verificar que la URL sea exacta

3. **Formatear en Bard:**
   ```yaml
   -
     type: text
     marks:
       -
         type: link
         attrs:
           href: "[URL]"
           rel: "noopener noreferrer"  # para externos
           target: _blank  # para externos
           title: null
     text: "[Link text]"
   ```

### Decisiones cuando algo no mapea directamente:

- **Links externos:**
  - `rel: "noopener noreferrer"`
  - `target: _blank`
  - `title: null`

- **Links internos:**
  - `rel: null`
  - `target: null`
  - `title: null`

- **Si el link tiene texto complejo:** Mantener exactamente como está en producción
- **Si el link está en una lista:** Mantener la estructura de la lista

### Validaciones:

- ✅ Todos los links del contenido principal están incluidos
- ✅ Los URLs son exactos de producción (no inventados)
- ✅ Los atributos `rel` y `target` son correctos según el tipo de link
- ✅ El texto del link coincide con producción

---

## 🛣️ Paso 9: Configuración de Routing y Redirects

**Objetivo:** Agregar el artículo a las rutas y crear redirects del URL antiguo.

### Archivos a Modificar:

1. **`app/Routing/migration/released-articles.php`:**
   ```php
   return [
       // ... otros artículos
       '/articles/[slug_category]/[slug]',
   ];
   ```

2. **`app/Routing/redirects.php`:**
   ```php
   return [
       // ... otros redirects
       '/articles/[old-slug]' => '/articles/[slug_category]/[slug]',
   ];
   ```

### Proceso:

1. **Verificar que no exista:**
   - Buscar el slug en `released-articles.php`
   - Buscar el redirect en `redirects.php`

2. **Agregar routing:**
   - Formato: `/articles/{slug_category}/{slug}`
   - Ejemplo: `/articles/legal/multiple-eins`

3. **Agregar redirect:**
   - Formato: `/articles/{old-slug}` => `/articles/{slug_category}/{slug}`
   - Ejemplo: `/articles/multiple-eins` => `/articles/legal/multiple-eins`

### Decisiones cuando algo no mapea directamente:

- **Si el slug_category no coincide:** Verificar la categoría correcta
- **Si ya existe el routing:** No duplicar, verificar si es el mismo artículo
- **Si hay múltiples redirects posibles:** Agregar todos los redirects necesarios

### Validaciones:

- ✅ El routing está agregado en `released-articles.php`
- ✅ El redirect está agregado en `redirects.php`
- ✅ El formato es correcto
- ✅ No hay duplicados

---

## ✅ Paso 10: Validaciones Finales

**Objetivo:** Verificar que todo esté correcto antes de finalizar.

### Checklist Crítico (OBLIGATORIO):

1. **✅ Imágenes:**
   ```bash
   # Verificar que todas las imágenes estén en S3
   php -r "
   require __DIR__ . '/vendor/autoload.php';
   \$app = require_once __DIR__ . '/bootstrap/app.php';
   \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

   use Illuminate\Support\Facades\Storage;

   \$disk = Storage::disk('s3');
   \$images = [
       'articles/featured/[nombre].webp',
       'articles/main-content/[nombre].webp',
   ];

   foreach (\$images as \$path) {
       if (\$disk->exists(\$path)) {
           echo '✓ ' . \$path . '\n';
       } else {
           echo '✗ ' . \$path . ' NO ENCONTRADA\n';
       }
   }
   "
   ```

2. **✅ Links:**
   - Comparar todos los links de producción con el artículo migrado
   - Verificar que ningún link esté faltando
   - Verificar que los URLs sean exactos

3. **✅ CTAs:**
   - Verificar que todos los CTAs estén migrados como `article_button`
   - Verificar que estén en las posiciones correctas

4. **✅ Contenido:**
   - Verificar que NO se haya inventado contenido
   - Comparar sección por sección con producción
   - Verificar que todos los headings, párrafos, listas coincidan

5. **✅ Status:**
   ```yaml
   hold: true
   published: true
   ```

6. **✅ SEO:**
   - Verificar que `seo_custom_meta_title` sea EXACTO de producción
   - Verificar que `seo_custom_meta_description` sea EXACTO de producción
   - Verificar que todos los campos SEO estén presentes

7. **✅ Key Takeaways:**
   - Si existe, verificar que esté en `after_blocks`
   - Verificar que use el fieldset `article_key_takeaways`

8. **✅ Estructura:**
   - Verificar que solo el primer párrafo esté en `intro`
   - Verificar que los bloques `rich_text` consecutivos estén combinados
   - Verificar que las imágenes sean bloques separados (no dentro de `rich_text`)

9. **✅ YAML:**
   ```bash
   # Verificar sintaxis YAML
   php -r "
   \$yaml = file_get_contents('content/collections/articles/[fecha].[slug].md');
   \$parsed = yaml_parse(\$yaml);
   if (\$parsed === false) {
       echo '✗ Error en YAML\n';
   } else {
       echo '✓ YAML válido\n';
   }
   "
   ```

10. **✅ UUID:**
    ```bash
    # Verificar que el UUID sea único
    grep -r "id: [UUID]" content/collections/articles/
    # Debe aparecer solo una vez
    ```

### Scripts de Verificación (si están disponibles):

```bash
# Script de verificación automática
php articles-migration/verify-migration.php \
  content/collections/articles/[fecha].[slug].md \
  https://bizee.com/articles/[slug]
```

### Decisiones cuando algo no mapea directamente:

- **Si falta una imagen:** Descargarla y subirla inmediatamente
- **Si falta un link:** Agregarlo en formato Bard correcto
- **Si hay un error de YAML:** Corregir la indentación o sintaxis
- **Si el UUID está duplicado:** Generar uno nuevo único

### Validaciones Adicionales:

- ✅ No hay `text: null` en el contenido Bard (debe ser string)
- ✅ Todos los strings usan comillas dobles (`"`)
- ✅ Las imágenes están registradas como assets en Statamic
- ✅ El artículo se puede abrir en el dashboard de Statamic sin errores

---

## 📝 Resumen del Flujo Completo

```
1. Leer CRITICAL-CHECKLIST.md
   ↓
2. Extraer contenido de producción (curl)
   ↓
3. Identificar y subir imágenes a S3
   ↓
4. Extraer metadatos y SEO
   ↓
5. Generar UUID único y crear estructura básica
   ↓
6. Convertir contenido a formato Bard
   ↓
7. Procesar elementos especiales (CTAs, videos, quotes, Key Takeaways)
   ↓
8. Verificar y formatear todos los links
   ↓
9. Agregar routing y redirects
   ↓
10. Ejecutar checklist crítico completo
   ↓
✅ Migración completa
```

---

## 🚨 Errores Comunes y Soluciones

### Error: "Invalid content, text values must be strings"
**Causa:** Hay `text: null` en el contenido Bard
**Solución:** Eliminar todos los nodos con `text: null` o convertirlos a strings vacíos

### Error: "Duplicate key detected"
**Causa:** Indentación incorrecta en YAML
**Solución:** Verificar que la indentación sea consistente (2 espacios)

### Error: Imagen no aparece
**Causa:** La imagen no está en S3 o no está registrada como asset
**Solución:** Subir la imagen a S3 y crear el asset en Statamic

### Error: Links faltantes
**Causa:** No se extrajeron todos los links del contenido
**Solución:** Comparar producción con artículo migrado y agregar links faltantes

### Error: Contenido inventado
**Causa:** Se agregó contenido que no existe en producción
**Solución:** Eliminar contenido inventado y usar solo contenido de producción

---

## 📚 Referencias

- `CRITICAL-CHECKLIST.md` - Checklist obligatorio
- `README-IMAGES.md` - Procesamiento de imágenes
- `README-LINKS.md` - Manejo de links
- `README-SEO.md` - Campos SEO
- `README-VIDEOS.md` - Videos de Wistia
- `README-ROUTING.md` - Routing y redirects
- `SCRIPTS-REFERENCE.md` - Referencia de scripts

---

**Última actualización:** Diciembre 2024
