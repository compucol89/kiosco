<?php
/**
 * api/crear_tabla_configuracion_facturacion.php
 * Crea tabla para configurar qué métodos de pago se facturan
 * RELEVANT FILES: config_afip.php, procesar_venta_ultra_rapida.php
 */

header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== CREANDO TABLA DE CONFIGURACIÓN FACTURACIÓN ===\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    
    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracion_facturacion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            metodo_pago VARCHAR(50) NOT NULL UNIQUE,
            requiere_factura BOOLEAN DEFAULT FALSE,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✅ Tabla creada\n\n";
    
    // Insertar configuración por defecto
    $metodos = [
        ['efectivo', 0],      // No factura efectivo por defecto
        ['tarjeta', 0],       // No factura tarjeta por defecto
        ['transferencia', 1], // SÍ factura transferencia
        ['qr', 1]             // SÍ factura QR
    ];
    
    foreach ($metodos as [$metodo, $factura]) {
        $pdo->prepare("
            INSERT INTO configuracion_facturacion (metodo_pago, requiere_factura) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE requiere_factura = ?
        ")->execute([$metodo, $factura, $factura]);
    }
    
    echo "✅ Configuración inicial guardada:\n";
    echo "   - Efectivo: NO factura\n";
    echo "   - Tarjeta: NO factura\n";
    echo "   - Transferencia: SÍ factura ✅\n";
    echo "   - QR: SÍ factura ✅\n\n";
    
    // Verificar
    $stmt = $pdo->query("SELECT * FROM configuracion_facturacion ORDER BY metodo_pago");
    $config = $stmt->fetchAll();
    
    echo "📋 Configuración actual:\n";
    foreach ($config as $c) {
        $factura = $c['requiere_factura'] ? '✅ SÍ' : '❌ NO';
        echo "   {$c['metodo_pago']}: $factura\n";
    }
    
    echo "\n✅ ¡Tabla creada y configurada correctamente!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>


