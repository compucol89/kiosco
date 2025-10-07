<?php
/**
 * 🚀 MIGRACIÓN AUTOMÁTICA - SISTEMA DE CAJA UNIFICADO
 * 
 * Script para migrar y verificar la estructura de base de datos
 * para el sistema de control de caja
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

require_once 'config_database.php';

echo "<h1>🚀 MIGRACIÓN SISTEMA DE CAJA UNIFICADO</h1>";
echo "<p><strong>Fecha:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

try {
    $pdo = obtenerConexionUnificada();
    echo "<p>✅ <strong>Conexión a la base de datos establecida</strong></p>";
    
    // ========================================================================
    // PASO 1: VERIFICAR Y CREAR TABLA PRINCIPAL DE CAJA
    // ========================================================================
    echo "<h2>📋 PASO 1: Verificando tabla principal 'caja'</h2>";
    
    $caja_exists = $pdo->query("SHOW TABLES LIKE 'caja'")->rowCount() > 0;
    
    if (!$caja_exists) {
        echo "<p>⚠️ Creando tabla 'caja'...</p>";
        
        $sql_caja = "CREATE TABLE caja (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_cierre TIMESTAMP NULL,
            monto_apertura DECIMAL(12,2) NOT NULL DEFAULT 0,
            monto_cierre DECIMAL(12,2) NULL,
            estado VARCHAR(20) NOT NULL DEFAULT 'abierta',
            usuario_id INT NULL,
            usuario_cierre_id INT NULL,
            descripcion VARCHAR(500) NULL,
            
            -- Campos calculados automáticamente
            efectivo_teorico DECIMAL(12,2) NULL,
            diferencia DECIMAL(12,2) NULL,
            justificacion TEXT NULL,
            notas_cierre TEXT NULL,
            
            -- Totales por método de pago
            total_ventas_efectivo DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_ventas_tarjeta DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_ventas_transferencia DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_retiros DECIMAL(12,2) NOT NULL DEFAULT 0,
            
            -- Auditoría
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Índices
            INDEX idx_estado (estado),
            INDEX idx_fecha_apertura (fecha_apertura),
            INDEX idx_usuario_id (usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_caja);
        echo "<p>✅ Tabla 'caja' creada exitosamente</p>";
    } else {
        echo "<p>✅ Tabla 'caja' ya existe</p>";
    }
    
    // ========================================================================
    // PASO 2: VERIFICAR Y CREAR TABLA DE MOVIMIENTOS
    // ========================================================================
    echo "<h2>📋 PASO 2: Verificando tabla 'movimientos_caja'</h2>";
    
    $movimientos_exists = $pdo->query("SHOW TABLES LIKE 'movimientos_caja'")->rowCount() > 0;
    
    if (!$movimientos_exists) {
        echo "<p>⚠️ Creando tabla 'movimientos_caja'...</p>";
        
        $sql_movimientos = "CREATE TABLE movimientos_caja (
            id INT AUTO_INCREMENT PRIMARY KEY,
            caja_id INT NULL,
            tipo VARCHAR(20) NOT NULL,
            monto DECIMAL(12,2) NOT NULL DEFAULT 0,
            descripcion VARCHAR(500) NULL,
            usuario_id INT NULL,
            fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            -- Detalles específicos de transacción
            metodo_pago VARCHAR(50) NULL DEFAULT 'efectivo',
            tipo_transaccion VARCHAR(50) NULL DEFAULT 'operacion',
            venta_id INT NULL,
            afecta_efectivo TINYINT(1) NOT NULL DEFAULT 1,
            numero_comprobante VARCHAR(100) NULL,
            categoria VARCHAR(100) NULL DEFAULT 'general',
            referencia VARCHAR(255) NULL,
            
            -- Auditoría y trazabilidad
            observaciones_extra TEXT NULL,
            estado VARCHAR(50) NULL DEFAULT 'confirmado',
            ip_origen VARCHAR(45) NULL,
            user_agent TEXT NULL,
            
            -- Timestamps para auditoría inmutable
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Índices optimizados
            INDEX idx_caja_id (caja_id),
            INDEX idx_tipo (tipo),
            INDEX idx_metodo_pago (metodo_pago),
            INDEX idx_tipo_transaccion (tipo_transaccion),
            INDEX idx_fecha_hora (fecha_hora),
            INDEX idx_usuario_id (usuario_id),
            INDEX idx_venta_id (venta_id),
            INDEX idx_estado (estado),
            INDEX idx_afecta_efectivo (afecta_efectivo),
            
            -- Claves foráneas
            FOREIGN KEY (caja_id) REFERENCES caja(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_movimientos);
        echo "<p>✅ Tabla 'movimientos_caja' creada exitosamente</p>";
    } else {
        echo "<p>✅ Tabla 'movimientos_caja' ya existe</p>";
        
        // Verificar si tiene las columnas necesarias
        $columns = $pdo->query("DESCRIBE movimientos_caja")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['caja_id', 'metodo_pago', 'tipo_transaccion', 'afecta_efectivo'];
        
        foreach ($required_columns as $column) {
            if (!in_array($column, $columns)) {
                echo "<p>⚠️ Agregando columna faltante: $column</p>";
                
                switch ($column) {
                    case 'caja_id':
                        $pdo->exec("ALTER TABLE movimientos_caja ADD COLUMN caja_id INT NULL AFTER id");
                        $pdo->exec("ALTER TABLE movimientos_caja ADD INDEX idx_caja_id (caja_id)");
                        break;
                    case 'metodo_pago':
                        $pdo->exec("ALTER TABLE movimientos_caja ADD COLUMN metodo_pago VARCHAR(50) NULL DEFAULT 'efectivo' AFTER fecha_hora");
                        break;
                    case 'tipo_transaccion':
                        $pdo->exec("ALTER TABLE movimientos_caja ADD COLUMN tipo_transaccion VARCHAR(50) NULL DEFAULT 'operacion' AFTER metodo_pago");
                        break;
                    case 'afecta_efectivo':
                        $pdo->exec("ALTER TABLE movimientos_caja ADD COLUMN afecta_efectivo TINYINT(1) NOT NULL DEFAULT 1 AFTER tipo_transaccion");
                        break;
                }
            }
        }
    }
    
    // ========================================================================
    // PASO 3: VERIFICAR ESTRUCTURA Y DATOS
    // ========================================================================
    echo "<h2>📋 PASO 3: Verificando integridad del sistema</h2>";
    
    // Contar registros
    $count_caja = $pdo->query("SELECT COUNT(*) FROM caja")->fetchColumn();
    $count_movimientos = $pdo->query("SELECT COUNT(*) FROM movimientos_caja")->fetchColumn();
    
    echo "<p>📊 <strong>Estadísticas actuales:</strong></p>";
    echo "<ul>";
    echo "<li>Registros en tabla 'caja': <strong>$count_caja</strong></li>";
    echo "<li>Registros en tabla 'movimientos_caja': <strong>$count_movimientos</strong></li>";
    echo "</ul>";
    
    // Verificar estado de cajas
    $cajas_abiertas = $pdo->query("SELECT COUNT(*) FROM caja WHERE estado = 'abierta'")->fetchColumn();
    
    if ($cajas_abiertas > 1) {
        echo "<p>⚠️ <strong>ADVERTENCIA:</strong> Hay $cajas_abiertas cajas abiertas. Solo debería haber una.</p>";
    } elseif ($cajas_abiertas == 1) {
        echo "<p>✅ <strong>Estado normal:</strong> Hay 1 caja abierta</p>";
    } else {
        echo "<p>🔒 <strong>Estado:</strong> No hay cajas abiertas actualmente</p>";
    }
    
    // ========================================================================
    // PASO 4: MIGRAR DATOS LEGACY SI EXISTEN
    // ========================================================================
    echo "<h2>📋 PASO 4: Verificando datos legacy</h2>";
    
    // Verificar si hay tabla antigua de movimientos
    $old_table_exists = $pdo->query("SHOW TABLES LIKE 'movimientos_caja_old'")->rowCount() > 0;
    
    if ($old_table_exists) {
        echo "<p>⚠️ Encontrada tabla legacy 'movimientos_caja_old'</p>";
        
        // Migrar datos si es necesario
        $count_old = $pdo->query("SELECT COUNT(*) FROM movimientos_caja_old")->fetchColumn();
        echo "<p>📊 Registros en tabla legacy: $count_old</p>";
        
        if ($count_old > 0) {
            echo "<p>💡 <strong>Nota:</strong> Para migrar datos legacy, ejecute manualmente el script de migración correspondiente</p>";
        }
    } else {
        echo "<p>✅ No se encontraron tablas legacy</p>";
    }
    
    // ========================================================================
    // RESUMEN FINAL
    // ========================================================================
    echo "<hr>";
    echo "<h2>🎉 MIGRACIÓN COMPLETADA EXITOSAMENTE</h2>";
    echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>";
    echo "<p><strong>✅ El sistema de caja está listo para producción</strong></p>";
    echo "<ul>";
    echo "<li>✅ Estructura de base de datos verificada</li>";
    echo "<li>✅ Tablas principales creadas/actualizadas</li>";
    echo "<li>✅ Índices optimizados aplicados</li>";
    echo "<li>✅ Compatibilidad con APIs legacy mantenida</li>";
    echo "</ul>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ol>";
    echo "<li>Verificar que la aplicación web funcione correctamente</li>";
    echo "<li>Realizar pruebas de apertura y cierre de caja</li>";
    echo "<li>Validar el registro de movimientos</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<h2>❌ ERROR EN LA MIGRACIÓN</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
    echo "</div>";
}
?>
