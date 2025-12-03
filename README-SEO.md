# Guía de Campos SEO en Migraciones

## ⚠️ REGLA CRÍTICA: Campos SEO son OBLIGATORIOS

**Todos los artículos migrados DEBEN incluir los campos SEO en el frontmatter.** Esto no es opcional. Nunca omitas este paso.

## ¿Por qué es obligatorio?

1. **SEO:** Los campos SEO son críticos para el posicionamiento en buscadores
2. **Consistencia:** Todos los artículos deben tener la misma estructura SEO
3. **Mantenimiento:** Facilita la gestión y actualización de SEO
4. **Completitud:** Un artículo sin campos SEO está incompleto

## Campos SEO Requeridos

Todos los artículos migrados DEBEN incluir estos campos en el frontmatter:

```yaml
seo_title: custom
seo_meta_description: custom
seo_custom_meta_title: "{title}"
seo_custom_meta_description: "{description}"
seo_canonical: none
seo_og_description: general
seo_og_title: title
seo_tw_title: title
seo_tw_description: general
seo_og_image:
  - articles/featured/[slug].webp
```

### Descripción de Campos

- **`seo_title`:** Siempre `custom`
- **`seo_meta_description`:** Siempre `custom`
- **`seo_custom_meta_title`:** El título exacto del tag `<title>` de la página en producción
- **`seo_custom_meta_description`:** La meta description exacta del tag `<meta name="description">` de la página en producción
- **`seo_canonical`:** Siempre `none`
- **`seo_og_description`:** Siempre `general`
- **`seo_og_title`:** Siempre `title`
- **`seo_tw_title`:** Siempre `title`
- **`seo_tw_description`:** Siempre `general`
- **`seo_og_image`:** **SIEMPRE** debe ser la misma imagen que `featured_image` (la imagen hero). Formato: array con un elemento que contiene la ruta de la imagen featured.

## Proceso Obligatorio de SEO

### Paso 1: Obtener Título y Meta Description de Producción

**SIEMPRE** debes obtener estos valores de la página en producción:

```bash
# Obtener título
curl -s "https://bizee.com/articles/[slug]" | grep -oE '<title>[^<]*</title>'

# Obtener meta description
curl -s "https://bizee.com/articles/[slug]" | grep -oE '<meta[^>]*name=["\']description["\'][^>]*>'
```

O usando Python para extraer correctamente:

```python
import re
import html

# Obtener título
title_match = re.search(r'<title>(.*?)</title>', html_content, re.DOTALL)
if title_match:
    title = html.unescape(title_match.group(1))

# Obtener meta description
meta_match = re.search(r'<meta[^>]*name=[\"\']description[\"\'][^>]*content=[\"\']([^\"\']*)[\"\']', html_content)
if meta_match:
    description = meta_match.group(1)
```

### Paso 2: Agregar Campos SEO al Frontmatter

Después de obtener los valores, agrega los campos SEO al frontmatter del artículo:

```yaml
---
id: [uuid]
published: false
blueprint: article
title: "Article Title"
# ... otros campos ...
slug_category: [category]
hold: true
seo_title: custom
seo_meta_description: custom
seo_custom_meta_title: "Título exacto de producción"
seo_custom_meta_description: "Meta description exacta de producción"
seo_canonical: none
seo_og_description: general
seo_og_title: title
seo_tw_title: title
seo_tw_description: general
seo_og_image:
  - articles/featured/[slug].webp
intro:
  # ...
---
```

## Checklist de SEO

Antes de considerar una migración completa, verifica:

- [ ] ¿Obtuve el título exacto del tag `<title>` de la página en producción?
- [ ] ¿Obtuve la meta description exacta del tag `<meta name="description">` de la página en producción?
- [ ] ¿Agregué todos los campos SEO al frontmatter?
- [ ] ¿Los valores de `seo_custom_meta_title` y `seo_custom_meta_description` son exactos (no placeholders)?
- [ ] ¿Los demás campos SEO tienen los valores correctos (`custom`, `none`, `general`, `title`)?
- [ ] ⚠️ **CRÍTICO:** ¿El campo `seo_og_image` contiene la misma imagen que `featured_image`? (debe ser exactamente la misma ruta)

## Errores Comunes

### ❌ Error: Olvidar agregar campos SEO
**Solución:** Siempre agrega los campos SEO como parte del proceso de migración. Este paso es **OBLIGATORIO**.

### ❌ Error: Usar placeholders en lugar de valores reales
**Solución:** **NUNCA** uses `{title}` o `{description}` como valores. **SIEMPRE** obtén los valores reales de la página en producción.

**Ejemplo incorrecto:**
```yaml
seo_custom_meta_title: "{title}"  # ❌ INCORRECTO
seo_custom_meta_description: "{description}"  # ❌ INCORRECTO
```

**Ejemplo correcto:**
```yaml
seo_custom_meta_title: "Here Is What Is Included In Bizee's Platinum Package"  # ✅ CORRECTO
seo_custom_meta_description: "The Platinum Package starts at a low service fee of $299 + state fees, which includes all required paperwork to properly form your new business entity."  # ✅ CORRECTO
```

### ❌ Error: Usar valores incorrectos para campos fijos
**Solución:** Los campos `seo_title`, `seo_meta_description`, `seo_canonical`, `seo_og_description`, `seo_og_title`, `seo_tw_title`, `seo_tw_description` siempre tienen los mismos valores. No los cambies.

## Integración con Scripts de Migración

### Usando `migrate-complete.php` (Recomendado)

El script `migrate-complete.php` debería incluir automáticamente la obtención y agregado de campos SEO. Asegúrate de que el script:

1. Obtenga el HTML de la página en producción
2. Extraiga el título y meta description
3. Agregue los campos SEO al frontmatter generado

### Migración Manual

Si migras manualmente, DEBES agregar los campos SEO:

```bash
# 1. Obtener título y meta description de producción
# 2. Agregar campos SEO al frontmatter
# 3. Continuar con el resto de la migración
```

## Referencias

- Ver `SCRIPTS-REFERENCE.md` para detalles de los scripts
- Ver `README.md` para el proceso completo de migración
- Ver `QUICK-START.md` para el checklist completo
