# Reglas de Formato para Artículos Migrados

Este documento describe las reglas generales de formato que deben seguirse al migrar artículos al sistema Statamic.

## 1. Uso de Comillas en Strings YAML

### Regla: Comillas Dobles para Texto con Apostrofes

**Regla General:** Si el texto contiene apostrofes (contracciones como `you'll`, `won't`, `Bizee's`, `you're`, etc.), el string debe usar **comillas dobles** (`"`). Si el texto no contiene apostrofes, se pueden usar comillas simples (`'`).

### Ejemplos

**❌ Incorrecto:**
```yaml
text: 'Here are the premium services you'll receive for business'
```

**✅ Correcto:**
```yaml
text: "Here are the premium services you'll receive for business"
```

**✅ También Correcto (sin apostrofes):**
```yaml
text: 'Selecting your business entity type'
```

### Contracciones Comunes que Requieren Comillas Dobles

- `you'll`, `you're`, `you've`
- `won't`, `don't`, `can't`, `isn't`, `aren't`
- `it's`, `that's`, `here's`, `what's`, `there's`
- `Bizee's`, `company's`, `business's`
- `we're`, `they're`, `I'm`
- Cualquier otra contracción con apostrofe

### Checklist

- [ ] ¿El texto contiene apostrofes? → Usar comillas dobles (`"`)
- [ ] ¿El texto NO contiene apostrofes? → Puedes usar comillas simples (`'`)

## 2. Formato de Links en Rich Text (Bard)

### Regla: Links Deben Estar en Formato Bard con Marks

**Regla General:** Todos los links dentro del contenido `rich_text` deben estar estructurados usando el formato Bard con `marks` y `attrs`.

### Estructura Correcta de un Link

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
          rel: null
          target: null
          title: null
    text: 'Texto del link'
  -
    type: text
    text: ' texto después del link.'
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
        text: 'If you need minimal support, then the Basic Package may be a great place to start.'
```

**✅ Correcto (link con formato Bard):**
```yaml
content:
  -
    type: paragraph
    content:
      -
        type: text
        text: 'If you need minimal support, then the '
      -
        type: text
        marks:
          -
            type: link
            attrs:
              href: 'https://bizee.com/blog/bizee-silver-package'
              rel: null
              target: null
              title: null
        text: 'Basic Package'
      -
        type: text
        text: ' may be a great place to start.'
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
  href: 'https://external-site.com'
  rel: 'noopener noreferrer'
  target: '_blank'
  title: null
```

### Links Internos

Para links internos (bizee.com), generalmente se debe usar:
```yaml
attrs:
  href: 'https://bizee.com/path'
  rel: null
  target: null
  title: null
```

### Checklist

- [ ] ¿El link está dentro de un bloque `rich_text`?
- [ ] ¿El link usa `marks` con `type: link`?
- [ ] ¿El link tiene `attrs` con `href`, `rel`, `target`, y `title`?
- [ ] ¿El texto del link está separado en su propio nodo `text`?
- [ ] ¿El texto antes y después del link está en nodos `text` separados?

## 3. Combinación de Reglas

Cuando un link contiene texto con apostrofes, asegúrate de aplicar ambas reglas:

```yaml
content:
  -
    type: text
    text: 'Click '
  -
    type: text
    marks:
      -
        type: link
        attrs:
          href: 'https://example.com'
          rel: null
          target: null
          title: null
    text: "here if you're ready"
  -
    type: text
    text: ' to continue.'
```

Nota: El texto del link (`"here if you're ready"`) usa comillas dobles porque contiene un apostrofe.

## Referencias

- Ver `README-STRUCTURE.md` para reglas de estructura de contenido
- Ver `README-LISTS.md` para reglas de manejo de listas
- Ver `README.md` para información general sobre migración
