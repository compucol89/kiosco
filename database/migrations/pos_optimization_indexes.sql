/*
 * Migración: Optimización de Índices para POS Enterprise
 * Target: Sub-50ms response time para API POS v2
 * Impacto: Mejora performance de consultas de productos críticas
 * 
 * Ejecución: mysql -u [user] -p [database] < pos_optimization_indexes.sql
 */

-- ===== ANÁLISIS PREVIO =====
-- Verificar índices existentes antes de la migración
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE,
    CARDINALITY
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'productos'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ===== ÍNDICES CRÍTICOS PARA POS =====

-- 1. ÍNDICE COMPUESTO PRINCIPAL: Stock + Activo + Categoría
-- Target: Filtros de productos con stock por categoría <10ms
CREATE INDEX IF NOT EXISTS idx_productos_pos_main 
ON productos (activo, stock_actual, categoria, nombre);

-- 2. ÍNDICE DE BÚSQUEDA: Búsqueda por nombre y código
-- Target: Búsquedas de productos <15ms
CREATE INDEX IF NOT EXISTS idx_productos_search 
ON productos (activo, nombre, codigo, barcode);

-- 3. ÍNDICE DE STOCK CRÍTICO: Estado de stock optimizado
-- Target: Filtros por stock disponible <5ms
CREATE INDEX IF NOT EXISTS idx_productos_stock_status 
ON productos (activo, stock_actual, stock_minimo, nombre);

-- 4. ÍNDICE DE CATEGORIZACIÓN: Optimizar filtros por categoría
-- Target: Cambio de categorías <8ms
CREATE INDEX IF NOT EXISTS idx_productos_categoria 
ON productos (categoria, activo, stock_actual, nombre);

-- 5. ÍNDICE DE ADMINISTRACIÓN: Vista completa para admin
-- Target: Carga administrativa completa <25ms
CREATE INDEX IF NOT EXISTS idx_productos_admin 
ON productos (activo, created_at, updated_at);

-- ===== ÍNDICES FULLTEXT PARA BÚSQUEDA AVANZADA =====

-- Búsqueda fulltext en nombre y descripción (solo para MyISAM/InnoDB 5.6+)
-- Verificar si la tabla soporta FULLTEXT
SET @engine = (SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'productos');

-- Solo crear FULLTEXT si es InnoDB o MyISAM
SET @sql = CASE 
    WHEN @engine IN ('InnoDB', 'MyISAM') THEN 
        'CREATE FULLTEXT INDEX IF NOT EXISTS idx_productos_fulltext 
         ON productos (nombre, descripcion);'
    ELSE 
        'SELECT "FULLTEXT index skipped - engine not supported" as message;'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ===== OPTIMIZACIÓN DE TABLA =====

-- Optimizar la tabla después de crear índices
OPTIMIZE TABLE productos;

-- ===== ANÁLISIS POST-MIGRACIÓN =====

-- Verificar nuevos índices creados
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    CARDINALITY,
    INDEX_TYPE,
    CASE 
        WHEN INDEX_NAME = 'idx_productos_pos_main' THEN 'Filtros POS principales'
        WHEN INDEX_NAME = 'idx_productos_search' THEN 'Búsqueda por nombre/código'
        WHEN INDEX_NAME = 'idx_productos_stock_status' THEN 'Estado de stock'
        WHEN INDEX_NAME = 'idx_productos_categoria' THEN 'Filtros por categoría'
        WHEN INDEX_NAME = 'idx_productos_admin' THEN 'Vista administrativa'
        WHEN INDEX_NAME = 'idx_productos_fulltext' THEN 'Búsqueda avanzada'
        ELSE 'Índice existente'
    END as PROPOSITO
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'productos'
AND INDEX_NAME LIKE 'idx_productos_%'
ORDER BY INDEX_NAME;

-- ===== STATISTICS UPDATE =====

-- Actualizar estadísticas para optimizador de consultas
ANALYZE TABLE productos;

-- ===== VERIFICACIÓN DE PERFORMANCE =====

-- Query de prueba para medir mejora
-- (Ejecutar antes y después de la migración para comparar)
SET @start_time = NOW(6);

SELECT COUNT(*) as productos_con_stock 
FROM productos 
WHERE activo = TRUE 
AND COALESCE(stock_actual, stock) > 0;

SELECT COUNT(*) as productos_categoria_bebidas
FROM productos 
WHERE activo = TRUE 
AND categoria = 'Bebidas'
AND COALESCE(stock_actual, stock) > 0;

SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as execution_time_ms;

-- ===== LOGGING DE MIGRACIÓN =====

INSERT INTO migration_log (
    migration_name,
    executed_at,
    description,
    status
) VALUES (
    'pos_optimization_indexes',
    NOW(),
    'Índices optimizados para POS Enterprise - Target <50ms response time',
    'COMPLETED'
) ON DUPLICATE KEY UPDATE 
    executed_at = NOW(),
    status = 'COMPLETED';

-- ===== RECOMENDACIONES POST-MIGRACIÓN =====

/*
PRÓXIMOS PASOS RECOMENDADOS:

1. MONITOREO:
   - Configurar alertas para queries >50ms
   - Monitoring de cardinality de índices
   - Tracking de hit ratio de cada índice

2. OPTIMIZACIONES ADICIONALES:
   - Considerar partitioning por categoria si >100k productos
   - Implementar Redis cache para productos frecuentes
   - Query cache para categorías populares

3. MANTENIMIENTO:
   - ANALYZE TABLE productos semanalmente
   - OPTIMIZE TABLE productos mensualmente
   - Revisar estadísticas de índices trimestralmente

4. TESTING:
   - Ejecutar stress tests con carga de producción
   - Validar queries de la API v2 con EXPLAIN
   - Benchmarking antes/después de la migración
*/

SELECT 'POS Optimization Indexes Migration Completed Successfully' as STATUS;