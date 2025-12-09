# 🤖 Instrucciones para Migrar Artículos con Cursor AI

Este documento contiene las instrucciones exactas que debes darle a tu agente de Cursor AI para migrar artículos correctamente.

## 📋 Instrucciones Iniciales para el Agente

Copia y pega estas instrucciones al inicio de cada sesión de migración con Cursor:

```
Eres un asistente especializado en migrar artículos de producción a Statamic CMS.

ANTES de empezar cualquier migración, DEBES leer estos documentos en orden:
1. articles-migration/CRITICAL-CHECKLIST.md - ⚠️ OBLIGATORIO
2. articles-migration/QUICK-START.md - Guía rápida
3. articles-migration/README.md - Documentación general

REGLAS CRÍTICAS QUE NUNCA DEBES OLVIDAR:
- ⚠️ NUNCA inventar contenido - TODO debe ser exacto de producción
- ⚠️ NUNCA copiar UUIDs de otros artículos - SIEMPRE generar uno nuevo único
- ⚠️ SIEMPRE subir TODAS las imágenes a S3 (featured + content images)
- ⚠️ SIEMPRE verificar TODOS los links del contenido principal
- ⚠️ SIEMPRE usar comillas dobles (") para strings en YAML
- ⚠️ SIEMPRE configurar hold=true y published=true
- ⚠️ SIEMPRE agregar routing y redirects al final
- ⚠️ SIEMPRE usar article_key_takeaways para "Key Takeaways:" al final
- ⚠️ SIEMPRE usar quote_box para quotes con style="--quote-box-color:var(--primary-600)"

Cuando migres un artículo, sigue este proceso paso a paso y verifica cada punto antes de continuar.
```

## 🎯 Instrucciones para Migrar un Artículo Específico

Cuando tengas un artículo específico para migrar, usa este formato:

```
Migra el artículo [URL] a la categoría "[categoría]"

Ejemplo:
Migra https://bizee.com/articles/example-article a categoría "legal"

PROCESO OBLIGATORIO:
1. Primero lee articles-migration/CRITICAL-CHECKLIST.md completo
2. Extrae el contenido completo de producción usando curl
3. Identifica TODAS las imágenes (featured + content) y súbelas a S3
4. Crea el archivo markdown con estructura correcta
5. Verifica TODOS los links del contenido principal
6. Convierte CTAs a article_button en posiciones correctas
7. Configura campos SEO exactos de producción
8. Agrega routing y redirects
9. Verifica el checklist crítico completo antes de terminar

IMPORTANTE:
- El UUID debe ser único (generar nuevo, nunca copiar)
- Las imágenes deben estar en S3, no locales
- Todos los links del contenido deben estar incluidos
- El contenido debe ser EXACTO de producción, nunca inventado
- hold=true y published=true deben estar presentes
```

## 📝 Checklist de Verificación para el Agente

Después de que el agente complete la migración, pídele que verifique:

```
Verifica que la migración esté completa usando el checklist crítico:

1. ✅ UUID único (no duplicado de otro artículo)
2. ✅ Featured image subida a S3 en articles/featured/
3. ✅ TODAS las content images subidas a S3 en articles/main-content/
4. ✅ TODOS los links del contenido principal incluidos en formato Bard
5. ✅ CTAs migrados como article_button en posiciones correctas
6. ✅ Contenido exacto de producción (nada inventado)
7. ✅ hold=true y published=true presentes
8. ✅ Campos SEO completos (seo_custom_meta_title y seo_custom_meta_description de producción)
9. ✅ Routing agregado en released-articles.php
10. ✅ Redirect agregado en redirects.php
11. ✅ Si hay "Key Takeaways:", está en after_blocks usando article_key_takeaways
12. ✅ Si hay quotes con style="--quote-box-color:var(--primary-600)", están como quote_box
13. ✅ Todos los strings usan comillas dobles (")

Si falta algo, corrígelo inmediatamente.
```

## 🔍 Instrucciones para Verificar Links

Cuando necesites verificar links, pídele al agente:

```
Verifica que TODOS los links del contenido principal estén incluidos:

1. Extrae todos los links de producción usando curl
2. Filtra SOLO los links del contenido principal (excluir header, footer, sidebar, featured articles, podcast)
3. Compara uno por uno con el artículo migrado
4. Si falta algún link, agrégalo inmediatamente en formato Bard correcto
5. Verifica que links externos tengan rel: "noopener noreferrer" y target: _blank
6. Verifica que links internos tengan rel: null, target: null, title: null

Esta verificación es OBLIGATORIA y debe hacerse al final de cada migración.
```

## 🖼️ Instrucciones para Imágenes

Cuando necesites manejar imágenes, pídele al agente:

```
Procesa TODAS las imágenes del artículo:

1. Identifica la featured image (hero) y súbela a articles/featured/[nombre-descriptivo].webp
2. Identifica TODAS las imágenes del contenido y súbelas a articles/main-content/[nombre-descriptivo].webp
3. Usa upload-images-via-statamic.php o descarga manualmente y sube a S3
4. Verifica que todas las imágenes aparezcan como bloques article_image en el artículo
5. NUNCA dejes imágenes locales - SIEMPRE deben estar en S3

⚠️ CRÍTICO: No solo la featured image - TODAS las imágenes del contenido deben estar subidas.
```

## 🎬 Instrucciones para Videos de Wistia

Cuando haya videos, pídele al agente:

```
Migra todos los videos de Wistia del contenido:

1. Busca todos los videos de Wistia en el contenido de producción
2. Extrae el VIDEO_ID de cada video
3. Crea bloques video con formato:
   - video_url: 'https://incfile.wistia.com/medias/[VIDEO_ID]'
   - show_video_object: false
   - type: video
   - enabled: true
4. Coloca cada video en la posición correcta donde aparece en producción

⚠️ IMPORTANTE: Usa el formato incfile.wistia.com/medias/, no el formato de embed.
```

## 📊 Instrucciones para Tablas

Cuando haya tablas, pídele al agente:

```
Migra todas las tablas usando el formato info_table:

1. Lee articles-migration/README-TABLES.md para el formato correcto
2. Convierte cada tabla HTML a formato info_table
3. Incluye headers y rows correctamente
4. Coloca cada tabla en la posición correcta donde aparece en producción
```

## 🔗 Instrucciones para Routing y Redirects

Al final de cada migración, pídele al agente:

```
Agrega routing y redirects para el artículo migrado:

1. Verifica si la ruta ya existe en app/Routing/migration/released-articles.php
2. Si no existe, agrega '/articles/{slug_category}/{slug}' al array
3. Verifica si el redirect ya existe en app/Routing/redirects.php
4. Si no existe, agrega '/articles/{old-slug}' => '/articles/{slug_category}/{slug}'

⚠️ ESTE PASO ES OBLIGATORIO y debe hacerse al final de cada migración.
```

## ⚠️ Instrucciones de Emergencia

Si el agente olvida algo crítico, recuérdale:

```
⚠️ RECUERDA: [Punto crítico olvidado]

Ejemplos:
- "⚠️ RECUERDA: Las imágenes deben estar en S3, no locales"
- "⚠️ RECUERDA: Todos los links del contenido deben estar incluidos"
- "⚠️ RECUERDA: El UUID debe ser único, nunca copiado"
- "⚠️ RECUERDA: hold=true y published=true deben estar presentes"
- "⚠️ RECUERDA: El contenido debe ser exacto de producción, nada inventado"
```

## 📚 Documentos de Referencia

Cuando el agente necesite información específica, dirígelo a:

- **Estructura:** `articles-migration/README-STRUCTURE.md`
- **Formato:** `articles-migration/README-FORMATTING.md`
- **Imágenes:** `articles-migration/README-IMAGES.md`
- **Links:** `articles-migration/README-LINKS.md`
- **Videos:** `articles-migration/README-VIDEOS.md`
- **Tablas:** `articles-migration/README-TABLES.md`
- **SEO:** `articles-migration/README-SEO.md`
- **Routing:** `articles-migration/README-ROUTING.md`

## 🎓 Ejemplo Completo de Conversación

```
Usuario: Migra https://bizee.com/articles/example-article a categoría "legal"

Agente: [Lee CRITICAL-CHECKLIST.md y QUICK-START.md]
        [Extrae contenido de producción]
        [Identifica y sube imágenes a S3]
        [Crea archivo markdown]
        [Verifica links]
        [Configura SEO]
        [Agrega routing y redirects]
        [Verifica checklist completo]

Usuario: Verifica que todos los links del contenido estén incluidos

Agente: [Extrae links de producción]
        [Compara con artículo migrado]
        [Agrega links faltantes]
        [Verifica formato Bard correcto]
```

## ✅ Checklist Final para el Usuario

Antes de considerar la migración completa, verifica manualmente:

- [ ] El artículo aparece correctamente en Statamic CP
- [ ] Todas las imágenes se ven correctamente
- [ ] Todos los links funcionan
- [ ] Los CTAs están en las posiciones correctas
- [ ] El contenido coincide exactamente con producción
- [ ] El routing funciona (puedes acceder a /articles/{categoria}/{slug})
- [ ] El redirect funciona (el URL viejo redirige al nuevo)

---

**Nota:** Estas instrucciones están diseñadas para trabajar con el agente de Cursor AI. Si el agente olvida algún punto crítico, recuérdaselo usando las "Instrucciones de Emergencia" arriba.
