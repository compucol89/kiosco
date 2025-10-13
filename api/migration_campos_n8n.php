<?php
/**
 * api/migration_campos_n8n.php
 * Migración: Agrega campos necesarios para integración con n8n
 * Ejecutar una sola vez antes de configurar n8n
 * RELEVANT FILES: api/n8n_ventas_pendientes.php, api/n8n_marcar_facturada.php
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cambios = [];
    $errores = [];
    
    // Función helper para verificar si existe una columna
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    }
    
    // 1. fecha_vencimiento_cae (fecha de vencimiento del CAE)
    if (!columnExists($pdo, 'ventas', 'fecha_vencimiento_cae')) {
        try {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN fecha_vencimiento_cae DATE NULL AFTER cae");
            $cambios[] = "✅ Columna 'fecha_vencimiento_cae' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error al agregar fecha_vencimiento_cae: " . $e->getMessage();
        }
    } else {
        $cambios[] = "ℹ️  Columna 'fecha_vencimiento_cae' ya existe";
    }
    
    // 2. punto_venta_afip (punto de venta usado en AFIP)
    if (!columnExists($pdo, 'ventas', 'punto_venta_afip')) {
        try {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN punto_venta_afip INT NULL AFTER tipo_comprobante");
            $cambios[] = "✅ Columna 'punto_venta_afip' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error al agregar punto_venta_afip: " . $e->getMessage();
        }
    } else {
        $cambios[] = "ℹ️  Columna 'punto_venta_afip' ya existe";
    }
    
    // 3. numero_comprobante_afip (número de comprobante de AFIP)
    if (!columnExists($pdo, 'ventas', 'numero_comprobante_afip')) {
        try {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN numero_comprobante_afip INT NULL AFTER punto_venta_afip");
            $cambios[] = "✅ Columna 'numero_comprobante_afip' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error al agregar numero_comprobante_afip: " . $e->getMessage();
        }
    } else {
        $cambios[] = "ℹ️  Columna 'numero_comprobante_afip' ya existe";
    }
    
    // 4. fecha_facturacion (timestamp de cuando se facturó en n8n)
    if (!columnExists($pdo, 'ventas', 'fecha_facturacion')) {
        try {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN fecha_facturacion DATETIME NULL AFTER cae");
            $cambios[] = "✅ Columna 'fecha_facturacion' agregada";
        } catch (Exception $e) {
            $errores[] = "❌ Error al agregar fecha_facturacion: " . $e->getMessage();
        }
    } else {
        $cambios[] = "ℹ️  Columna 'fecha_facturacion' ya existe";
    }
    
    // 5. Crear tabla de auditoría para facturación (opcional)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS auditoria_facturacion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                venta_id INT NOT NULL,
                cae VARCHAR(50),
                fecha_facturacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                origen VARCHAR(20) DEFAULT 'n8n',
                datos_adicionales TEXT,
                INDEX idx_venta (venta_id),
                INDEX idx_fecha (fecha_facturacion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $cambios[] = "✅ Tabla 'auditoria_facturacion' creada/verificada";
    } catch (Exception $e) {
        $errores[] = "❌ Error al crear tabla de auditoría: " . $e->getMessage();
    }
    
    // 6. Crear índices para optimizar queries de n8n
    try {
        $resultado = $pdo->query("SHOW INDEX FROM ventas WHERE Key_name = 'idx_ventas_cae'");
        if ($resultado->rowCount() === 0) {
            $pdo->exec("CREATE INDEX idx_ventas_cae ON ventas(cae)");
            $cambios[] = "✅ Índice en columna 'cae' creado";
        } else {
            $cambios[] = "ℹ️  Índice 'idx_ventas_cae' ya existe";
        }
    } catch (Exception $e) {
        $errores[] = "⚠️  Índice en 'cae': " . $e->getMessage();
    }
    
    try {
        $resultado = $pdo->query("SHOW INDEX FROM ventas WHERE Key_name = 'idx_ventas_estado_fecha'");
        if ($resultado->rowCount() === 0) {
            $pdo->exec("CREATE INDEX idx_ventas_estado_fecha ON ventas(estado, fecha)");
            $cambios[] = "✅ Índice compuesto (estado, fecha) creado";
        } else {
            $cambios[] = "ℹ️  Índice 'idx_ventas_estado_fecha' ya existe";
        }
    } catch (Exception $e) {
        $errores[] = "⚠️  Índice compuesto: " . $e->getMessage();
    }
    
    // Resumen final
    $resumen = [
        'success' => count($errores) === 0,
        'total_cambios' => count($cambios),
        'total_errores' => count($errores),
        'cambios' => $cambios,
        'errores' => $errores,
        'mensaje' => count($errores) === 0 
            ? '🎉 Migración completada exitosamente. El sistema está listo para n8n.' 
            : '⚠️  Migración completada con algunos errores. Revisar detalles.'
    ];
    
    http_response_code(200);
    echo json_encode($resumen, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error crítico en la migración',
        'detalle' => $e->getMessage()
    ]);
}
?>

