# Guía de Procesamiento de Imágenes en Migraciones

## ⚠️ REGLA CRÍTICA: Procesamiento de Imágenes es OBLIGATORIO

**Todas las migraciones de artículos DEBEN incluir el procesamiento de imágenes.** Esto no es opcional. Nunca omitas este paso.

## ¿Por qué es obligatorio?

1. **Consistencia:** Todas las imágenes deben estar en S3 con rutas consistentes
2. **Rendimiento:** Las imágenes desde S3 se cargan más rápido
3. **Mantenimiento:** Facilita la gestión y actualización de imágenes
4. **Completitud:** Un artículo sin imágenes procesadas está incompleto

## Proceso Obligatorio de Imágenes

### Paso 1: Descargar y Subir Imágenes a S3

**SIEMPRE** ejecuta este script después de crear el archivo del artículo:

```bash
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/[slug] \
  [slug]
```

Este script:
- Descarga las imágenes del artículo original
- Las sube directamente a S3
- Genera un mapeo de URLs originales → rutas S3
- No guarda imágenes localmente (solo temporalmente)

### Paso 2: Actualizar el Artículo con Rutas de Imágenes

Después de ejecutar el script, DEBES actualizar el artículo:

#### Imagen Destacada (Featured Image)

Actualiza el campo `featured_image` en el frontmatter:

```yaml
featured_image: articles/featured/[slug].webp
```

Ejemplo:
```yaml
featured_image: articles/featured/can-a-minor-own-a-business.webp
```

#### Imágenes del Contenido (Content Images)

Agrega bloques `article_image` en `main_blocks` donde corresponda:

```yaml
main_blocks:
  # ... otros bloques ...
  -
    id: [unique-id]
    version: article_image_1
    image: articles/main-content/[slug]-[description].webp
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
    [slug].webp          # Imagen destacada
  main-content/
    [slug]-[desc].webp   # Imágenes del contenido
```

Ejemplos:
- `articles/featured/can-a-minor-own-a-business.webp`
- `articles/main-content/can-a-minor-own-a-business-map.webp`

## Errores Comunes

### ❌ Error: Olvidar procesar imágenes
**Solución:** Siempre ejecuta `download-and-upload-images-to-s3.php` como parte del proceso de migración.

### ❌ Error: Usar URLs originales en lugar de rutas S3
**Solución:** Usa siempre rutas relativas como `articles/featured/[slug].webp`, nunca URLs completas.

### ❌ Error: No agregar bloques `article_image` para imágenes del contenido
**Solución:** Cada imagen del contenido debe tener su propio bloque `article_image` en `main_blocks`.

### ❌ Error: Dejar `featured_image` vacío
**Solución:** Si el artículo tiene una imagen destacada, siempre debe estar configurada en `featured_image`.

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
