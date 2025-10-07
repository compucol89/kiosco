<?php
/**
 * 🧪 TEST SDK SIMPLE
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'afip_sdk_simple.php';
require_once 'bd_conexion.php';

try {
    echo "🚀 TESTING AFIP SDK SIMPLE...\n\n";
    
    // Crear venta de prueba
    $pdo = Conexion::obtenerConexion();
    $stmt = $pdo->prepare("
        INSERT INTO ventas (monto_total, metodo_pago, estado, fecha, cliente_nombre, detalles_json) 
        VALUES (850.00, 'efectivo', 'completado', NOW(), 'Cliente SDK Test', '[{\"nombre\":\"Coca Cola\",\"cantidad\":1,\"precio\":850}]')
    ");
    $stmt->execute();
    $venta_test_id = $pdo->lastInsertId();
    
    echo "✅ Venta de prueba: ID {$venta_test_id} - $850\n\n";
    
    // Probar SDK
    echo "🌐 GENERANDO COMPROBANTE CON SDK...\n";
    $resultado = generarComprobanteSDK($venta_test_id);
    
    echo "📋 RESULTADO:\n";
    echo "- Success: " . ($resultado['success'] ? 'SÍ ✅' : 'NO ❌') . "\n";
    echo "- CAE: " . ($resultado['cae'] ?? 'N/A') . "\n";
    echo "- Número: " . ($resultado['numero_comprobante'] ?? 'N/A') . "\n";
    echo "- Método: " . ($resultado['metodo'] ?? 'N/A') . "\n";
    
    if (isset($resultado['nota'])) {
        echo "- Nota: " . $resultado['nota'] . "\n";
    }
    
    // Verificar en BD
    $stmt = $pdo->prepare("SELECT cae, numero_comprobante, comprobante_fiscal FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    $venta_bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n💾 EN BASE DE DATOS:\n";
    echo "- CAE: " . ($venta_bd['cae'] ?? 'NULL') . "\n";
    echo "- Número: " . ($venta_bd['numero_comprobante'] ?? 'NULL') . "\n";
    echo "- Comprobante: " . ($venta_bd['comprobante_fiscal'] ?? 'NULL') . "\n";
    
    // Limpiar
    $stmt = $pdo->prepare("DELETE FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    
    echo "\n🎯 CONCLUSIÓN:\n";
    if ($resultado['success'] && isset($resultado['metodo']) && $resultado['metodo'] === 'AFIP_SDK_REAL') {
        echo "🟢 AFIP SDK FUNCIONANDO - FACTURACIÓN REAL\n";
    } elseif ($resultado['success']) {
        echo "🟡 FACTURACIÓN LOCAL VÁLIDA - SDK NO DISPONIBLE\n";
    } else {
        echo "🔴 ERROR EN FACTURACIÓN\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
?>
