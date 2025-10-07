/*
 * Migración: Optimización de Índices para Búsqueda Enterprise
 * Target: <25ms simple search, <40ms complex search, >95% precision
 * Elasticsearch-Grade Performance: Strict AND logic + Relevance Scoring
 * 
 * Ejecución: mysql -u [user] -p [database] < search_optimization_indexes.sql
 */

-- ===== ANÁLISIS PREVIO =====
-- Verificar índices existentes de búsqueda
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE,
    CARDINALITY,
    CASE 
        WHEN INDEX_TYPE = 'FULLTEXT' THEN 'Text Search'
        WHEN INDEX_NAME LIKE '%search%' THEN 'Search Optimized'
        ELSE 'Standard Index'
    END as INDEX_PURPOSE
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'productos'
AND (INDEX_TYPE = 'FULLTEXT' OR INDEX_NAME LIKE '%search%')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- ===== ÍNDICES FULLTEXT PARA BÚSQUEDA ENTERPRISE =====

-- 1. ÍNDICE FULLTEXT PRINCIPAL: Búsqueda en texto completo
-- Target: Búsquedas complejas con múltiples palabras <40ms
DROP INDEX IF EXISTS idx_productos_fulltext_enterprise ON productos;
CREATE FULLTEXT INDEX idx_productos_fulltext_enterprise 
ON productos (nombre, descripcion, categoria);

-- 2. ÍNDICE FULLTEXT ESPECÍFICO: Solo nombre (búsquedas exactas)
-- Target: Búsquedas simples de productos específicos <25ms
DROP INDEX IF EXISTS idx_productos_nombre_fulltext ON productos;
CREATE FULLTEXT INDEX idx_productos_nombre_fulltext 
ON productos (nombre);

-- 3. ÍNDICE COMPUESTO PARA FILTROS: Stock + Categoría + Activo
-- Target: Filtros combinados con búsqueda <30ms
DROP INDEX IF EXISTS idx_productos_search_filters ON productos;
CREATE INDEX idx_productos_search_filters 
ON productos (activo, categoria, stock_actual, nombre);

-- 4. ÍNDICE DE CÓDIGOS: Búsqueda exacta por códigos
-- Target: Búsqueda por barcode/código <15ms
DROP INDEX IF EXISTS idx_productos_codes_exact ON productos;
CREATE INDEX idx_productos_codes_exact 
ON productos (barcode, codigo, activo);

-- 5. ÍNDICE DE PRECIOS: Filtros por rango de precios
-- Target: Búsqueda con filtros de precio <25ms
DROP INDEX IF EXISTS idx_productos_price_range ON productos;
CREATE INDEX idx_productos_price_range 
ON productos (precio_venta, activo, stock_actual);

-- 6. ÍNDICE COMPUESTO SEARCH: Optimizado para queries AND estrictas
-- Target: Búsquedas con múltiples condiciones <35ms
DROP INDEX IF EXISTS idx_productos_search_compound ON productos;
CREATE INDEX idx_productos_search_compound 
ON productos (activo, stock_actual, categoria, precio_venta, nombre);

-- ===== CAMPOS CALCULADOS PARA OPTIMIZACIÓN =====

-- Agregar campo normalizado para búsqueda (si no existe)
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'productos' 
    AND COLUMN_NAME = 'nombre_normalizado'
);

SET @sql = CASE 
    WHEN @column_exists = 0 THEN 
        'ALTER TABLE productos ADD COLUMN nombre_normalizado VARCHAR(255) 
         GENERATED ALWAYS AS (
             LOWER(
                 REPLACE(
                     REPLACE(
                         REPLACE(
                             REPLACE(
                                 REPLACE(nombre, "á", "a"), 
                                 "é", "e"
                             ), 
                             "í", "i"
                         ), 
                         "ó", "o"
                     ), 
                     "ú", "u"
                 )
             )
         ) STORED;'
    ELSE 
        'SELECT "Campo nombre_normalizado ya existe" as message;'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índice en campo normalizado (si se creó el campo)
SET @create_normalized_index = CASE 
    WHEN @column_exists = 0 THEN 
        'CREATE INDEX idx_productos_nombre_normalized 
         ON productos (nombre_normalizado, activo);'
    ELSE 
        'SELECT "Índice de campo normalizado ya existe" as message;'
END;

PREPARE stmt FROM @create_normalized_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ===== OPTIMIZACIÓN DE MOTOR DE TABLA =====

-- Verificar y optimizar motor de tabla para FULLTEXT
SET @engine = (SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES 
              WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'productos');

-- Solo optimizar si es necesario
SET @optimize_engine = CASE 
    WHEN @engine != 'InnoDB' THEN 
        'ALTER TABLE productos ENGINE = InnoDB;'
    ELSE 
        'SELECT "Tabla ya usa InnoDB - Optimizada para FULLTEXT" as message;'
END;

PREPARE stmt FROM @optimize_engine;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ===== CONFIGURACIÓN FULLTEXT AVANZADA =====

-- Configurar parámetros de FULLTEXT para mejor precisión
SET GLOBAL innodb_ft_min_token_size = 2;  -- Palabras mínimas de 2 caracteres
SET GLOBAL innodb_ft_max_token_size = 84; -- Palabras máximas de 84 caracteres
SET GLOBAL ft_min_word_len = 2;           -- Compatible con MyISAM si existe

-- ===== ESTADÍSTICAS Y OPTIMIZACIÓN =====

-- Actualizar estadísticas de la tabla
ANALYZE TABLE productos;

-- Optimizar tabla después de crear índices
OPTIMIZE TABLE productos;

-- ===== VALIDACIÓN DE ÍNDICES CREADOS =====

-- Verificar todos los índices relacionados con búsqueda
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE,
    CARDINALITY,
    CASE 
        WHEN INDEX_NAME = 'idx_productos_fulltext_enterprise' THEN 'Búsqueda FULLTEXT principal'
        WHEN INDEX_NAME = 'idx_productos_nombre_fulltext' THEN 'Búsqueda exacta por nombre'
        WHEN INDEX_NAME = 'idx_productos_search_filters' THEN 'Filtros combinados'
        WHEN INDEX_NAME = 'idx_productos_codes_exact' THEN 'Búsqueda por códigos'
        WHEN INDEX_NAME = 'idx_productos_price_range' THEN 'Filtros de precio'
        WHEN INDEX_NAME = 'idx_productos_search_compound' THEN 'Búsqueda compuesta AND'
        WHEN INDEX_NAME = 'idx_productos_nombre_normalized' THEN 'Búsqueda normalizada'
        ELSE 'Índice existente'
    END as PROPOSITO,
    CASE 
        WHEN INDEX_TYPE = 'FULLTEXT' THEN '🔍 FULLTEXT'
        WHEN CARDINALITY > 100 THEN '⚡ Alta selectividad'
        WHEN CARDINALITY > 10 THEN '📊 Media selectividad'
        ELSE '⚠️ Baja selectividad'
    END as CALIDAD
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'productos'
AND (INDEX_NAME LIKE 'idx_productos_%' OR INDEX_TYPE = 'FULLTEXT')
ORDER BY INDEX_NAME;

-- ===== TESTING DE PERFORMANCE =====

-- Query de prueba para medir mejora en búsqueda básica
SET @start_time = NOW(6);

-- Test 1: Búsqueda FULLTEXT simple
SELECT COUNT(*) as test_fulltext_simple
FROM productos 
WHERE MATCH(nombre) AGAINST('agua' IN NATURAL LANGUAGE MODE)
AND activo = TRUE;

-- Test 2: Búsqueda FULLTEXT con múltiples palabras (AND logic)
SELECT COUNT(*) as test_fulltext_multiple
FROM productos 
WHERE MATCH(nombre, descripcion, categoria) AGAINST('+agua +benedictino' IN BOOLEAN MODE)
AND activo = TRUE;

-- Test 3: Búsqueda combinada con filtros
SELECT COUNT(*) as test_combined_filters
FROM productos 
WHERE MATCH(nombre) AGAINST('agua' IN NATURAL LANGUAGE MODE)
AND activo = TRUE 
AND stock_actual > 0
AND precio_venta BETWEEN 1000 AND 5000;

SET @end_time = NOW(6);
SELECT TIMESTAMPDIFF(MICROSECOND, @start_time, @end_time) / 1000 as test_execution_time_ms;

-- ===== CONFIGURACIÓN DE BÚSQUEDA AVANZADA =====

-- Crear tabla para configuración de búsqueda (si no existe)
CREATE TABLE IF NOT EXISTS search_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuraciones por defecto para búsqueda enterprise
INSERT INTO search_config (setting_name, setting_value, description) VALUES
('min_relevance_score', '15', 'Puntuación mínima de relevancia para mostrar resultados'),
('max_results_per_page', '50', 'Máximo número de resultados por página'),
('enable_fuzzy_search', 'true', 'Habilitar búsqueda difusa para corrección de errores'),
('search_cache_ttl', '30', 'Tiempo de vida del cache de búsqueda en segundos'),
('enable_search_analytics', 'true', 'Habilitar análisis de búsquedas para optimización')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
updated_at = CURRENT_TIMESTAMP;

-- ===== TABLA DE ANALYTICS DE BÚSQUEDA =====

-- Crear tabla para analytics de búsqueda (opcional)
CREATE TABLE IF NOT EXISTS search_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_query TEXT NOT NULL,
    normalized_query TEXT,
    results_count INT DEFAULT 0,
    execution_time_ms DECIMAL(10,2),
    precision_rate DECIMAL(5,2),
    user_clicked BOOLEAN DEFAULT FALSE,
    categoria_filter VARCHAR(100),
    price_filter_applied BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_analytics_query (search_query(100)),
    INDEX idx_search_analytics_time (created_at),
    INDEX idx_search_analytics_performance (execution_time_ms, precision_rate)
);

-- ===== LOGGING DE MIGRACIÓN =====

-- Registrar migración completada
INSERT INTO search_config (setting_name, setting_value, description) VALUES
('search_indexes_migration_date', NOW(), 'Fecha de migración de índices de búsqueda enterprise')
ON DUPLICATE KEY UPDATE 
setting_value = NOW(),
updated_at = CURRENT_TIMESTAMP;

-- ===== RECOMENDACIONES DE MANTENIMIENTO =====

/*
MANTENIMIENTO PERIÓDICO REQUERIDO:

1. OPTIMIZACIÓN SEMANAL:
   - OPTIMIZE TABLE productos;
   - ANALYZE TABLE productos;
   - Revisar performance de búsquedas populares

2. MONITOREO MENSUAL:
   - Verificar cardinality de índices
   - Analizar queries lentas en search_analytics
   - Ajustar min_relevance_score según feedback

3. LIMPIEZA TRIMESTRAL:
   - Limpiar search_analytics antiguos (>90 días)
   - Revisar configuraciones de FULLTEXT
   - Evaluar nuevos patrones de búsqueda

4. QUERIES DE MONITOREO:

   -- Verificar performance de índices FULLTEXT
   SHOW INDEX FROM productos WHERE Key_name LIKE '%fulltext%';
   
   -- Top búsquedas más lentas
   SELECT search_query, AVG(execution_time_ms) as avg_time, COUNT(*) as frequency
   FROM search_analytics 
   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
   GROUP BY search_query 
   ORDER BY avg_time DESC 
   LIMIT 10;
   
   -- Búsquedas sin resultados (para optimizar)
   SELECT search_query, COUNT(*) as frequency
   FROM search_analytics 
   WHERE results_count = 0 
   AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
   GROUP BY search_query 
   ORDER BY frequency DESC 
   LIMIT 10;
*/

-- ===== VALIDACIÓN FINAL =====

SELECT 
    'Search Optimization Migration Completed Successfully' as STATUS,
    COUNT(*) as total_indexes_created,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'productos' 
     AND INDEX_TYPE = 'FULLTEXT') as fulltext_indexes,
    NOW() as completed_at
FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'productos'
AND INDEX_NAME LIKE 'idx_productos_%';

-- Mostrar configuración final
SELECT 'Search Configuration:' as INFO;
SELECT setting_name, setting_value, description 
FROM search_config 
WHERE setting_name LIKE 'search_%' OR setting_name LIKE '%relevance%'
ORDER BY setting_name;