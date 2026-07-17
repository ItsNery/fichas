# Optimización de Espaciado — Ficha Municipal

> **Fecha:** 2026-07-14
> **Objetivo:** Reducir el scroll vertical excesivo comprimiendo márgenes, paddings y alturas entre secciones sin sacrificar legibilidad.

---

## Archivos modificados

| Archivo | Tipo de cambio |
|---|---|
| `resources/css/estilos.css` | CSS (4 reglas) |
| `resources/views/municipios/perfil.blade.php` | Blade (4 cambios) |
| `resources/views/municipios/resumen_v3.blade.php` | Blade (4 cambios) |
| `resources/views/municipios/resumen_test.blade.php` | Blade (4 cambios) |

---

## Cambios en CSS (`estilos.css`)

### 1. `.seccion-editorial` — padding general de cada temática

```diff
 .seccion-editorial {
-    padding: 5rem 0;    /* 80px top + 80px bottom = 160px */
+    padding: 3rem 0;    /* 48px top + 48px bottom = 96px */
 }

 .seccion-editorial__divisor {
     width: 40px;
     height: 3px;
     background: var(--color2);
-    margin: 1.5rem 0;   /* 24px */
+    margin: 1.25rem 0;  /* 20px */
 }
```

**Reducción:** 64px por temática.
**Impacto:** En ficha con 15 temáticas ~960px menos de scroll.

---

### 2. `.dimension-header` — encabezado de sección en perfil

```diff
 .dimension-header {
     background: linear-gradient(90deg, var(--color1) 0%, var(--color2) 100%);
     color: white;
-    padding: 2rem;         /* 32px */
+    padding: 1.25rem;      /* 20px */
     border-radius: 15px;
-    margin-bottom: 1rem;   /* 16px */
+    margin-bottom: 0.5rem; /* 8px */
 }
```

**Reducción:** 20px por dimensión.
**Impacto:** En perfil con ~7 dimensiones ~140px.

---

### 3. `.perfil-tarjeta__body` — interior de tarjetas indicador

```diff
 .perfil-tarjeta__body {
-    padding: 1.5rem;   /* 24px */
+    padding: 1rem;     /* 16px */
 }
```

**Reducción:** 8px por tarjeta (16px vertical total).
**Impacto:** ~16 tarjetas visibles ~128px.

---

### 4. `.banner-dimension` — banner entre dimensiones (v3/test)

```diff
 .banner-dimension {
-    height: 250px;
+    height: 200px;
     ...
-    margin-top: 4rem;  /* 64px */
+    margin-top: 2rem;  /* 32px */
 }
```

**Reducción:** 82px por banner (50px altura + 32px margen).
**Impacto:** ~5 banners en ficha ~410px.

---

## Cambios en vistas Blade

### `perfil.blade.php`

| Ubicación | Antes | Después |
|---|---|---|
| Contenedor principal | `container mt-5` (48px) | `container mt-4` (24px) |
| Sección de perfil | `section-perfil mb-5 pb-5` (96px) | `section-perfil mb-4 pb-4` (48px) |
| Fila título de tarjeta | `mb-3` (16px) | `mb-2` (8px) |
| Sección similares | `similares-seccion py-5` (96px) | `similares-seccion py-4` (48px) |

**Reducción total:** ~300–400px de scroll vertical.

### `resumen_v3.blade.php`

| Ubicación | Antes | Después |
|---|---|---|
| Contenedor principal | `container py-5` (96px v) | `container py-4` (48px v) |
| Bloque dimensión | `dimension-bloque mb-5` (48px) | `dimension-bloque mb-4` (24px) |
| Sub-navegación temáticas | `sub-navegacion py-3 mb-4` (40px v) | `sub-navegacion py-2 mb-3` (24px v) |
| Bento grid (tarjetas KPI) | `row g-4 mt-5` (48px top) | `row g-4 mt-4` (24px top) |

**Reducción total:** ~200–300px por dimensión.

### `resumen_test.blade.php`

Mismos cambios que `resumen_v3`.

---

## Resumen de reducción total estimada

| Vista | Reducción vertical |
|---|---|
| `perfil` (7 dimensiones, ~16 tarjetas) | **~700–900px** |
| `resumen_v3` (5 dimensiones, ~15 temáticas) | **~1300–1600px** |
| `resumen_test` (5 dimensiones, ~15 temáticas) | **~1300–1600px** |

---

## Criterios aplicados

- No se modificaron espaciados del hero-ficha (el usuario lo excluyó explícitamente).
- No se eliminaron márgenes/paddings completamente — solo se redujeron proporcionalmente.
- Se mantuvieron las reglas de `clamp()` responsivas en títulos y textos.
- Los valores nuevos usan la escala Bootstrap existente (mt-4, py-4, mb-4) para consistencia.
- Altura de `banner-dimension` se redujo de 250px a 200px manteniendo visibilidad del título y fondo.
