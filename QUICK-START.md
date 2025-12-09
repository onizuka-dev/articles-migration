# 🚀 Guía Rápida de Migración de Artículos

**Este es el entry point principal para migrar artículos.** Úsalo como referencia rápida y punto de partida.

## ⚠️ CHECKLIST CRÍTICO - LEE PRIMERO

**ANTES de empezar cualquier migración, revisa el checklist crítico:**
- 📋 **[`CRITICAL-CHECKLIST.md`](./CRITICAL-CHECKLIST.md)** - ⚠️ **OBLIGATORIO LEER** - Puntos críticos que NO pueden olvidarse

Este documento contiene los 7 puntos críticos que DEBES verificar en CADA migración:
1. ⚠️ Imágenes del contenido (obligatorio subir todas)
2. ⚠️ Verificación de links (obligatorio verificar todos)
3. ⚠️ CTAs (article_button) posicionados correctamente
4. ⚠️ NO inventar contenido (siempre exacto de producción)
5. ⚠️ Status: hold=true, published=true
6. ⚠️ Campos SEO completos
7. ⚠️ Key Takeaways usar fieldset article_key_takeaways

## ⚡ Proceso Rápido (3 Pasos)

### 1. Ejecutar Script de Migración Completa (Recomendado)

```bash
cd articles-migration
php migrate-complete.php \
  https://bizee.com/articles/[slug] \
  [slug] \
  content/collections/articles/[fecha].[slug].md
```

Este script automatiza TODO:
- ✅ Descarga el contenido HTML
- ✅ Procesa y sube imágenes a S3 usando Statamic API (genera thumbnails automáticamente)
- ✅ Genera nombres descriptivos para imágenes hero basados en el contenido (ej: "woman-working-laptop")
- ✅ Genera estructura básica del artículo
- ✅ Aplica reglas de formato automáticamente

### 2. Revisar y Completar el Artículo

**⚠️ CRITICAL - NO INVENTAR CONTENIDO:** **NEVER invent, create, or modify content that does not exist in the production page.** All content (headings, paragraphs, lists, descriptions, etc.) MUST be extracted exactly as it appears in production. **If you cannot find specific content in production, DO NOT create it.** This is a migration, not content creation. Always verify that:
- All headings match production exactly
- All paragraphs match production exactly
- All numbered/bulleted items match production exactly
- All descriptions and explanations match production exactly
- If production has 40 items, the migrated article must have exactly 40 items (not 18, not 39, not 41)
- If production says "X", the migrated article must say "X" (not "Y" or "similar to X")

**This rule is CRITICAL and NON-NEGOTIABLE. Violating this rule will result in incorrect content being published.**

El script genera una estructura base. Debes:
- Revisar el contenido generado
- ⚠️ **OBLIGATORIO:** Verificar que todos los links del contenido original estén incluidos en formato Bard (ver paso 3)
- Verificar que todos los videos de Wistia estén incluidos como bloques `video`
- Asegurar que las imágenes estén correctamente referenciadas
- Completar cualquier contenido faltante (pero SOLO si existe en producción - nunca inventar)

### 2.5. ⚠️ **NUEVO:** Ejecutar Verificación Automática (Recomendado)

**ANTES** de revisar manualmente, ejecuta el script de verificación automática:

```bash
php verify-migration.php \
  content/collections/articles/[fecha].[slug].md \
  https://bizee.com/articles/[slug]
```

Este script verifica automáticamente:
- ✅ UUID único (no duplicado)
- ✅ Campos SEO presentes y correctos
- ✅ Imágenes en S3 (no locales)
- ✅ Links completos (comparación con producción)
- ✅ Videos de Wistia incluidos
- ✅ CTAs (article_button) incluidos
- ✅ Tablas migradas como info_table
- ✅ Routing en released-articles.php y redirects.php
- ✅ Comillas dobles en YAML
- ✅ Estructura de bloques (type y enabled)
- ✅ Bloques rich_text combinados
- ✅ Estructura de intro correcta

**El script mostrará errores y warnings que debes corregir antes de continuar.**

### 3. Verificar Checklist Final

Antes de considerar la migración completa:

- [ ] ⚠️ **CRÍTICO:** ¿El UUID del artículo es único? (NUNCA copiar el UUID de otro artículo. Si dos artículos comparten el mismo UUID, Statamic solo reconocerá uno y el otro no aparecerá en el dashboard)
- [ ] ⚠️ **IMPORTANTE:** ¿El artículo tiene subtitle en producción? Si aparece un texto justo después del título en la página de producción, DEBE estar incluido como campo `subtitle` en el frontmatter del artículo migrado.
- [ ] ⚠️ **OBLIGATORIO:** ¿TODAS las imágenes del contenido están subidas a S3 y referenciadas correctamente?
  - **PASO 1:** Verifica que la featured image esté en `articles/featured/`
  - **PASO 2:** Verifica que TODAS las imágenes del contenido estén en `articles/main-content/`
  - **PASO 3:** Verifica que todas aparezcan como bloques `article_image` en el artículo
  - **⚠️ CRÍTICO:** NO solo la featured image - TODAS las imágenes del contenido deben estar subidas
- [ ] ⚠️ **OBLIGATORIO:** ¿Revisaste que TODOS los links del contenido original están incluidos en formato Bard?
  - **PASO 1:** Abre la página de producción en el navegador
  - **PASO 2:** Identifica TODOS los links visibles **SOLO en el contenido principal** (excluir header, footer, featured articles, sidebar, podcast, etc.)
  - **PASO 3:** Compara uno por uno con el artículo migrado
  - **PASO 4:** Si falta algún link del contenido, agrégalo inmediatamente
  - **⚠️ ESTA VERIFICACIÓN ES OBLIGATORIA Y DEBE HACERSE AL FINAL DE CADA MIGRACIÓN - NO PUEDE OMITIRSE**
  - **⚠️ IMPORTANTE:** Solo verificar links del contenido del artículo, NO del layout
- [ ] ⚠️ **OBLIGATORIO:** ¿Todos los CTAs (article_button) del contenido están incluidos y en la posición correcta?
  - **PASO 1:** Revisa el contenido original y busca todos los CTAs (banners con botones como "Form Your LLC", "PROTECT YOUR BUSINESS", etc.)
  - **PASO 2:** Verifica que cada CTA esté migrado como bloque `article_button` en `main_blocks`
  - **PASO 3:** Verifica que estén en la posición correcta (donde aparecen en producción)
  - **PASO 4:** Excluir CTAs del layout (header, footer, sidebar)
  - **⚠️ ESTA VERIFICACIÓN ES OBLIGATORIA Y DEBE HACERSE AL FINAL DE CADA MIGRACIÓN**
- [ ] ¿Todos los videos de Wistia están incluidos como bloques `video` en `main_blocks`?
- [ ] ¿Los campos SEO están agregados? (`seo_title`, `seo_meta_description`, `seo_custom_meta_title`, `seo_custom_meta_description`, etc.)
- [ ] ¿El `seo_custom_meta_title` es el título exacto del tag `<title>` de producción?
- [ ] ¿El `seo_custom_meta_description` es la meta description exacta de producción?
- [ ] ⚠️ **OBLIGATORIO:** ¿Agregaste las rutas en `app/Routing/migration/released-articles.php` y `app/Routing/redirects.php`?
  - **PASO 1:** Verifica si la ruta ya existe en `released-articles.php` (buscar por slug)
  - **PASO 2:** Si no existe, agrega `/articles/{slug_category}/{slug}` a `released-articles.php`
  - **PASO 3:** Verifica si el redirect ya existe en `redirects.php` (buscar por slug original)
  - **PASO 4:** Si no existe, agrega `/articles/{old-slug}` => `/articles/{slug_category}/{slug}` a `redirects.php`
  - **⚠️ ESTE PASO ES OBLIGATORIO Y DEBE HACERSE AL FINAL DE CADA MIGRACIÓN**
- [ ] ⚠️ **OBLIGATORIO:** ¿El artículo tiene `hold: true` y `published: true`? (AMBOS deben estar presentes, NUNCA usar `published: false` para artículos migrados)
- [ ] ⚠️ **OBLIGATORIO:** ¿Si el artículo tiene "Key Takeaways:" al final, está migrado usando el fieldset `article_key_takeaways` en `after_blocks`? (NUNCA incluir "Key Takeaways:" como parte del contenido en `main_blocks`)
- [ ] ⚠️ **OBLIGATORIO:** ¿Si hay quotes con `style="--quote-box-color:var(--primary-600)"`, están migrados como bloques `quote_box`? (NUNCA dejar quotes como párrafos normales en `rich_text`)
- [ ] ⚠️ **CRÍTICO:** ¿TODOS los strings usan comillas dobles (`"`)? (NUNCA usar comillas simples `'`; escapar comillas dobles internas con `\"`; ⚠️ **NO escapar comillas simples cuando usas comillas dobles como wrapper**)
- [ ] ¿Los saltos de línea son correctos? (exactamente 1 `hardBreak` entre párrafos, headings y listas)
- [ ] ¿Los bloques `rich_text` consecutivos están combinados?
- [ ] ¿Solo el primer párrafo está en `intro`?
- [ ] ⚠️ **IMPORTANTE:** ¿Todas las tablas están convertidas al formato `info_table`? (ver `README-TABLES.md`)

## 📚 Documentación Completa

### Documentos Principales

1. **`README.md`** - Guía general de migración
2. **`QUICK-START.md`** (este archivo) - Entry point rápido
3. **`SCRIPTS-REFERENCE.md`** - Referencia de todos los scripts

### Guías Específicas

- **`README-STRUCTURE.md`** - Reglas de estructura de contenido
- **`README-LISTS.md`** - Manejo de listas
- **`README-FORMATTING.md`** - Reglas de formato (quotes, links, line breaks)
- **`README-TABLES.md`** - ⚠️ **IMPORTANTE:** Migración de tablas usando bloques `info_table`
- **`README-IMAGES.md`** - ⚠️ **CRÍTICO:** Procesamiento obligatorio de imágenes
- **`README-LINKS.md`** - ⚠️ **CRÍTICO:** Verificación obligatoria de links
- **`README-VIDEOS.md`** - ⚠️ **CRÍTICO:** Migración obligatoria de videos con Wistia
- **`README-SEO.md`** - ⚠️ **CRÍTICO:** Campos SEO obligatorios en todos los artículos migrados

## ⚠️ Reglas Críticas (NUNCA Olvidar)

### 0. UUID: CRÍTICO - Debe Ser Único

**NUNCA** copies el UUID de otro artículo. **SIEMPRE** genera un UUID único para cada artículo:

- Cada artículo DEBE tener su propio UUID v4 único
- Si dos artículos comparten el mismo UUID, Statamic solo reconocerá uno de ellos
- El artículo con UUID duplicado NO aparecerá en el dashboard
- Siempre genera un nuevo UUID usando `generateUUID()` o una herramienta generadora de UUID

**❌ INCORRECTO:** Copiar `id: a47e5476-277e-a3aa-277e-d97433dd42a5` de otro artículo
**✅ CORRECTO:** Generar un nuevo UUID único para cada artículo

### 1. Imágenes: OBLIGATORIO en S3

**NUNCA** dejes imágenes localmente. **SIEMPRE** deben estar en S3:

```bash
# SIEMPRE ejecutar después de crear el artículo
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]
```

**Rutas correctas en el artículo:**
- Featured: `articles/featured/[slug].webp`
- Content: `articles/main-content/[slug]-[desc].webp`

**❌ INCORRECTO:** Guardar imágenes en `public/assets/` localmente
**✅ CORRECTO:** Subir a S3 y usar rutas `articles/featured/` o `articles/main-content/`

### 2. Videos: OBLIGATORIO Incluir Todos

**SIEMPRE** verificar que todos los videos de Wistia del contenido original estén incluidos como bloques `video`:

```yaml
main_blocks:
  -
    id: [unique-id]
    version: article_video_1
    video_url: 'https://incfile.wistia.com/medias/[VIDEO_ID]'
    show_video_object: false
    type: video
    enabled: true
```

**⚠️ IMPORTANTE:** Usa el formato `https://incfile.wistia.com/medias/[VIDEO_ID]`, no el formato de embed.

### 3. Links: OBLIGATORIO Verificar Todos

**SIEMPRE** verificar que todos los links del contenido original estén incluidos en formato Bard:

```yaml
# Formato correcto
content:
  -
    type: text
    text: 'Texto antes '
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: 'https://example.com'
          rel: 'noopener noreferrer'  # Para externos
          target: '_blank'             # Para externos
          title: null
    text: 'Texto del link'
  -
    type: text
    text: ' texto después.'
```

### 4. Formato: Reglas Estrictas

- **Quotes:**
  - Dobles (`"`) para texto con apostrofes (escapar comillas dobles internas con `\"`)
  - ⚠️ **CRÍTICO:** Cuando usas comillas dobles como wrapper, **NO escapar comillas simples** dentro del texto - dejarlas tal cual
  - Simples (`'`) para texto sin apostrofes
  - Si hay comillas dobles pero NO apostrofes, preferir comillas simples para el string externo
- **Line breaks:** Exactamente 1 `hardBreak` entre párrafos, headings y listas
- **Lists:** Todas como `bulletList` (incluso las numeradas)
- **Rich text blocks:** Combinar consecutivos (a menos que haya otro componente entre ellos)

## 🔄 Flujo de Trabajo Recomendado

```
1. Ejecutar migrate-complete.php
   ↓
2. Revisar estructura generada
   ↓
3. Verificar imágenes en S3 (si migrate-complete.php no las procesó)
   ↓
4. Verificar todos los videos de Wistia están incluidos como bloques `video`
   ↓
5. Verificar todos los links están en formato Bard
   ↓
6. Aplicar formato correcto (quotes, line breaks)
   ↓
7. Combinar bloques rich_text consecutivos
   ↓
8. Checklist final
   ↓
9. ✅ Migración completa
```

## 🆘 Si Algo Sale Mal

### Problema: Imágenes no están en S3

**Solución:**
```bash
# Ejecutar manualmente
php download-and-upload-images-to-s3.php
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]

# Verificar que las rutas en el artículo sean correctas:
# - articles/featured/[slug].webp
# - articles/main-content/[slug]-[desc].webp
```

### Problema: Videos faltantes o mal formateados

**Solución:**
1. Revisar contenido original en el navegador
2. Buscar todos los videos de Wistia (buscar por `incfile.wistia.com` o IDs de video)
3. Verificar que cada video esté en el artículo migrado como bloque `video`
4. Asegurar formato correcto: `https://incfile.wistia.com/medias/[VIDEO_ID]` (ver `README-VIDEOS.md`)

### Problema: Links faltantes o mal formateados

**Solución:**
1. Revisar contenido original en el navegador
2. Listar todos los links encontrados
3. Verificar que cada link esté en el artículo migrado
4. Asegurar formato Bard correcto (ver `README-LINKS.md`)

### Problema: Formato incorrecto

**Solución:**
- Revisar `README-FORMATTING.md` para reglas específicas
- Usar `formatting-helper.php` para funciones de ayuda

## 📝 Notas Importantes

- **NUNCA** guardes imágenes localmente en `public/assets/` de forma permanente
- **SIEMPRE** usa rutas de S3: `articles/featured/` o `articles/main-content/`
- **SIEMPRE** verifica que todos los links estén incluidos
- **SIEMPRE** verifica que todos los videos de Wistia estén incluidos
- **SIEMPRE** aplica las reglas de formato antes de completar

## 🔗 Referencias Rápidas

- **Scripts:** Ver `SCRIPTS-REFERENCE.md`
- **Estructura:** Ver `README-STRUCTURE.md`
- **Formato:** Ver `README-FORMATTING.md`
- **Imágenes:** Ver `README-IMAGES.md` ⚠️
- **Links:** Ver `README-LINKS.md` ⚠️
- **Videos:** Ver `README-VIDEOS.md` ⚠️

---

**Última actualización:** 2024-11-29
**Mantener actualizado:** Este documento debe reflejar el proceso actual de migración
