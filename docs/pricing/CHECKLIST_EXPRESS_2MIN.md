# ⚡ CHECKLIST EXPRESS: DETECTAR PRODUCTOS MAL CATEGORIZADOS (2 MINUTOS)

**Objetivo:** Encontrar productos alcohólicos que NO recibirán ajuste de precio.  
**Tiempo:** 2 minutos  
**Requisito:** Acceso a MySQL (phpMyAdmin, Workbench, o similar)

---

## 🎯 QUERY ULTRA-RÁPIDA (COPIAR Y PEGAR)

```sql
-- ⚡ COPIAR Y EJECUTAR EN MYSQL
SELECT 
    p.id,
    p.nombre,
    COALESCE(p.categoria, '❌ SIN CATEGORÍA') as categoria_actual,
    p.precio_venta,
    p.stock,
    -- ¿Necesita corrección?
    CASE 
        WHEN p.categoria LIKE '%alcoh%' THEN '✅ OK'
        WHEN p.categoria IS NULL OR p.categoria = '' THEN '🔴 SIN CATEGORÍA'
        ELSE '🟡 CATEGORÍA INCORRECTA'
    END as estado
FROM productos p
WHERE p.nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra|aperol|champagne|espumante|ipa|lager|malbec|cabernet)'
  AND p.activo = 1
  AND p.stock > 0
ORDER BY estado DESC, p.nombre
LIMIT 100;
```

---

## 📊 INTERPRETAR RESULTADOS

### ✅ Verde: "OK"
```
ID: 123 | Cerveza Quilmes 1L | Bebidas Alcohólicas | $1,200 | ✅ OK
```
**Acción:** Ninguna. Ya está bien.

---

### 🔴 Rojo: "SIN CATEGORÍA"
```
ID: 456 | Fernet Branca 750ml | ❌ SIN CATEGORÍA | $3,500 | 🔴
```
**Acción:** URGENTE. Asignar "Bebidas Alcohólicas".

---

### 🟡 Amarillo: "CATEGORÍA INCORRECTA"
```
ID: 789 | Vodka Smirnoff 1L | Bebidas | $4,200 | 🟡
```
**Acción:** Cambiar de "Bebidas" → "Bebidas Alcohólicas".

---

## 📋 EXPORTAR RESULTADOS

### Opción 1: Copiar a Excel
1. Seleccionar resultados
2. Copiar (Ctrl+C)
3. Pegar en Excel
4. Filtrar por columna "estado" ≠ "OK"
5. Usar columna "id" para buscar en UI

### Opción 2: Exportar CSV
1. Click en "Exportar" (phpMyAdmin)
2. Formato: CSV
3. Guardar como `productos_a_corregir.csv`

---

## 🎯 CONTEO RÁPIDO (OPCIONAL)

```sql
-- ¿Cuántos productos necesitan corrección?
SELECT 
    CASE 
        WHEN categoria LIKE '%alcoh%' THEN '✅ OK'
        WHEN categoria IS NULL OR categoria = '' THEN '🔴 SIN CATEGORÍA'
        ELSE '🟡 INCORRECTA'
    END as estado,
    COUNT(*) as cantidad
FROM productos
WHERE nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra|aperol)'
  AND activo = 1
GROUP BY estado;
```

**Output esperado:**
```
✅ OK              : 45 productos
🟡 INCORRECTA      : 12 productos  ← CORREGIR
🔴 SIN CATEGORÍA   : 3 productos   ← URGENTE
```

---

## ⏱️ TIEMPO ESTIMADO POR CORRECCIONES

| Cantidad a corregir | Tiempo estimado |
|---------------------|-----------------|
| 1-10 productos      | 10-20 minutos   |
| 11-30 productos     | 30-45 minutos   |
| 31-50 productos     | 1-1.5 horas     |
| 50+ productos       | Considerar UPDATE masivo |

---

## 🚀 PRÓXIMO PASO

**Ir a:** `MINI_GUIA_NORMALIZACION_UI.md` para ver cómo corregir desde la UI.

---

✅ **Checklist completado → Lista de IDs generada → Listo para normalizar**

