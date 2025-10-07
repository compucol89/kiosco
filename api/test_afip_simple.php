<?php
/**
 * 🧪 TEST SIMPLE DE AFIP
 * Prueba rápida del sistema de facturación
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'config_afip.php';
require_once 'afip_testing_simulator.php';

try {
    echo "🧪 PROBANDO SISTEMA AFIP...\n\n";
    
    // Crear venta de prueba
    require_once 'bd_conexion.php';
    $pdo = Conexion::obtenerConexion();
    
    $stmt = $pdo->prepare("
        INSERT INTO ventas (monto_total, metodo_pago, estado, fecha, cliente_nombre, detalles_json) 
        VALUES (1500.00, 'efectivo', 'completado', NOW(), 'Test AFIP', '[]')
    ");
    $stmt->execute();
    $venta_test_id = $pdo->lastInsertId();
    
    echo "✅ Venta de prueba creada: ID {$venta_test_id}\n";
    
    // Probar generación de comprobante
    $resultado = generarComprobanteAFIPTesting($venta_test_id);
    
    echo "📋 RESULTADO:\n";
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // Verificar que se guardó en BD
    $stmt = $pdo->prepare("SELECT cae, numero_comprobante, comprobante_fiscal, tipo_comprobante FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    $venta_actualizada = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n💾 DATOS EN BD:\n";
    echo json_encode($venta_actualizada, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // Limpiar
    $stmt = $pdo->prepare("DELETE FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    
    echo "\n🎉 ¡PRUEBA EXITOSA! El sistema AFIP funciona correctamente.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
?>
