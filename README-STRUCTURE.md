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

### 2. Combinar Rich Text Consecutivos ⚠️ REGLA CRÍTICA

**Regla:** Si hay varios bloques `rich_text` consecutivos (sin otro componente en medio), **DEBEN** combinarse en un solo bloque `rich_text`. Esta es una regla obligatoria que debe aplicarse siempre.

**⚠️ IMPORTANTE:** Esta regla debe aplicarse automáticamente durante la migración. Nunca dejes bloques `rich_text` consecutivos separados a menos que haya un componente diferente (botón, imagen, etc.) entre ellos.

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
- `quote_box` - ⚠️ **IMPORTANTE:** Cajas de cita (ver sección 5 para guía completa)
- `article_key_takeaways` - Puntos clave
- `bordered_container` - Contenedores con borde
- `info_table` - ⚠️ **IMPORTANTE:** Tablas de información (ver `README-TABLES.md` para guía completa)
- `video` - Videos

**Nota sobre tablas:** Cuando encuentres tablas en el contenido original, **SIEMPRE** debes convertirlas al bloque `info_table` en lugar de dejarlas como texto plano. Ver `README-TABLES.md` para instrucciones detalladas.

## Checklist de Estructura

Antes de completar una migración, verifica:

- [ ] ¿Solo el primer párrafo está en `intro`?
- [ ] ¿El resto del contenido está en `main_blocks`?
- [ ] ⚠️ **¿Los bloques `rich_text` consecutivos están combinados?** (CRÍTICO: Verificar que no haya bloques `rich_text` seguidos sin componente intermedio)
- [ ] ¿Los bloques `rich_text` separados por otros componentes están separados?
- [ ] ¿Hay exactamente 1 salto de línea (`hardBreak`) entre párrafos, headings y lists?
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

### 4. After Blocks - Key Takeaways ⚠️ REGLA CRÍTICA

**Regla:** Cuando un artículo contiene una sección "Key Takeaways:" al final, **DEBE** migrarse usando el fieldset `article_key_takeaways` en `after_blocks` (NO en `main_blocks`).

**⚠️ IMPORTANTE:**
- **NUNCA** incluir "Key Takeaways:" como parte del contenido en `main_blocks`
- **SIEMPRE** usar el fieldset `article_key_takeaways` en `after_blocks`
- El contenido puede ser una `bulletList` o párrafos con bullets (formato `•`)
- Generar un UUID único para el bloque `id`

**Estructura Requerida:**
```yaml
after_blocks:
  -
    id: [UUID único generado]
    version: article_key_takeaways_1
    heading: 'Key Takeaways'
    article_key_takeaways_version: rich_text_1
    article_key_takeaways_content:
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
                    text: 'Primer takeaway...'
          -
            type: listItem
            content:
              -
                type: paragraph
                content:
                  -
                    type: text
                    text: 'Segundo takeaway...'
    type: article_key_takeaways
    enabled: true
```

**Ejemplo Incorrecto (Key Takeaways en main_blocks):**
```yaml
main_blocks:
  -
    id: main1
    version: rich_text_1
    content:
      -
        type: heading
        attrs:
          level: 3
        content:
          -
            type: text
            text: 'Key Takeaways:'
      -
        type: bulletList
        content:
          # ... takeaways aquí
    type: rich_text
    enabled: true
```

**Ejemplo Correcto (Key Takeaways en after_blocks):**
```yaml
main_blocks:
  -
    id: main1
    version: rich_text_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Contenido del artículo...'
    type: rich_text
    enabled: true
after_blocks:
  -
    id: takeaways1
    version: article_key_takeaways_1
    heading: 'Key Takeaways'
    article_key_takeaways_version: rich_text_1
    article_key_takeaways_content:
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
                    text: 'Primer takeaway...'
    type: article_key_takeaways
    enabled: true
```

**Nota:** El fieldset `article_key_takeaways` está ubicado en `resources/fieldsets/article_key_takeaways.yaml` y debe usarse siempre que aparezca "Key Takeaways:" en el contenido del artículo.

### 5. Quote Box - Quotes con Estilo Especial ⚠️ REGLA CRÍTICA

**Regla:** Cuando detectes un quote en el contenido con `style="--quote-box-color:var(--primary-600)"`, **DEBE** migrarse usando el fieldset `quote_box` en `main_blocks` (NO como párrafo normal en `rich_text`).

**⚠️ IMPORTANTE:**
- **NUNCA** dejar quotes como párrafos normales en bloques `rich_text`
- **SIEMPRE** usar el fieldset `quote_box` cuando detectes el estilo `--quote-box-color:var(--primary-600)`
- Los quotes deben estar en la posición correcta donde aparecen en producción
- Generar un UUID único para el bloque `id`

**Estructura Requerida:**
```yaml
main_blocks:
  -
    id: [UUID único generado]
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

**Ejemplo Incorrecto (Quote como párrafo normal):**
```yaml
main_blocks:
  -
    id: main1
    version: rich_text_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Texto normal del artículo...'
      -
        type: paragraph
        content:
          -
            type: text
            text: 'By starting or investing in a business...'  # ❌ Quote como párrafo normal
    type: rich_text
    enabled: true
```

**Ejemplo Correcto (Quote como bloque quote_box):**
```yaml
main_blocks:
  -
    id: main1
    version: rich_text_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Texto normal del artículo...'
    type: rich_text
    enabled: true
  -
    id: quote1
    version: quote_box_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'By starting or investing in a business in these high-growth sectors, you can take advantage of substantial growth potential and generate long-term returns.'
    type: quote_box
    enabled: true
  -
    id: main2
    version: rich_text_1
    content:
      -
        type: paragraph
        content:
          -
            type: text
            text: 'Continuación del contenido...'
    type: rich_text
    enabled: true
```

**Nota:** El fieldset `quote_box` está ubicado en `resources/fieldsets/quote_box.yaml` y debe usarse siempre que detectes un quote con el estilo `--quote-box-color:var(--primary-600)` en el contenido HTML de producción.
