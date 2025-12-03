# Guía de Procesamiento de Imágenes en Migraciones

## ⚠️ REGLA CRÍTICA: Procesamiento de Imágenes es OBLIGATORIO

**Todas las migraciones de artículos DEBEN incluir el procesamiento de imágenes.** Esto no es opcional. Nunca omitas este paso.

## ¿Por qué es obligatorio?

1. **Consistencia:** Todas las imágenes deben estar en S3 con rutas consistentes
2. **Rendimiento:** Las imágenes desde S3 se cargan más rápido
3. **Mantenimiento:** Facilita la gestión y actualización de imágenes
4. **Completitud:** Un artículo sin imágenes procesadas está incompleto

## Proceso Obligatorio de Imágenes

### ⚠️ REGLA CRÍTICA: Las Imágenes DEBEN Estar en S3, NO Localmente

**NUNCA** guardes imágenes localmente en `public/assets/` de forma permanente. **SIEMPRE** deben estar en S3 con rutas `articles/featured/` o `articles/main-content/`.

### Paso 1: Descargar y Subir Imágenes a S3

**SIEMPRE** ejecuta este script después de crear el archivo del artículo:

```bash
# Opción 1: Script directo a S3 (más rápido, pero sin thumbnails)
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]

# Opción 2: Script vía Statamic API (genera thumbnails automáticamente) ⭐ RECOMENDADO
php upload-images-via-statamic.php \
  https://bizee.com/articles/[slug] \
  [slug] \
  https://bizee.test/cp
```

**Script `download-and-upload-images-to-s3.php`:**
- Descarga las imágenes del artículo original
- Las sube **directamente a S3** (no las guarda localmente de forma permanente)
- Genera un mapeo de URLs originales → rutas S3
- Las imágenes solo se guardan temporalmente durante el proceso de subida
- ⚠️ **No genera thumbnails** en el CP

**Script `upload-images-via-statamic.php` (⭐ RECOMENDADO):**
- Descarga las imágenes del artículo original
- **Genera nombres descriptivos** basados en el contenido de la imagen (ej: "woman-working-laptop" en lugar de solo usar el slug)
- Las sube a S3 usando Statamic API
- **Genera thumbnails automáticamente** en el CP
- Las imágenes aparecen inmediatamente en el dashboard de Statamic

**❌ INCORRECTO:**
```bash
# NO hacer esto manualmente
curl -o public/assets/articles/featured/image.webp https://...
# Esto guarda la imagen localmente, lo cual NO queremos
```

**✅ CORRECTO:**
```bash
# Usar el script que sube directamente a S3
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]
```

### Paso 2: Actualizar el Artículo con Rutas de Imágenes de S3

Después de ejecutar el script, DEBES actualizar el artículo con las rutas de S3:

#### ⚠️ IMPORTANTE: Usar Rutas de S3, NO URLs Locales

**Las rutas deben ser relativas a S3:**
- `articles/featured/[slug].webp` ✅
- `articles/main-content/[slug]-[description].webp` ✅

**NO usar:**
- URLs completas de S3 ❌
- Rutas locales como `public/assets/...` ❌
- URLs del sitio original ❌

#### Imagen Destacada (Featured Image)

Actualiza el campo `featured_image` en el frontmatter con la ruta de S3:
```yaml
featured_image: articles/featured/[descriptive-name].webp
```

**Nombres Descriptivos:** El script `upload-images-via-statamic.php` genera nombres descriptivos basados en el contenido de la imagen. Por ejemplo:
- `woman-working-laptop.webp` (en lugar de solo `add-members-llc.webp`)
- `afro-woman-with-glasses-smiling.webp`
- `business-partnership.webp`

Ejemplos:
```yaml
featured_image: articles/featured/woman-working-laptop.webp
featured_image: articles/featured/business-partnership.webp
featured_image: articles/featured/afro-woman-with-glasses-smiling.webp
```

**NOTA:** Esta ruta apunta directamente a S3. El sistema Statamic resolverá automáticamente la URL completa de S3.

#### Imágenes del Contenido (Content Images)

Agrega bloques `article_image` en `main_blocks` donde corresponda:

```yaml
main_blocks:
  # ... otros bloques ...
  -
    id: [unique-id]
    version: article_image_1
    image: articles/main-content/[slug]-[description].webp  # Ruta de S3
    type: article_image
    enabled: true
  # ... otros bloques ...
```

Ejemplo:
```yaml
  -
    id: canminor-map
    version: article_image_1
    image: articles/main-content/can-a-minor-own-a-business-map.webp
    type: article_image
    enabled: true
```

**NOTA:** La ruta `articles/main-content/...` apunta directamente a S3. No uses rutas locales.

### Paso 3: Verificar Imágenes en S3

El script `download-and-upload-images-to-s3.php` verifica automáticamente que las imágenes estén en S3. Si alguna imagen falla, el script te lo indicará.

## Checklist de Imágenes

Antes de considerar una migración completa, verifica:

- [ ] ¿Ejecuté `download-and-upload-images-to-s3.php`?
- [ ] ¿El campo `featured_image` tiene la ruta correcta de S3?
- [ ] ¿Todas las imágenes del contenido tienen bloques `article_image`?
- [ ] ¿Las rutas de las imágenes siguen el formato `articles/featured/` o `articles/main-content/`?
- [ ] ¿Verifiqué que las imágenes existen en S3?

## Estructura de Rutas en S3

Las imágenes deben seguir esta estructura:

```
articles/
  featured/
    [descriptive-name].webp          # Imagen destacada (nombres descriptivos)
  main-content/
    [slug]-[desc].webp   # Imágenes del contenido
```

Ejemplos:
- `articles/featured/woman-working-laptop.webp` (descriptivo)
- `articles/featured/business-partnership.webp` (descriptivo)
- `articles/featured/afro-woman-with-glasses-smiling.webp` (descriptivo)
- `articles/main-content/can-a-minor-own-a-business-map.webp`

## Errores Comunes

### ❌ Error: Guardar imágenes localmente en lugar de S3
**Solución:** **NUNCA** guardes imágenes en `public/assets/` localmente. **SIEMPRE** usa el script `download-and-upload-images-to-s3.php` que las sube directamente a S3.

**Ejemplo de error:**
```bash
# ❌ INCORRECTO - No hacer esto
curl -o public/assets/articles/featured/image.webp https://...
```

**Solución correcta:**
```bash
# ✅ CORRECTO
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]
```

### ❌ Error: Olvidar procesar imágenes
**Solución:** Siempre ejecuta `download-and-upload-images-to-s3.php` como parte del proceso de migración. Este paso es **OBLIGATORIO**.

### ❌ Error: Usar URLs originales o rutas locales en lugar de rutas S3
**Solución:** Usa siempre rutas relativas de S3 como `articles/featured/[slug].webp` o `articles/main-content/[slug]-[desc].webp`. Nunca uses URLs completas ni rutas locales.

**Ejemplos incorrectos:**
```yaml
# ❌ INCORRECTO
featured_image: https://s3.amazonaws.com/...
featured_image: public/assets/articles/featured/...
featured_image: /assets/articles/featured/...
```

**Ejemplo correcto:**
```yaml
# ✅ CORRECTO
featured_image: articles/featured/[slug].webp
```

### ❌ Error: No agregar bloques `article_image` para imágenes del contenido
**Solución:** Cada imagen del contenido debe tener su propio bloque `article_image` en `main_blocks` con la ruta de S3.

### ❌ Error: Dejar `featured_image` vacío
**Solución:** Si el artículo tiene una imagen destacada, siempre debe estar configurada en `featured_image` con la ruta de S3.

## Integración con Scripts de Migración

### Usando `migrate-complete.php` (Recomendado)

El script `migrate-complete.php` incluye automáticamente el procesamiento de imágenes:

```bash
php migrate-complete.php \
  https://bizee.com/articles/[slug] \
  [slug] \
  content/collections/articles/[date].[slug].md
```

Este script ejecuta automáticamente `download-and-upload-images-to-s3.php` y actualiza las rutas.

### Migración Manual

Si migras manualmente, DEBES ejecutar el procesamiento de imágenes:

```bash
# 1. Crear el archivo del artículo (con estructura básica)
# 2. Ejecutar procesamiento de imágenes (OBLIGATORIO)
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]

# 3. Actualizar el artículo con las rutas de imágenes
# 4. Continuar con el resto de la migración
```

## Referencias

- Ver `SCRIPTS-REFERENCE.md` para detalles de los scripts
- Ver `README.md` para el proceso completo de migración
- Ver `README-STRUCTURE.md` para estructura de bloques
