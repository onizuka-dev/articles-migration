# ⚠️ CHECKLIST CRÍTICO DE MIGRACIÓN

**Este documento contiene los puntos CRÍTICOS que DEBES verificar en CADA migración de artículo.**

## 🔴 Puntos Críticos que NO Pueden Olvidarse

### 1. ⚠️ **IMÁGENES DEL CONTENIDO** - OBLIGATORIO
- ✅ **Featured image:** Debe estar subida a S3 en `articles/featured/`
- ✅ **Content images:** TODAS las imágenes del contenido deben estar subidas a S3 en `articles/main-content/`
- ✅ **NUNCA** dejar imágenes sin subir o referenciar imágenes locales
- ✅ Usar `upload-images-via-statamic.php` o `download-and-upload-images-to-s3.php`
- ✅ Verificar que todas las imágenes aparecen en el artículo como bloques `article_image`

### 2. ⚠️ **VERIFICACIÓN DE LINKS** - OBLIGATORIO
- ✅ **PASO 1:** Abre la página de producción en el navegador
- **PASO 2:** Identifica TODOS los links visibles **SOLO en el contenido principal** (excluir header, footer, featured articles, sidebar, podcast, etc.)
- **PASO 3:** Compara uno por uno con el artículo migrado
- **PASO 4:** Si falta algún link del contenido, agrégalo inmediatamente
- ✅ **ESTA VERIFICACIÓN ES OBLIGATORIA Y DEBE HACERSE AL FINAL DE CADA MIGRACIÓN**
- ✅ Verificar que los links externos tengan `rel: 'noopener noreferrer'` y `target: _blank`
- ✅ Verificar que los links internos tengan `rel: null`, `target: null`, `title: null`

### 3. ⚠️ **CTAs (article_button)** - OBLIGATORIO
- ✅ **PASO 1:** Revisa el contenido original y busca todos los CTAs (banners con botones como "Form Your LLC", "PROTECT YOUR BUSINESS", etc.)
- ✅ **PASO 2:** Verifica que cada CTA esté migrado como bloque `article_button` en `main_blocks`
- ✅ **PASO 3:** Verifica que estén en la posición correcta (donde aparecen en producción)
- ✅ **PASO 4:** Excluir CTAs del layout (header, footer, sidebar)
- ✅ Verificar estructura correcta: `label` con párrafos, `url`, `open_in_new_tab`, `type: article_button`, `enabled: true`
- ✅ **ESTA VERIFICACIÓN ES OBLIGATORIA Y DEBE HACERSE AL FINAL DE CADA MIGRACIÓN**

### 4. ⚠️ **NO INVENTAR CONTENIDO** - CRÍTICO
- ✅ **NEVER invent, create, or modify content that does not exist in the production page**
- ✅ All content (headings, paragraphs, lists, descriptions, etc.) MUST be extracted exactly as it appears in production
- ✅ If you cannot find specific content in production, DO NOT create it
- ✅ This is a migration, not content creation
- ✅ Always verify that:
  - All headings match production exactly
  - All paragraphs match production exactly
  - All numbered/bulleted items match production exactly
  - All descriptions and explanations match production exactly
  - If production has X items, the migrated article must have exactly X items (not X-1, not X+1)

### 5. ⚠️ **STATUS DEL ARTÍCULO** - OBLIGATORIO
- ✅ **hold: true** - Siempre debe estar presente
- ✅ **published: true** - Siempre debe estar presente
- ✅ **NUNCA** usar `published: false` para artículos migrados
- ✅ Ambos campos deben estar en el frontmatter

### 6. ⚠️ **CAMPOS SEO** - OBLIGATORIO
- ✅ Seguir la documentación completa en `README-SEO.md`
- ✅ Extraer de producción:
  - `seo_title`: custom
  - `seo_meta_description`: custom
  - `seo_custom_meta_title`: Del tag `<title>` de producción
  - `seo_custom_meta_description`: De la meta description de producción
  - `seo_canonical`: none (o el valor de producción)
  - `seo_og_description`: general
  - `seo_og_title`: title
  - `seo_tw_title`: title
  - `seo_tw_description`: general
- ✅ **NUNCA** omitir campos SEO

### 7. ⚠️ **KEY TAKEAWAYS** - OBLIGATORIO
- ✅ Cuando veas "Key Takeaways:" al final de un artículo, **SIEMPRE** usar el fieldset `article_key_takeaways` en `after_blocks`
- ✅ **NUNCA** incluir "Key Takeaways:" como parte del contenido en `main_blocks`
- ✅ Estructura requerida:
  ```yaml
  after_blocks:
    -
      id: [UUID único]
      version: article_key_takeaways_1
      heading: 'Key Takeaways'
      article_key_takeaways_version: rich_text_1
      article_key_takeaways_content:
        -
          type: bulletList
          content:
            # ... items aquí
      type: article_key_takeaways
      enabled: true
  ```

### 8. ⚠️ **QUOTE BOX** - OBLIGATORIO
- ✅ Cuando detectes un quote en el contenido con `style="--quote-box-color:var(--primary-600)"`, **SIEMPRE** usar el fieldset `quote_box` en `main_blocks`
- ✅ **NUNCA** dejar quotes como párrafos normales en bloques `rich_text`
- ✅ Estructura requerida:
  ```yaml
  main_blocks:
    -
      id: [UUID único]
      version: quote_box_1
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Texto del quote aquí...'
      type: quote_box
      enabled: true
  ```
- ✅ Los quotes deben estar en la posición correcta donde aparecen en producción

## ✅ Checklist Final Antes de Completar Migración

Antes de considerar la migración completa, verifica CADA punto:

- [ ] ⚠️ **CRÍTICO:** ¿El UUID del artículo es único? (NUNCA copiar UUID de otro artículo)
- [ ] ⚠️ **OBLIGATORIO:** ¿TODAS las imágenes del contenido están subidas a S3 y referenciadas correctamente?
- [ ] ⚠️ **OBLIGATORIO:** ¿Revisaste que TODOS los links del contenido original están incluidos en formato Bard?
- [ ] ⚠️ **OBLIGATORIO:** ¿Todos los CTAs (article_button) del contenido están incluidos y en la posición correcta?
- [ ] ⚠️ **CRÍTICO:** ¿El contenido es exactamente igual al de producción? (no inventado, no modificado)
- [ ] ⚠️ **OBLIGATORIO:** ¿El artículo tiene `hold: true` y `published: true`?
- [ ] ⚠️ **OBLIGATORIO:** ¿Todos los campos SEO están agregados y correctos?
- [ ] ⚠️ **OBLIGATORIO:** ¿Si hay "Key Takeaways:", está en `after_blocks` usando `article_key_takeaways`?
- [ ] ⚠️ **OBLIGATORIO:** ¿Si hay quotes con `style="--quote-box-color:var(--primary-600)"`, están migrados como bloques `quote_box`?
- [ ] ⚠️ **OBLIGATORIO:** ¿Agregaste las rutas en `released-articles.php` y `redirects.php`?
- [ ] ⚠️ **CRÍTICO:** ¿TODOS los strings usan comillas dobles (`"`)? (NUNCA usar comillas simples `'`)
- [ ] ⚠️ **OBLIGATORIO:** ¿Todos los videos de Wistia están incluidos como bloques `video`?
- [ ] ⚠️ **OBLIGATORIO:** ¿Todas las tablas están convertidas al formato `info_table`?

## 📝 Notas Importantes

- Este checklist debe revisarse **SIEMPRE** antes de completar cualquier migración
- Si falta algún punto, la migración NO está completa
- Es mejor tomar más tiempo verificando que tener que corregir después
- Cuando dudes, consulta la documentación completa en los archivos README correspondientes
