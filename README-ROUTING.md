# Guía de Routing Obligatorio en Migraciones de Artículos

## ⚠️ REGLA CRÍTICA: Routing Es OBLIGATORIO

**Todas las migraciones de artículos DEBEN incluir las rutas en los archivos de routing.** Esto no es opcional. Nunca omitas el routing.

## ¿Por qué es obligatorio?

1. **Accesibilidad:** Sin las rutas, el artículo no será accesible en producción
2. **Redirects:** Los redirects aseguran que los enlaces antiguos sigan funcionando
3. **SEO:** Los redirects preservan el SEO de URLs antiguas
4. **Consistencia:** Todos los artículos migrados deben seguir el mismo patrón de routing

## Archivos de Routing

### 1. `app/Routing/migration/released-articles.php`

Este archivo contiene la lista de artículos que están "released" (publicados/disponibles). Cada artículo migrado debe agregarse aquí.

**Formato de la ruta:**
```php
'/articles/{slug_category}/{slug}',
```

**Ejemplo:**
```php
'/articles/legal/use-virtual-office-address-as-legal-business-address',
```

### 2. `app/Routing/redirects.php`

Este archivo contiene los redirects de URLs antiguas a las nuevas rutas. Esto asegura que los enlaces antiguos sigan funcionando.

**Formato del redirect:**
```php
'/articles/{old-slug}' => '/articles/{slug_category}/{slug}',
```

**Ejemplo:**
```php
'/articles/use-virtual-office-address-as-legal-business-address' => '/articles/legal/use-virtual-office-address-as-legal-business-address',
```

## ⚠️ Proceso Obligatorio de Routing

**Este proceso DEBE realizarse al final de cada migración, como parte del checklist principal.**

### Paso 1: Verificar si la Ruta Ya Existe

**⚠️ OBLIGATORIO:** Antes de agregar una ruta, verifica si ya existe:

1. **En `released-articles.php`:**
   - Busca el slug del artículo en el archivo
   - Si ya existe, NO lo agregues de nuevo

2. **En `redirects.php`:**
   - Busca el slug original del artículo en el archivo
   - Si ya existe, NO lo agregues de nuevo

### Paso 2: Agregar Ruta en `released-articles.php`

Si la ruta no existe, agrega la nueva ruta al final del array, antes del cierre `];`:

```php
return [
    // ... otras rutas ...
    '/articles/legal/use-virtual-office-address-as-legal-business-address',
];
```

**Ubicación:** Agrega la ruta en orden alfabético o al final del array, antes del cierre `];`.

### Paso 3: Agregar Redirect en `redirects.php`

Si el redirect no existe, agrega el redirect al final del array, antes del cierre `];`:

```php
return [
    // ... otros redirects ...
    '/articles/use-virtual-office-address-as-legal-business-address' => '/articles/legal/use-virtual-office-address-as-legal-business-address',
];
```

**Ubicación:** Agrega el redirect al final del array, antes del cierre `];`.

### Paso 4: Verificar Formato

Asegúrate de que:
- La ruta en `released-articles.php` sigue el formato `/articles/{slug_category}/{slug}`
- El redirect en `redirects.php` sigue el formato `/articles/{old-slug}` => `/articles/{slug_category}/{slug}`
- Ambos archivos tienen comas correctas y sintaxis PHP válida

## ⚠️ CHECKLIST OBLIGATORIO DE ROUTING

**Esta verificación DEBE realizarse al final de CADA migración, antes de considerar el artículo completo.**

Antes de considerar una migración completa, verifica:

- [ ] ⚠️ **OBLIGATORIO:** ¿Verifiqué si la ruta ya existe en `released-articles.php`? (Buscar por slug)
- [ ] ⚠️ **OBLIGATORIO:** ¿Verifiqué si el redirect ya existe en `redirects.php`? (Buscar por slug original)
- [ ] ⚠️ **OBLIGATORIO:** ¿Agregué la ruta en `released-articles.php` si no existía? (Formato: `/articles/{slug_category}/{slug}`)
- [ ] ⚠️ **OBLIGATORIO:** ¿Agregué el redirect en `redirects.php` si no existía? (Formato: `/articles/{old-slug}` => `/articles/{slug_category}/{slug}`)
- [ ] ¿El formato de la ruta es correcto? (Debe incluir la categoría y el slug)
- [ ] ¿El formato del redirect es correcto? (Debe mapear el slug antiguo al nuevo)
- [ ] ¿La sintaxis PHP es válida? (Comas correctas, cierre de array correcto)

**⚠️ IMPORTANTE:** Esta verificación NO es opcional. Es parte del checklist principal y DEBE realizarse al final de cada migración.

## Ejemplos Completos

### Ejemplo 1: Artículo en Categoría "legal"

**Slug del artículo:** `use-virtual-office-address-as-legal-business-address`
**Categoría:** `legal`

**En `released-articles.php`:**
```php
'/articles/legal/use-virtual-office-address-as-legal-business-address',
```

**En `redirects.php`:**
```php
'/articles/use-virtual-office-address-as-legal-business-address' => '/articles/legal/use-virtual-office-address-as-legal-business-address',
```

### Ejemplo 2: Artículo en Categoría "strategies"

**Slug del artículo:** `bizee-premium-package`
**Categoría:** `strategies`

**En `released-articles.php`:**
```php
'/articles/strategies/bizee-premium-package',
```

**En `redirects.php`:**
```php
'/articles/bizee-premium-package' => '/articles/strategies/bizee-premium-package',
```

## Errores Comunes

### ❌ Error: Agregar rutas duplicadas
**Solución:** Siempre verifica si la ruta ya existe antes de agregarla. Usa `grep` para buscar el slug en el archivo.

### ❌ Error: Formato incorrecto de ruta
**Solución:** La ruta debe seguir el formato `/articles/{slug_category}/{slug}`. Asegúrate de incluir la categoría correcta.

### ❌ Error: Redirect incorrecto
**Solución:** El redirect debe mapear el slug antiguo (sin categoría) al nuevo slug (con categoría). Verifica que ambos slugs sean correctos.

### ❌ Error: Sintaxis PHP incorrecta
**Solución:** Asegúrate de que las comas estén correctas y que el array se cierre correctamente con `];`.

### ❌ Error: Omitir el routing completamente
**Solución:** El routing es OBLIGATORIO. Siempre agrega las rutas al final de cada migración, como parte del checklist principal.

## Comandos Útiles

### Verificar si una ruta existe

```bash
# Buscar en released-articles.php
grep "use-virtual-office-address-as-legal-business-address" app/Routing/migration/released-articles.php

# Buscar en redirects.php
grep "use-virtual-office-address-as-legal-business-address" app/Routing/redirects.php
```

### Verificar sintaxis PHP

```bash
# Verificar sintaxis de released-articles.php
php -l app/Routing/migration/released-articles.php

# Verificar sintaxis de redirects.php
php -l app/Routing/redirects.php
```

## Referencias

- Ver `README.md` para el proceso completo de migración
- Ver `QUICK-START.md` para el checklist principal
- Ver `SCRIPTS-REFERENCE.md` para detalles de los scripts
