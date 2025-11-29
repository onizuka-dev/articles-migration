# Guía de Estructura de Artículos

## Reglas Generales de Estructura

### 1. Intro - Solo el Primer Párrafo

**Regla:** Solo el primer párrafo del contenido debe ir en `intro`. Todo lo demás va en `main_blocks`.

**Ejemplo:**

```yaml
intro:
  -
    type: paragraph
    content:
      -
        type: text
        text: 'Este es el primer párrafo del artículo.'
main_blocks:
  -
    id: block1
    version: rich_text_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Este es el segundo párrafo y todo lo demás.'
      # ... resto del contenido
```

### 2. Combinar Rich Text Consecutivos

**Regla:** Si hay varios bloques `rich_text` consecutivos (sin otro componente en medio), deben combinarse en un solo bloque `rich_text`.

**Ejemplo Incorrecto:**
```yaml
main_blocks:
  -
    id: block1
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text: 'Título 1'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido 1'
    type: rich_text
    enabled: true
  -
    id: block2
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Título 2'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido 2'
    type: rich_text
    enabled: true
```

**Ejemplo Correcto:**
```yaml
main_blocks:
  -
    id: block1
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Título 1'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido 1'
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Título 2'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido 2'
    type: rich_text
    enabled: true
```

### 3. No Combinar si Hay Otro Componente en Medio

**Regla:** Si hay un botón, imagen u otro componente entre bloques `rich_text`, NO se combinan.

**Ejemplo Correcto:**
```yaml
main_blocks:
  -
    id: block1
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Título 1'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido 1'
    type: rich_text
    enabled: true
  -
    id: button1
    version: article_button_1
    label:
      -
        type: paragraph
        attrs:
          textAlign: left
        content:
          -
            type: text
            text: 'Click aquí'
    url: 'https://example.com'
    type: article_button
    enabled: true
  -
    id: block2
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Título 2'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido 2'
    type: rich_text
    enabled: true
```

En este caso, `block1` y `block2` NO se combinan porque hay un botón (`button1`) en medio.

## Componentes que Interrumpen la Combinación

Los siguientes componentes deben mantener los bloques `rich_text` separados:

- `article_button` - Botones/CTAs
- `article_image` - Imágenes
- `quote_box` - Cajas de cita
- `article_key_takeaways` - Puntos clave
- `bordered_container` - Contenedores con borde
- `info_table` - Tablas de información
- `video` - Videos

## Checklist de Estructura

Antes de completar una migración, verifica:

- [ ] ¿Solo el primer párrafo está en `intro`?
- [ ] ¿El resto del contenido está en `main_blocks`?
- [ ] ¿Los bloques `rich_text` consecutivos están combinados?
- [ ] ¿Los bloques `rich_text` separados por otros componentes están separados?
- [ ] ¿El formato YAML es correcto?

## Ejemplo Completo

```yaml
intro:
  -
    type: paragraph
    content:
      -
        type: text
        text: 'Este es el primer párrafo del artículo.'

main_blocks:
  # Primer bloque rich_text con contenido inicial
  -
    id: intro-content
    version: rich_text_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Este es el segundo párrafo.'
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
                    text: 'Item 1'
    type: rich_text
    enabled: true

  # Botón interrumpe, así que el siguiente rich_text es separado
  -
    id: button1
    version: article_button_1
    label:
      -
        type: paragraph
        attrs:
          textAlign: left
        content:
          -
            type: text
            text: 'Click aquí'
    url: 'https://example.com'
    type: article_button
    enabled: true

  # Este rich_text está separado porque hay un botón antes
  -
    id: section1
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 2
        content:
          -
            type: text
            text: 'Sección 1'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido de la sección 1'
      # Si hubiera otro rich_text después sin componente en medio,
      # se combinaría aquí
    type: rich_text
    enabled: true
```
