<?php
/**
 * 🔧 AGREGAR COLUMNAS AFIP A TABLA VENTAS
 * Migración para soportar facturación electrónica
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 AGREGANDO COLUMNAS AFIP A TABLA VENTAS...\n\n";
    
    // Verificar si las columnas ya existen
    $stmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'cae'");
    $stmt->execute();
    $cae_exists = $stmt->rowCount() > 0;
    
    if (!$cae_exists) {
        // Agregar columnas AFIP
        $sql = "
        ALTER TABLE ventas 
        ADD COLUMN cae VARCHAR(14) NULL COMMENT 'Código de Autorización Electrónica AFIP',
        ADD COLUMN numero_comprobante_fiscal VARCHAR(50) NULL COMMENT 'Número de comprobante fiscal',
        ADD COLUMN fecha_vencimiento_cae DATE NULL COMMENT 'Fecha de vencimiento del CAE',
        ADD COLUMN tipo_comprobante INT NULL COMMENT 'Tipo de comprobante AFIP (6=Factura B, 83=Ticket)',
        ADD COLUMN estado_afip VARCHAR(20) DEFAULT 'PENDIENTE' COMMENT 'Estado del comprobante en AFIP'
        ";
        
        $pdo->exec($sql);
        echo "✅ Columnas AFIP agregadas exitosamente\n";
        
        // Crear índices para performance
        $pdo->exec("CREATE INDEX idx_ventas_cae ON ventas(cae)");
        $pdo->exec("CREATE INDEX idx_ventas_estado_afip ON ventas(estado_afip)");
        echo "✅ Índices AFIP creados\n";
        
    } else {
        echo "ℹ️ Las columnas AFIP ya existen\n";
    }
    
    // Verificar estructura final
    $stmt = $pdo->prepare("DESCRIBE ventas");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 COLUMNAS AFIP EN TABLA VENTAS:\n";
    foreach ($columnas as $columna) {
        if (in_array($columna['Field'], ['cae', 'numero_comprobante_fiscal', 'fecha_vencimiento_cae', 'tipo_comprobante', 'estado_afip'])) {
            echo "- {$columna['Field']}: {$columna['Type']} ({$columna['Comment']})\n";
        }
    }
    
    echo "\n🎉 ¡MIGRACIÓN COMPLETADA! Ahora el sistema puede guardar datos AFIP.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
