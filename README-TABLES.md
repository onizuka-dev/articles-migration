# Guía de Migración de Tablas

## ⚠️ REGLA: Las Tablas DEBEN Usar el Bloque `info_table`

**Cuando encuentres tablas en el contenido original, SIEMPRE debes convertirlas al bloque `info_table` de Statamic.** No las dejes como texto plano con párrafos separados.

## ¿Por qué usar `info_table`?

1. **Estructura semántica:** Las tablas se renderizan correctamente como tablas HTML
2. **Mejor UX:** Los usuarios pueden escanear y comparar datos fácilmente
3. **Consistencia:** Todas las tablas tienen el mismo formato visual
4. **Mantenibilidad:** Es más fácil actualizar datos en formato estructurado

## Estructura del Bloque `info_table`

El bloque `info_table` tiene la siguiente estructura:

```yaml
-
  id: [unique-id]
  version: info_table_1
  columns:
    -
      id: [column-id-1]
      title:
        -
          type: paragraph
          content:
            -
              type: text
              marks:
                -
                  type: bold
              text: "Column Title 1"
      rows:
        -
          id: [row-id-1]
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Row 1, Column 1 content"
          type: row
          enabled: true
        -
          id: [row-id-2]
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Row 2, Column 1 content"
          type: row
          enabled: true
      type: new_set
      enabled: true
    -
      id: [column-id-2]
      title:
        -
          type: paragraph
          content:
            -
              type: text
              marks:
                -
                  type: bold
              text: "Column Title 2"
      rows:
        -
          id: [row-id-3]
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Row 1, Column 2 content"
          type: row
          enabled: true
        -
          id: [row-id-4]
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Row 2, Column 2 content"
          type: row
          enabled: true
      type: new_set
      enabled: true
  type: info_table
  enabled: true
```

## Componentes del Bloque

### Campos Requeridos

- **`id`**: UUID único para el bloque de tabla
- **`version`**: Siempre `info_table_1`
- **`columns`**: Array de columnas (mínimo 2 columnas)
- **`type`**: Siempre `info_table`
- **`enabled`**: Siempre `true`

### Estructura de Columnas

Cada columna tiene:
- **`id`**: UUID único para la columna
- **`title`**: Contenido Bard con el título de la columna (usualmente en bold)
- **`rows`**: Array de filas con el contenido de esa columna
- **`type`**: Siempre `new_set`
- **`enabled`**: Siempre `true`

### Estructura de Filas

Cada fila tiene:
- **`id`**: UUID único para la fila
- **`text`**: Contenido Bard con el texto de la celda
- **`type`**: Siempre `row`
- **`enabled`**: Siempre `true`

## Ejemplo Real: Tabla de Estadísticas

Ejemplo de una tabla de estadísticas de una ciudad:

```yaml
-
  id: table1
  version: info_table_1
  columns:
    -
      id: col1-1
      title:
        -
          type: paragraph
          content:
            -
              type: text
              marks:
                -
                  type: bold
              text: "Metric"
      rows:
        -
          id: row1-1
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Cost of Living"
          type: row
          enabled: true
        -
          id: row1-2
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Median Income of Residents"
          type: row
          enabled: true
        -
          id: row1-3
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Average Annual Revenue"
          type: row
          enabled: true
      type: new_set
      enabled: true
    -
      id: col1-2
      title:
        -
          type: paragraph
          content:
            -
              type: text
              marks:
                -
                  type: bold
              text: "Value"
      rows:
        -
          id: row1-4
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "152.1"
          type: row
          enabled: true
        -
          id: row1-5
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "$69,235"
          type: row
          enabled: true
        -
          id: row1-6
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "$95,230"
          type: row
          enabled: true
      type: new_set
      enabled: true
  type: info_table
  enabled: true
```

## Proceso de Migración de Tablas

### Paso 1: Identificar Tablas en el Contenido Original

Busca en el HTML original:
- Elementos `<table>`
- Listas de datos estructurados (métrica + valor)
- Datos organizados en filas y columnas

### Paso 2: Analizar la Estructura

Determina:
- Número de columnas
- Número de filas
- Contenido de los encabezados (títulos de columnas)
- Contenido de cada celda

### Paso 3: Generar UUIDs Únicos

**⚠️ CRÍTICO:** Cada tabla, columna y fila necesita un UUID único. **NUNCA copies UUIDs de otras tablas.**

Genera UUIDs usando:
```bash
php -r "echo bin2hex(random_bytes(8));"
```

### Paso 4: Crear el Bloque `info_table`

Estructura el bloque siguiendo el formato descrito arriba:
1. Crea el bloque con `id`, `version`, `type`, `enabled`
2. Agrega cada columna con su `title` (Bard format)
3. Agrega cada fila dentro de cada columna con su `text` (Bard format)

### Paso 5: Insertar en `main_blocks`

El bloque `info_table` debe ir en `main_blocks` como cualquier otro bloque:

```yaml
main_blocks:
  -
    id: main1
    version: rich_text_1
    content:
      # ... contenido antes de la tabla ...
    type: rich_text
    enabled: true
  -
    id: table1
    version: info_table_1
    columns:
      # ... estructura de la tabla ...
    type: info_table
    enabled: true
  -
    id: main2
    version: rich_text_1
    content:
      # ... contenido después de la tabla ...
    type: rich_text
    enabled: true
```

## Reglas Importantes

### 1. UUIDs Únicos
- **NUNCA** copies UUIDs de otras tablas
- Cada tabla, columna y fila debe tener su propio UUID único
- Si dos tablas comparten UUIDs, Statamic solo reconocerá una

### 2. Número de Filas
- **Todas las columnas deben tener el mismo número de filas**
- Si una columna tiene 5 filas, todas las demás también deben tener 5 filas

### 3. Títulos de Columnas
- Los títulos de columnas deben estar en formato Bard
- Usualmente se usa `bold` para los títulos
- Ejemplo: `"Metric"` o `"Value"`

### 4. Contenido de Celdas
- Cada celda debe estar en formato Bard
- Puede contener texto simple, links, o formato (bold, italic, etc.)
- Si hay links en las celdas, deben usar el formato Bard con `marks` y `attrs`

### 5. Separación de Bloques
- Las tablas deben ser bloques separados en `main_blocks`
- No deben estar dentro de bloques `rich_text`
- Separa el contenido antes y después de la tabla en bloques `rich_text` diferentes

## Errores Comunes

### ❌ Error: Dejar tablas como texto plano
**Solución:** **SIEMPRE** convierte las tablas al formato `info_table`. No las dejes como párrafos con texto en bold seguido de valores.

**Ejemplo incorrecto:**
```yaml
content:
  -
    type: paragraph
    content:
      -
        type: text
        marks:
          -
            type: bold
        text: "Cost of Living"
  -
    type: paragraph
    content:
      -
        type: text
        text: "152.1"
```

**Ejemplo correcto:**
```yaml
-
  id: table1
  version: info_table_1
  columns:
    -
      id: col1-1
      title:
        -
          type: paragraph
          content:
            -
              type: text
              marks:
                -
                  type: bold
                text: "Metric"
      rows:
        -
          id: row1-1
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "Cost of Living"
          type: row
          enabled: true
      type: new_set
      enabled: true
    -
      id: col1-2
      title:
        -
          type: paragraph
          content:
            -
              type: text
              marks:
                -
                  type: bold
                text: "Value"
      rows:
        -
          id: row1-2
          text:
            -
              type: paragraph
              content:
                -
                  type: text
                  text: "152.1"
          type: row
          enabled: true
      type: new_set
      enabled: true
  type: info_table
  enabled: true
```

### ❌ Error: Número diferente de filas en columnas
**Solución:** Asegúrate de que todas las columnas tengan exactamente el mismo número de filas.

### ❌ Error: Copiar UUIDs de otras tablas
**Solución:** **SIEMPRE** genera UUIDs nuevos para cada tabla, columna y fila.

### ❌ Error: Poner la tabla dentro de un bloque `rich_text`
**Solución:** Las tablas deben ser bloques separados en `main_blocks`, no contenido dentro de `rich_text`.

## Checklist de Tablas

Antes de considerar una migración completa, verifica:

- [ ] ¿Identifiqué todas las tablas en el contenido original?
- [ ] ¿Convertí todas las tablas al formato `info_table`?
- [ ] ¿Cada tabla tiene un UUID único?
- [ ] ¿Cada columna tiene un UUID único?
- [ ] ¿Cada fila tiene un UUID único?
- [ ] ¿Todas las columnas tienen el mismo número de filas?
- [ ] ¿Los títulos de columnas están en formato Bard con bold?
- [ ] ¿El contenido de las celdas está en formato Bard?
- [ ] ¿Las tablas están como bloques separados en `main_blocks`?
- [ ] ¿El contenido antes y después de cada tabla está en bloques `rich_text` separados?

## Referencias

- Ver `README-STRUCTURE.md` para estructura general de bloques
- Ver `README.md` para el proceso completo de migración
- Ver `QUICK-START.md` para el checklist completo
