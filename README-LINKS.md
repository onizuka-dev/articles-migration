# Guía de Links en Migraciones de Artículos

## ⚠️ REGLA CRÍTICA: Todos los Links Deben Estar Incluidos

**Todas las migraciones de artículos DEBEN incluir todos los links del contenido original.** Esto no es opcional. Nunca omitas links del contenido original.

**⚠️ IMPORTANTE:** Solo se deben verificar links que están dentro del **contenido principal del artículo**. Se deben **excluir** links de:
- Header/Navegación
- Footer
- Sección de "Featured Articles" (artículos relacionados)
- Sidebar
- Sección de Podcast
- Autor bio
- Botones de compartir en redes sociales
- Cualquier otro elemento del layout

Solo los links que aparecen en el contenido migrado del artículo deben ser verificados.

**⚠️ VERIFICACIÓN OBLIGATORIA:** Al final de CADA migración, DEBES revisar el contenido original y comparar con el artículo migrado para asegurar que TODOS los links del contenido estén presentes. Esta verificación es parte del checklist principal y NO puede omitirse.

## ¿Por qué es obligatorio?

1. **Completitud:** Un artículo sin todos sus links está incompleto
2. **SEO:** Los links internos mejoran el SEO y la navegación
3. **Experiencia de usuario:** Los links proporcionan contexto y recursos adicionales
4. **Consistencia:** Todos los artículos deben mantener la misma estructura de links

## ⚠️ Proceso Obligatorio de Verificación de Links

**Este proceso DEBE realizarse al final de cada migración, como parte del checklist principal.**

### Paso 1: Identificar Todos los Links del Contenido Original

**⚠️ OBLIGATORIO:** Antes de completar la migración, identifica TODOS los links en el contenido original:

- Links en el texto principal
- Links en listas
- Links en headings (si los hay)
- Links en citas o bloques especiales
- Links en botones (estos se manejan por separado)

### Paso 2: Migrar Links en Formato Bard

Todos los links en bloques `rich_text` deben usar el formato Bard:

```yaml
content:
  -
    type: text
    text: 'Texto antes del link '
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: 'https://example.com'
          rel: null  # o 'noopener noreferrer' para externos
          target: null  # o '_blank' para externos
          title: null
    text: 'Texto del link'
  -
    type: text
    text: ' texto después del link.'
```

### Paso 3: ⚠️ Verificación Final Obligatoria de Links

**⚠️ ESTE PASO ES OBLIGATORIO Y DEBE REALIZARSE AL FINAL DE CADA MIGRACIÓN:**

**⚠️ CRÍTICO:** Esta verificación NO puede omitirse. Es parte del checklist principal y DEBE realizarse para CADA migración.

**Proceso paso a paso:**

1. **Abre la página de producción en el navegador** - Visita la URL completa del artículo en producción
2. **Identifica TODOS los links visibles en el contenido principal** - Revisa todo el contenido (no incluyas navegación, footer, etc.)
3. **Crea una lista completa de todos los links encontrados** - Anota el texto del link y su URL
4. **Abre el artículo migrado** - Abre el archivo `.md` del artículo migrado
5. **Compara uno por uno** - Revisa cada link de tu lista y verifica que esté presente en el artículo migrado
6. **Verifica el formato** - Asegúrate de que cada link use formato Bard con `marks` y `attrs`
7. **Verifica las URLs** - Compara que las URLs coincidan exactamente con las de producción
8. **Si falta algún link, agrégalo inmediatamente** - No completes la migración hasta que todos estén presentes

**⚠️ NUNCA omitas este paso. Es parte del checklist principal de migración y es OBLIGATORIO.**

## ⚠️ CHECKLIST OBLIGATORIO DE LINKS

**Esta verificación DEBE realizarse al final de CADA migración, antes de considerar el artículo completo.**

Antes de considerar una migración completa, verifica:

- [ ] ⚠️ **OBLIGATORIO:** ¿Identifiqué TODOS los links del contenido original? (Compara el HTML de producción con el artículo migrado)
- [ ] ⚠️ **OBLIGATORIO:** ¿Todos los links están incluidos en el artículo migrado? (Ningún link puede faltar)
- [ ] ⚠️ **OBLIGATORIO:** ¿Cada link usa el formato Bard con `marks` y `attrs`? (Nunca dejar links como texto plano)
- [ ] ¿Los links externos tienen `rel: 'noopener noreferrer'` y `target: '_blank'`?
- [ ] ¿Los links internos tienen `rel: null` y `target: null`?
- [ ] ¿El texto del link está en su propio nodo `text`?
- [ ] ¿El texto antes y después del link está en nodos `text` separados?
- [ ] ⚠️ **OBLIGATORIO:** ¿Las URLs de los links coinciden exactamente con las de producción? (Verifica que no haya URLs incorrectas)

**⚠️ IMPORTANTE:** Esta verificación NO es opcional. Es parte del checklist principal y DEBE realizarse al final de cada migración.

## Tipos de Links

### Links Internos (bizee.com)

```yaml
marks:
  -
    type: link
    attrs:
      href: 'https://bizee.com/path'
      rel: null
      target: null
      title: null
```

### Links Externos

```yaml
marks:
  -
    type: link
    attrs:
      href: 'https://external-site.com'
      rel: 'noopener noreferrer'
      target: '_blank'
      title: null
```

## Errores Comunes

### ❌ Error: Omitir links del contenido original
**Solución:** Siempre compara el contenido original con el migrado para asegurar que todos los links estén presentes.

### ❌ Error: Links como texto plano en lugar de formato Bard
**Solución:** Todos los links deben usar el formato Bard con `marks` y `attrs`. Nunca dejes links como texto plano.

### ❌ Error: Links externos sin `rel` y `target` correctos
**Solución:** Los links externos siempre deben tener `rel: 'noopener noreferrer'` y `target: '_blank'` por seguridad.

### ❌ Error: Links internos con `target: '_blank'`
**Solución:** Los links internos deben tener `target: null` para abrirse en la misma pestaña.

### ❌ Error: Texto del link mezclado con texto normal
**Solución:** El texto del link debe estar en su propio nodo `text` separado del texto antes y después.

## Ejemplo Completo

**Contenido Original:**
```
If you need help, check out our [Business Guide](https://bizee.com/business-guide) or visit [external resource](https://example.com).
```

**Formato Bard Correcto:**
```yaml
content:
  -
    type: text
    text: 'If you need help, check out our '
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: 'https://bizee.com/business-guide'
          rel: null
          target: null
          title: null
    text: 'Business Guide'
  -
    type: text
    text: ' or visit '
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: 'https://example.com'
          rel: 'noopener noreferrer'
          target: '_blank'
          title: null
    text: 'external resource'
  -
    type: text
    text: '.'
```

## ⚠️ Proceso de Verificación Obligatorio

**⚠️ CRÍTICO:** Este proceso DEBE realizarse al final de cada migración, como parte del checklist principal. **NO puede omitirse ni saltarse.**

### Método 1: Comparación Manual (Recomendado)

**⚠️ ESTE MÉTODO ES OBLIGATORIO Y DEBE REALIZARSE PARA CADA MIGRACIÓN:**

1. **Abre el contenido original en el navegador** - Visita la URL completa de producción (ej: `https://bizee.com/articles/[slug]`)
2. **Identifica todos los links visibles** - Revisa TODO el contenido principal (no incluyas navegación, footer, sidebar, etc.)
3. **Crea una lista completa de todos los links encontrados** - Anota el texto del link y su URL exacta
4. **Abre el artículo migrado** - Abre el archivo `.md` del artículo migrado
5. **Compara uno por uno** - Revisa cada link de tu lista y verifica que esté presente en el artículo migrado
6. **Verifica el formato** - Asegúrate de que cada link use formato Bard con `marks` y `attrs`
7. **Verifica las URLs** - Compara que las URLs coincidan exactamente con las de producción
8. **Si falta algún link, agrégalo inmediatamente** - No completes la migración hasta que todos estén presentes

**⚠️ Este paso es OBLIGATORIO y forma parte del checklist principal de migración. NO puede omitirse.**

### Método 2: Búsqueda en el Código

```bash
# Buscar todos los links en el contenido original (HTML)
grep -o 'href="[^"]*"' contenido-original.html

# Buscar todos los links en el artículo migrado
grep -A 5 'type: link' content/collections/articles/[archivo].md
```

## Links en Botones

Los links en botones se manejan de forma diferente. Los botones usan el campo `url` directamente:

```yaml
type: article_button
url: 'https://bizee.com/path'
```

Estos NO necesitan formato Bard, pero también deben estar incluidos si existen en el contenido original.

## Referencias

- Ver `README-FORMATTING.md` para detalles del formato Bard de links
- Ver `README.md` para el proceso completo de migración
- Ver `README-STRUCTURE.md` para estructura de bloques
