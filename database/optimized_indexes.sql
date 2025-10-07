-- 🚀 OPTIMIZACIÓN ENTERPRISE - ÍNDICES DE BASE DE DATOS
-- Performance Target: <25ms para todas las consultas críticas
-- Basado en análisis del módulo Historial de Ventas

-- ===== ELIMINAR ÍNDICES EXISTENTES (SI EXISTEN) =====
DROP INDEX IF EXISTS idx_ventas_performance ON ventas;
DROP INDEX IF EXISTS idx_ventas_fecha_estado ON ventas;
DROP INDEX IF EXISTS idx_ventas_metodo_pago ON ventas;
DROP INDEX IF EXISTS idx_ventas_cliente ON ventas;
DROP INDEX IF EXISTS idx_ventas_monto ON ventas;
DROP INDEX IF EXISTS idx_ventas_busqueda ON ventas;
DROP INDEX IF EXISTS idx_ventas_comprobante ON ventas;

-- ===== ÍNDICES PRINCIPALES PARA PERFORMANCE =====

-- 1. ÍNDICE COMPUESTO PRINCIPAL (CRÍTICO)
-- Optimiza: WHERE estado = ? AND DATE(fecha) = ? AND metodo_pago = ?
-- Target: <10ms para consultas del dashboard
CREATE INDEX idx_ventas_performance 
ON ventas (estado, fecha, metodo_pago, monto_total) 
COMMENT 'Índice principal para performance <25ms en consultas de ventas';

-- 2. ÍNDICE PARA BÚSQUEDAS POR FECHA (FRECUENTE)
-- Optimiza: DATE(fecha) = CURDATE(), rangos de fechas
CREATE INDEX idx_ventas_fecha_estado 
ON ventas (fecha DESC, estado, id DESC) 
COMMENT 'Optimización para filtros de fecha y ordenamiento temporal';

-- 3. ÍNDICE PARA AGREGACIONES POR MÉTODO DE PAGO
-- Optimiza: GROUP BY metodo_pago, SUM(monto_total)
CREATE INDEX idx_ventas_metodo_pago 
ON ventas (metodo_pago, estado, monto_total) 
COMMENT 'Agregaciones rápidas por método de pago';

-- 4. ÍNDICE PARA BÚSQUEDAS DE CLIENTES
-- Optimiza: WHERE cliente_nombre LIKE '%...%'
CREATE INDEX idx_ventas_cliente 
ON ventas (cliente_nombre, fecha DESC) 
COMMENT 'Búsquedas por nombre de cliente';

-- 5. ÍNDICE PARA RANGOS DE MONTOS
-- Optimiza: WHERE monto_total BETWEEN ? AND ?
CREATE INDEX idx_ventas_monto 
ON ventas (monto_total, fecha DESC, estado) 
COMMENT 'Filtros por rangos de montos';

-- 6. ÍNDICE PARA BÚSQUEDA POR COMPROBANTE
-- Optimiza: WHERE numero_comprobante = ?
CREATE INDEX idx_ventas_comprobante 
ON ventas (numero_comprobante) 
COMMENT 'Búsqueda directa por número de comprobante';

-- ===== ÍNDICES ESPECIALIZADOS =====

-- 7. ÍNDICE PARA ID + FECHA (PAGINACIÓN OPTIMIZADA)
-- Optimiza: ORDER BY fecha DESC, id DESC LIMIT ? OFFSET ?
CREATE INDEX idx_ventas_pagination 
ON ventas (fecha DESC, id DESC, estado) 
COMMENT 'Paginación ultra-rápida para lista de ventas';

-- 8. ÍNDICE PARA MÉTRICAS DEL DASHBOARD
-- Optimiza: COUNT(*), SUM(monto_total), AVG(monto_total)
CREATE INDEX idx_ventas_dashboard_metrics 
ON ventas (estado, fecha, monto_total, metodo_pago) 
COMMENT 'Cálculo instantáneo de métricas del dashboard';

-- ===== CONFIGURACIÓN DE PERFORMANCE =====

-- Configurar el optimizador de MySQL para mejor performance
SET SESSION optimizer_switch = 'index_merge=on,index_merge_union=on,index_merge_sort_union=on';

-- ===== ANÁLISIS DE COBERTURA DE ÍNDICES =====

-- Query para verificar uso de índices (ejecutar después de crear)
-- EXPLAIN SELECT * FROM ventas WHERE estado = 'completado' AND DATE(fecha) = CURDATE();

-- ===== ESTADÍSTICAS Y MANTENIMIENTO =====

-- Actualizar estadísticas de la tabla para optimización
ANALYZE TABLE ventas;

-- ===== VISTAS MATERIALIZADAS PARA PERFORMANCE =====

-- Vista pre-calculada para métricas diarias (actualizar cada 5 minutos)
DROP VIEW IF EXISTS ventas_daily_summary;
CREATE VIEW ventas_daily_summary AS
SELECT 
    DATE(fecha) as fecha_dashboard,
    COUNT(*) as total_ventas,
    SUM(monto_total) as total_recaudado,
    AVG(monto_total) as ticket_promedio,
    SUM(descuento) as total_descuentos,
    
    -- Pre-calcular por método de pago
    SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto_total ELSE 0 END) as efectivo_total,
    SUM(CASE WHEN metodo_pago = 'tarjeta' THEN monto_total ELSE 0 END) as tarjeta_total,
    SUM(CASE WHEN metodo_pago = 'mercadopago' THEN monto_total ELSE 0 END) as mp_total,
    SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto_total ELSE 0 END) as transferencia_total,
    
    -- Contadores por método
    COUNT(CASE WHEN metodo_pago = 'efectivo' THEN 1 END) as efectivo_count,
    COUNT(CASE WHEN metodo_pago = 'tarjeta' THEN 1 END) as tarjeta_count,
    COUNT(CASE WHEN metodo_pago = 'mercadopago' THEN 1 END) as mp_count,
    COUNT(CASE WHEN metodo_pago = 'transferencia' THEN 1 END) as transferencia_count,
    
    -- Métricas adicionales
    MAX(monto_total) as venta_maxima,
    MIN(monto_total) as venta_minima,
    
    -- Timestamp de última actualización
    NOW() as last_updated
FROM ventas 
WHERE estado IN ('completada', 'completado')
    AND fecha >= CURDATE() - INTERVAL 30 DAY  -- Últimos 30 días
GROUP BY DATE(fecha)
ORDER BY fecha_dashboard DESC;

-- ===== PROCEDIMIENTOS ALMACENADOS PARA PERFORMANCE =====

-- Procedimiento para obtener métricas del dashboard ultra-rápido
DELIMITER $$

DROP PROCEDURE IF EXISTS GetDashboardMetricsOptimized$$
CREATE PROCEDURE GetDashboardMetricsOptimized(IN target_date DATE)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    -- Usar la vista pre-calculada si está disponible
    IF target_date = CURDATE() THEN
        SELECT 
            total_ventas,
            total_recaudado,
            ticket_promedio,
            total_descuentos,
            efectivo_total,
            tarjeta_total + mp_total + transferencia_total as digital_total,
            venta_maxima,
            venta_minima
        FROM ventas_daily_summary 
        WHERE fecha_dashboard = target_date
        LIMIT 1;
    ELSE
        -- Cálculo directo para fechas específicas
        SELECT 
            COUNT(*) as total_ventas,
            COALESCE(SUM(monto_total), 0) as total_recaudado,
            COALESCE(AVG(monto_total), 0) as ticket_promedio,
            COALESCE(SUM(descuento), 0) as total_descuentos,
            SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto_total ELSE 0 END) as efectivo_total,
            SUM(CASE WHEN metodo_pago IN ('tarjeta', 'mercadopago', 'transferencia') THEN monto_total ELSE 0 END) as digital_total,
            MAX(monto_total) as venta_maxima,
            MIN(monto_total) as venta_minima
        FROM ventas 
        WHERE DATE(fecha) = target_date 
            AND estado IN ('completada', 'completado');
    END IF;
END$$

DELIMITER ;

-- ===== CONFIGURACIÓN DE CACHE DE QUERIES =====

-- Habilitar query cache para queries repetitivas
SET GLOBAL query_cache_type = ON;
SET GLOBAL query_cache_size = 67108864; -- 64MB

-- ===== MONITORING Y ALERTAS =====

-- Vista para monitorear performance de queries
DROP VIEW IF EXISTS query_performance_monitor;
CREATE VIEW query_performance_monitor AS
SELECT 
    'ventas' as table_name,
    COUNT(*) as total_rows,
    AVG(CHAR_LENGTH(detalles_json)) as avg_json_size,
    MAX(fecha) as latest_record,
    MIN(fecha) as earliest_record
FROM ventas;

-- ===== VALIDACIÓN DE ÍNDICES =====

-- Script para verificar que los índices se crearon correctamente
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    INDEX_TYPE,
    COMMENT
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'ventas'
    AND INDEX_NAME LIKE 'idx_ventas_%'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- ===== RECOMENDACIONES DE PERFORMANCE =====

/*
RECOMENDACIONES ENTERPRISE:

1. MONITORING:
   - Ejecutar ANALYZE TABLE ventas semanalmente
   - Monitorear slow query log
   - Verificar uso de índices con EXPLAIN

2. MANTENIMIENTO:
   - Actualizar estadísticas cada noche
   - Purgar datos antiguos (>2 años) si es necesario
   - Verificar fragmentación de índices

3. SCALING:
   - Considerar particionamiento por fecha para >1M registros
   - Implementar read replicas para consultas de solo lectura
   - Usar connection pooling

4. CACHE:
   - Redis/Memcached para métricas del dashboard
   - Application-level caching para consultas frecuentes
   - Query result caching para reportes

5. ALERTAS:
   - Configurar alertas para queries >25ms
   - Monitor de uso de CPU y memoria
   - Alertas de espacio en disco

Performance Targets Esperados:
- Dashboard metrics: <10ms
- Lista de ventas con filtros: <25ms
- Búsquedas por texto: <50ms
- Agregaciones complejas: <100ms
*/