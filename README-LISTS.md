# Guía para Manejar Listas en Migraciones

## Regla Importante ⚠️

**TODAS las listas deben migrarse como `bulletList`, incluso si en el HTML original son listas numeradas (`<ol>`).**

Esta es la regla del proyecto: las listas numeradas en HTML se convierten en listas con viñetas (`bulletList`) en Bard.

## Tipos de Listas en Bard

### bulletList (Lista con viñetas) ✅ USAR SIEMPRE
Se usa para **todas las listas**, independientemente de si eran numeradas o no en el HTML original.

**Formato:**
```yaml
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
              text: 'Primer elemento'
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Segundo elemento'
```

### orderedList (Lista numerada) ❌ NO USAR
**No se debe usar `orderedList` en las migraciones.** Todas las listas deben ser `bulletList`.

## Cuándo Usar bulletList

### Siempre usa `bulletList` para:
- ✅ Listas numeradas del HTML (`<ol>`)
- ✅ Listas con viñetas del HTML (`<ul>`)
- ✅ Listas que mencionan "steps", "pasos", "proceso"
- ✅ Listas con números: "1.", "2.", "3."
- ✅ Cualquier lista, sin excepción

## Procesamiento de Listas Numeradas

Cuando encuentres una lista numerada en el HTML (como "1. Item", "2. Item"), debes:

1. **Remover los números** de cada elemento
2. **Usar `bulletList`** en lugar de `orderedList`

**Ejemplo:**

**HTML original:**
```html
<ol>
  <li>1. Selecting your business entity type</li>
  <li>2. Selecting the formation state</li>
  <li>3. Selecting a package type</li>
</ol>
```

**Formato Bard correcto:**
```yaml
-
  type: bulletList  # ← Siempre bulletList, nunca orderedList
  content:
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Selecting your business entity type'  # ← Sin el "1."
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Selecting the formation state'  # ← Sin el "2."
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Selecting a package type'  # ← Sin el "3."
```

## Helper para Listas

Usa el helper `list-helper.php` para generar listas correctamente:

```php
require_once 'articles-migration/list-helper.php';

// Lista simple
$list = generateBulletList([
    'First item',
    'Second item',
    'Third item'
]);

// Lista con números (los números se removerán automáticamente)
$numberedList = generateListFromItems([
    '1. First step',
    '2. Second step',
    '3. Third step'
]);
// Resultado: bulletList con items sin números
```

## Verificación

Antes de completar una migración, verifica:
1. ✅ ¿Todas las listas usan `bulletList`? (no `orderedList`)
2. ✅ ¿Se removieron los números de las listas numeradas?
3. ✅ ¿El formato YAML es correcto?

## Ejemplo Real

**Texto original:**
> "With Bizee, you can start your business today in only three easy steps, which include:
> 1. Selecting your business entity type
> 2. Selecting the formation state
> 3. Selecting a package type that suits your business needs the best"

**Formato correcto:**
```yaml
-
  type: bulletList  # ← Siempre bulletList, aunque diga "three easy steps"
  content:
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Selecting your business entity type'  # ← Sin "1."
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Selecting the formation state'  # ← Sin "2."
    -
      type: listItem
      content:
        -
          type: paragraph
          content:
            -
              type: text
              text: 'Selecting a package type that suits your business needs the best'  # ← Sin "3."
```

## Checklist de Migración

- [ ] Identificar todas las listas en el contenido
- [ ] **Todas las listas deben usar `bulletList`** (nunca `orderedList`)
- [ ] Remover números de listas numeradas (1., 2., 3., etc.)
- [ ] Verificar que el formato YAML sea correcto
- [ ] Revisar en Statamic CMS que las listas se muestren correctamente

## Notas Importantes

- ❌ **NO uses `orderedList`** en migraciones
- ✅ **Siempre usa `bulletList`** para todas las listas
- ✅ Remueve los números de las listas numeradas antes de migrar
- ✅ Usa `list-helper.php` para generar listas correctamente
