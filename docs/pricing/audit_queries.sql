-- ================================================================
-- AUDIT QUERIES: ALCOHOLIC BEVERAGES MAPPING
-- Sistema de Precios Dinámicos - Tayrona Almacén
-- Date: 21/10/2025
-- Purpose: Read-only queries to audit product categorization
-- ================================================================

-- ================================================================
-- 1. PRODUCTOS POR CATEGORÍA (OVERVIEW)
-- ================================================================
-- Muestra distribución de productos por categoría
-- Útil para ver qué categorías existen y cuántos productos tienen

SELECT 
    COALESCE(categoria, '(sin categoría)') as categoria,
    COUNT(*) as total_productos,
    ROUND(AVG(precio_venta), 2) as precio_promedio,
    SUM(stock) as stock_total
FROM productos
WHERE activo = 1
GROUP BY categoria
ORDER BY total_productos DESC;

-- ================================================================
-- 2. PRODUCTOS ALCOHÓLICOS (HEURÍSTICA POR NOMBRE)
-- ================================================================
-- Encuentra productos que por su nombre parecen alcohólicos
-- Muestra su categoría actual para detectar inconsistencias

SELECT 
    p.id,
    p.nombre,
    p.codigo as sku,
    COALESCE(p.categoria, '(sin categoría)') as categoria,
    p.precio_venta,
    p.stock,
    -- Detectar si está en categoría alcohólica
    CASE 
        WHEN p.categoria LIKE '%alcoh%' THEN '✅ Bien categorizado'
        WHEN p.categoria IS NULL OR p.categoria = '' THEN '❌ SIN CATEGORÍA'
        ELSE '⚠️ Categoría incorrecta'
    END as estado_categoria
FROM productos p
WHERE p.nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra|aperol|champagne|espumante|ipa|lager|malbec|cabernet|syrah|pinot|chardonnay|sauvignon)'
  AND p.activo = 1
ORDER BY estado_categoria DESC, p.categoria, p.nombre
LIMIT 200;

-- ================================================================
-- 3. PRODUCTOS ALCOHÓLICOS MAL CATEGORIZADOS
-- ================================================================
-- Lista productos que por nombre son alcohólicos pero NO están
-- en categoría "Bebidas Alcohólicas" o similar

SELECT 
    p.id,
    p.nombre,
    COALESCE(p.categoria, '(sin categoría)') as categoria_actual,
    p.precio_venta,
    p.stock,
    'Bebidas Alcohólicas' as categoria_sugerida
FROM productos p
WHERE p.nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra|aperol|champagne|espumante|ipa|lager|malbec)'
  AND (p.categoria IS NULL 
       OR p.categoria = '' 
       OR p.categoria NOT LIKE '%alcoh%')
  AND p.activo = 1
ORDER BY p.stock DESC, p.nombre
LIMIT 100;

-- ================================================================
-- 4. FALSOS POSITIVOS (No alcohólicos en categoría alcohólica)
-- ================================================================
-- Lista productos EN categoría alcohólica pero que por nombre
-- NO parecen alcohólicos (posibles errores)

SELECT 
    p.id,
    p.nombre,
    p.categoria,
    p.precio_venta,
    p.stock,
    '⚠️ Revisar manualmente' as nota
FROM productos p
WHERE p.categoria LIKE '%alcoh%'
  AND p.nombre NOT REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra|aperol|champagne|espumante|ipa|lager|malbec|cabernet|alcohol|licor)'
  AND p.activo = 1
ORDER BY p.nombre
LIMIT 50;

-- ================================================================
-- 5. PRODUCTOS SIN CATEGORÍA (ALTA PRIORIDAD)
-- ================================================================
-- Productos activos sin categoría → NO recibirán ajuste de precio

SELECT 
    p.id,
    p.nombre,
    p.codigo as sku,
    p.precio_venta,
    p.stock,
    p.updated_at as ultima_actualizacion
FROM productos p
WHERE (p.categoria IS NULL OR p.categoria = '')
  AND p.activo = 1
  AND p.stock > 0
ORDER BY p.stock DESC, p.nombre
LIMIT 100;

-- ================================================================
-- 6. VARIANTES DE CATEGORÍA "BEBIDAS"
-- ================================================================
-- Detecta todas las variantes de categorías relacionadas con bebidas
-- Útil para ver si hay alternativas como "Licores", "Vinos", etc.

SELECT DISTINCT 
    categoria,
    COUNT(*) as total_productos
FROM productos
WHERE (categoria LIKE '%bebida%'
   OR categoria LIKE '%alcoh%'
   OR categoria LIKE '%licor%'
   OR categoria LIKE '%vino%'
   OR categoria LIKE '%cervez%')
  AND activo = 1
GROUP BY categoria
ORDER BY total_productos DESC;

-- ================================================================
-- 7. DISTRIBUCIÓN DE PRECIOS EN CATEGORÍA ALCOHÓLICA
-- ================================================================
-- Análisis de precios para productos en categoría alcohólica
-- Útil para estimar impacto del ajuste de +10%

SELECT 
    COALESCE(p.categoria, '(sin categoría)') as categoria,
    COUNT(*) as total_productos,
    MIN(p.precio_venta) as precio_min,
    MAX(p.precio_venta) as precio_max,
    ROUND(AVG(p.precio_venta), 2) as precio_promedio,
    ROUND(AVG(p.precio_venta) * 0.10, 2) as incremento_promedio_10pct,
    SUM(p.stock) as stock_total
FROM productos p
WHERE p.categoria LIKE '%alcoh%'
  AND p.activo = 1
GROUP BY p.categoria
ORDER BY total_productos DESC;

-- ================================================================
-- 8. PRODUCTOS MÁS VENDIDOS ALCOHÓLICOS (requiere tabla ventas)
-- ================================================================
-- TOP productos alcohólicos por volumen (útil para priorizar correcciones)
-- Comentado porque requiere JOIN con detalle_ventas

/*
SELECT 
    p.id,
    p.nombre,
    p.categoria,
    p.precio_venta,
    COUNT(dv.id) as veces_vendido,
    SUM(dv.cantidad) as unidades_vendidas,
    SUM(dv.subtotal) as revenue_total
FROM productos p
INNER JOIN detalle_ventas dv ON dv.producto_id = p.id
INNER JOIN ventas v ON v.id = dv.venta_id
WHERE p.nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron)'
  AND p.activo = 1
  AND v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY p.id, p.nombre, p.categoria, p.precio_venta
ORDER BY unidades_vendidas DESC
LIMIT 50;
*/

-- ================================================================
-- 9. VERIFICAR ESTRUCTURA DE TABLA PRODUCTOS
-- ================================================================
-- Muestra la estructura de la tabla para confirmar campos disponibles

DESCRIBE productos;

-- ================================================================
-- 10. SAMPLE DE PRODUCTOS PARA TESTING
-- ================================================================
-- Muestra productos variados para testing de slugify()

SELECT 
    p.id,
    p.nombre,
    p.categoria,
    -- Simular slugify en SQL (aproximación)
    LOWER(TRIM(REGEXP_REPLACE(
        COALESCE(p.categoria, ''), 
        '[^a-zA-Z0-9]+', 
        '-'
    ), '-')) as categoria_slug_simulado,
    CASE 
        WHEN LOWER(TRIM(REGEXP_REPLACE(COALESCE(p.categoria, ''), '[^a-zA-Z0-9]+', '-'), '-')) = 'bebidas-alcoholicas' 
        THEN '✅ Recibirá ajuste'
        ELSE '❌ NO recibirá ajuste'
    END as estado_pricing
FROM productos p
WHERE p.activo = 1
  AND p.stock > 0
ORDER BY p.categoria, p.nombre
LIMIT 50;

-- ================================================================
-- 11. CONTEO RÁPIDO DE COBERTURA
-- ================================================================
-- Métricas de cobertura del sistema de pricing

SELECT 
    'Total productos activos' as metrica,
    COUNT(*) as cantidad
FROM productos 
WHERE activo = 1

UNION ALL

SELECT 
    'Productos con stock > 0' as metrica,
    COUNT(*) as cantidad
FROM productos 
WHERE activo = 1 AND stock > 0

UNION ALL

SELECT 
    'Productos SIN categoría' as metrica,
    COUNT(*) as cantidad
FROM productos 
WHERE activo = 1 AND (categoria IS NULL OR categoria = '')

UNION ALL

SELECT 
    'Productos en "Bebidas Alcohólicas"' as metrica,
    COUNT(*) as cantidad
FROM productos 
WHERE activo = 1 AND categoria LIKE '%alcoh%'

UNION ALL

SELECT 
    'Alcohólicos (por nombre) BIEN categorizados' as metrica,
    COUNT(*) as cantidad
FROM productos 
WHERE activo = 1 
  AND nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra)'
  AND categoria LIKE '%alcoh%'

UNION ALL

SELECT 
    'Alcohólicos (por nombre) MAL categorizados' as metrica,
    COUNT(*) as cantidad
FROM productos 
WHERE activo = 1 
  AND nombre REGEXP '(cerveza|vino|fernet|whisky|vodka|gin|ron|sidra)'
  AND (categoria IS NULL OR categoria NOT LIKE '%alcoh%');

-- ================================================================
-- INSTRUCCIONES DE USO:
-- ================================================================
-- 1. Copiar y pegar cada query en tu cliente MySQL (phpMyAdmin, Workbench, etc.)
-- 2. Ejecutar queries una por una (no todas juntas)
-- 3. Exportar resultados a CSV o Excel para análisis
-- 4. Usar los IDs de productos para corrección manual via UI
-- ================================================================

