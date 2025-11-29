# 🚀 Guía Rápida de Migración de Artículos

**Este es el entry point principal para migrar artículos.** Úsalo como referencia rápida y punto de partida.

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
- ✅ Procesa y sube imágenes a S3
- ✅ Genera estructura básica del artículo
- ✅ Aplica reglas de formato automáticamente

### 2. Revisar y Completar el Artículo

El script genera una estructura base. Debes:
- Revisar el contenido generado
- Verificar que todos los links estén en formato Bard
- Asegurar que las imágenes estén correctamente referenciadas
- Completar cualquier contenido faltante

### 3. Verificar Checklist Final

Antes de considerar la migración completa:

- [ ] ¿Todas las imágenes están en S3 y referenciadas correctamente?
- [ ] ¿Todos los links del contenido original están incluidos en formato Bard?
- [ ] ¿El formato es correcto (quotes, line breaks, etc.)?
- [ ] ¿Los bloques `rich_text` consecutivos están combinados?
- [ ] ¿Solo el primer párrafo está en `intro`?

## 📚 Documentación Completa

### Documentos Principales

1. **`README.md`** - Guía general de migración
2. **`QUICK-START.md`** (este archivo) - Entry point rápido
3. **`SCRIPTS-REFERENCE.md`** - Referencia de todos los scripts

### Guías Específicas

- **`README-STRUCTURE.md`** - Reglas de estructura de contenido
- **`README-LISTS.md`** - Manejo de listas
- **`README-FORMATTING.md`** - Reglas de formato (quotes, links, line breaks)
- **`README-IMAGES.md`** - ⚠️ **CRÍTICO:** Procesamiento obligatorio de imágenes
- **`README-LINKS.md`** - ⚠️ **CRÍTICO:** Verificación obligatoria de links

## ⚠️ Reglas Críticas (NUNCA Olvidar)

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

### 2. Links: OBLIGATORIO Verificar Todos

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

### 3. Formato: Reglas Estrictas

- **Quotes:**
  - Dobles (`"`) para texto con apostrofes (escapar comillas dobles internas con `\"`)
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
4. Verificar todos los links están en formato Bard
   ↓
5. Aplicar formato correcto (quotes, line breaks)
   ↓
6. Combinar bloques rich_text consecutivos
   ↓
7. Checklist final
   ↓
8. ✅ Migración completa
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
- **SIEMPRE** aplica las reglas de formato antes de completar

## 🔗 Referencias Rápidas

- **Scripts:** Ver `SCRIPTS-REFERENCE.md`
- **Estructura:** Ver `README-STRUCTURE.md`
- **Formato:** Ver `README-FORMATTING.md`
- **Imágenes:** Ver `README-IMAGES.md` ⚠️
- **Links:** Ver `README-LINKS.md` ⚠️

---

**Última actualización:** 2024-11-29
**Mantener actualizado:** Este documento debe reflejar el proceso actual de migración
