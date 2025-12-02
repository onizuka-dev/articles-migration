# Reglas de Formato para Artículos Migrados

Este documento describe las reglas generales de formato que deben seguirse al migrar artículos al sistema Statamic.

## 1. Uso de Comillas en Strings YAML

### ⚠️ REGLA CRÍTICA: SIEMPRE Usar Comillas Dobles

**⚠️ REGLA CRÍTICA:** **SIEMPRE usar comillas dobles (`"`) para TODOS los valores de string en YAML. NUNCA usar comillas simples (`'`).** Esta regla es crítica para evitar problemas cuando hay apostrofes, contracciones o comillas simples dentro del texto.

**Razón:** Usar comillas simples puede causar errores de parsing cuando el texto contiene apostrofes (como `you'll`, `won't`, `Bizee's`, etc.) o comillas simples internas. Usar siempre comillas dobles elimina estos problemas.

### Regla: Escapar Comillas Dobles Dentro de Strings con Comillas Dobles

**Regla Crítica:** Si un string usa comillas dobles (`"`) y contiene comillas dobles dentro del texto (como palabras entre comillas), estas comillas internas **DEBEN** ser escapadas usando `\"`.

**Ejemplos:**

**❌ Incorrecto (comillas dobles sin escapar):**
```yaml
text: "The keyword "food" or "health food""
```

**✅ Correcto (comillas dobles escapadas):**
```yaml
text: "The keyword \"food\" or \"health food\""
```

**✅ También Correcto (usar comillas simples para el string externo):**
```yaml
text: 'The keyword "food" or "health food"'
```

**Regla Crítica: NO Escapar Comillas Simples Dentro de Strings con Comillas Dobles**

**⚠️ IMPORTANTE:** Cuando un string usa comillas dobles (`"`) como wrapper porque contiene apostrofes, **NO debes escapar las comillas simples** dentro del texto. Las comillas simples dentro del texto deben dejarse tal cual.

**Ejemplos:**

**❌ Incorrecto (escapando comillas simples innecesariamente):**
```yaml
text: 'There is no \'official\' way to have a company\'s SIC code changed'
```

**✅ Correcto (usar comillas dobles y NO escapar comillas simples):**
```yaml
text: "There is no 'official' way to have a company's SIC code changed"
```

**⚠️ REGLA CRÍTICA DE DECISIÓN:**
1. **SIEMPRE usar comillas dobles (`"`) para TODOS los strings** - sin excepciones.
2. Si el texto contiene comillas dobles dentro → escapar las comillas dobles internas con `\"`.
3. **NUNCA escapar comillas simples** dentro del texto cuando usas comillas dobles como wrapper - dejarlas tal cual.

**⚠️ IMPORTANTE:** Los scripts de migración (`formatting-helper.php`) aplican estas reglas automáticamente. Si migras manualmente, asegúrate de seguir estas reglas o usar la función `formatTextForYaml()`.

### Ejemplos

**❌ Incorrecto (usando comillas simples):**
```yaml
text: 'Here are the premium services you'll receive for business'
```

**❌ También Incorrecto (comillas simples incluso sin apostrofes):**
```yaml
text: 'Selecting your business entity type'
```

**✅ Correcto (SIEMPRE usar comillas dobles):**
```yaml
text: "Here are the premium services you'll receive for business"
```

**✅ Correcto (SIEMPRE usar comillas dobles, incluso sin apostrofes):**
```yaml
text: "Selecting your business entity type"
```

### Contracciones Comunes que Requieren Comillas Dobles

- `you'll`, `you're`, `you've`
- `won't`, `don't`, `can't`, `isn't`, `aren't`
- `it's`, `that's`, `here's`, `what's`, `there's`
- `Bizee's`, `company's`, `business's`
- `we're`, `they're`, `I'm`
- Cualquier otra contracción con apostrofe

### Checklist

- [ ] ⚠️ **CRÍTICO:** ¿Estás usando comillas dobles (`"`) para TODOS los strings? (NUNCA usar comillas simples)
- [ ] ¿Verificaste que las comillas dobles dentro de strings estén escapadas con `\"`?
- [ ] ⚠️ **CRÍTICO:** ¿Verificaste que NO estés escapando comillas simples cuando usas comillas dobles como wrapper?
- [ ] ¿Todos los valores de `text:` usan comillas dobles?
- [ ] ¿Todos los valores de `href:` usan comillas dobles?
- [ ] ¿Todos los valores de `title:` usan comillas dobles?

## 2. Formato de Links en Rich Text (Bard)

### Regla: Links Deben Estar en Formato Bard con Marks ⚠️ MANDATORY

**Regla General:** Todos los links dentro del contenido `rich_text` deben estar estructurados usando el formato Bard con `marks` y `attrs`.

**⚠️ CRÍTICO:** Todos los links del contenido original DEBEN estar incluidos en el artículo migrado. **Nunca omitas links del contenido original.** Siempre verifica que todos los links estén presentes antes de completar la migración.

### Estructura Correcta de un Link

```yaml
content:
  -
    type: text
    text: "Texto antes del link "
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: "https://example.com"
          rel: null
          target: null
          title: null
    text: "Texto del link"
  -
    type: text
    text: " texto después del link."
```

### Ejemplo Completo

**❌ Incorrecto (link como texto plano):**
```yaml
content:
  -
    type: paragraph
    content:
      -
        type: text
        text: "If you need minimal support, then the Basic Package may be a great place to start."
```

**✅ Correcto (link con formato Bard y comillas dobles):**
```yaml
content:
  -
    type: paragraph
    content:
      -
        type: text
        text: "If you need minimal support, then the "
      -
        type: text
        marks:
          -
            type: link
            attrs:
              href: "https://bizee.com/blog/bizee-silver-package"
              rel: null
              target: null
              title: null
        text: "Basic Package"
      -
        type: text
        text: " may be a great place to start."
```

### Atributos del Link

- `href`: URL del link (requerido)
- `rel`: Relación del link (generalmente `null` o `'noopener noreferrer'` para links externos)
- `target`: Target del link (`null` para mismo tab, `'_blank'` para nueva pestaña)
- `title`: Título del link (generalmente `null`)

### Links Externos

Para links externos (que no sean de bizee.com), generalmente se debe usar:
```yaml
attrs:
  href: "https://external-site.com"
  rel: "noopener noreferrer"
  target: "_blank"
  title: null
```

### Links Internos

Para links internos (bizee.com), generalmente se debe usar:
```yaml
attrs:
  href: "https://bizee.com/path"
  rel: null
  target: null
  title: null
```

### Checklist

- [ ] ⚠️ **¿Todos los links del contenido original están incluidos?** (CRÍTICO: Verificar contra el contenido original)
- [ ] ¿El link está dentro de un bloque `rich_text`?
- [ ] ¿El link usa `marks` con `type: link`?
- [ ] ¿El link tiene `attrs` con `href`, `rel`, `target`, y `title`?
- [ ] ¿El texto del link está separado en su propio nodo `text`?
- [ ] ¿El texto antes y después del link está en nodos `text` separados?
- [ ] ¿Los links externos tienen `rel: 'noopener noreferrer'` y `target: '_blank'`?
- [ ] ¿Los links internos tienen `rel: null` y `target: null`?

## 3. Combinación de Reglas

Cuando un link contiene texto con apostrofes, asegúrate de aplicar ambas reglas:

```yaml
content:
  -
    type: text
    text: "Click "
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: "https://example.com"
          rel: null
          target: null
          title: null
    text: "here if you're ready"
  -
    type: text
    text: " to continue."
```

Nota: **TODOS los strings usan comillas dobles** - esta es la regla crítica para evitar problemas con apostrofes.

## 3. Saltos de Línea entre Elementos

### Regla: 1 Salto de Línea entre Párrafos, Headings y Lists

**Regla General:** Entre párrafos, headings y lists debe haber exactamente **1 salto de línea** (`hardBreak`). Esto asegura una separación visual consistente en el contenido.

### Estructura Correcta

```yaml
content:
  -
    type: heading
    attrs:
      level: 2
    content:
      -
        type: text
        text: "Título de Sección"
  -
    type: paragraph
    content:
      -
        type: hardBreak
  -
    type: paragraph
    content:
      -
        type: text
        text: "Texto del párrafo después del heading."
  -
    type: paragraph
    content:
      -
        type: hardBreak
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
                text: "Item 1"
  -
    type: paragraph
    content:
      -
        type: hardBreak
  -
    type: paragraph
    content:
      -
        type: text
        text: "Texto después de la lista."
```

### Reglas Específicas

1. **Después de un heading:** Debe haber un `hardBreak` seguido de un párrafo
2. **Entre párrafos:** Debe haber un `hardBreak` entre cada par de párrafos
3. **Antes de una lista:** Debe haber un `hardBreak` antes de la lista
4. **Después de una lista:** Debe haber un `hardBreak` después de la lista
5. **Entre listas:** Debe haber un `hardBreak` entre listas consecutivas

### Ejemplo Incorrecto (sin saltos de línea)

```yaml
content:
  -
    type: heading
    attrs:
      level: 2
    content:
      -
        type: text
        text: "Título"
  -
    type: paragraph
    content:
      -
        type: text
        text: "Texto sin salto de línea"
  -
    type: bulletList
    # ... lista sin salto antes
```

### Ejemplo Correcto (con saltos de línea)

```yaml
content:
  -
    type: heading
    attrs:
      level: 2
    content:
      -
        type: text
        text: "Título"
  -
    type: paragraph
    content:
      -
        type: hardBreak
  -
    type: paragraph
    content:
      -
        type: text
        text: "Texto con salto de línea"
  -
    type: paragraph
    content:
      -
        type: hardBreak
  -
    type: bulletList
    # ... lista con salto antes
```

### Checklist

- [ ] ¿Hay un `hardBreak` después de cada heading?
- [ ] ¿Hay un `hardBreak` entre párrafos consecutivos?
- [ ] ¿Hay un `hardBreak` antes de cada lista?
- [ ] ¿Hay un `hardBreak` después de cada lista?
- [ ] ¿Hay exactamente 1 `hardBreak` entre elementos (no más, no menos)?

## Referencias

- Ver `README-STRUCTURE.md` para reglas de estructura de contenido
- Ver `README-LISTS.md` para reglas de manejo de listas
- Ver `README-LINKS.md` para reglas obligatorias de verificación de links
- Ver `README.md` para información general sobre migración
