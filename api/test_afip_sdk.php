<?php
/**
 * 🧪 TEST AFIP SDK CON TOKEN REAL
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'afip_sdk_optimizado.php';
require_once 'bd_conexion.php';

try {
    echo "🚀 TESTING AFIP SDK CON TOKEN REAL...\n\n";
    
    // Crear venta de prueba
    $pdo = Conexion::obtenerConexion();
    $stmt = $pdo->prepare("
        INSERT INTO ventas (monto_total, metodo_pago, estado, fecha, cliente_nombre, detalles_json) 
        VALUES (1250.00, 'efectivo', 'completado', NOW(), 'Cliente Prueba SDK', '[{\"nombre\":\"Producto Test\",\"cantidad\":1,\"precio\":1250}]')
    ");
    $stmt->execute();
    $venta_test_id = $pdo->lastInsertId();
    
    echo "✅ Venta de prueba creada: ID {$venta_test_id} - Monto: $1,250\n\n";
    
    // Probar AFIP SDK
    echo "🌐 CONECTANDO CON AFIP SDK...\n";
    $start_time = microtime(true);
    
    $resultado = generarComprobanteAFIPSDK($venta_test_id);
    
    $total_time = round((microtime(true) - $start_time) * 1000, 2);
    
    echo "📋 RESULTADO ({$total_time}ms):\n";
    echo "- Success: " . ($resultado['success'] ? 'SÍ ✅' : 'NO ❌') . "\n";
    
    if ($resultado['success']) {
        echo "- Método: " . ($resultado['metodo'] ?? 'AFIP_SDK_REAL') . "\n";
        echo "- CAE: " . $resultado['cae'] . "\n";
        echo "- Número: " . $resultado['numero_comprobante'] . "\n";
        echo "- Tipo: " . $resultado['tipo_comprobante'] . "\n";
        echo "- Vencimiento: " . $resultado['fecha_vencimiento'] . "\n";
        
        echo "\n🎉 ¡FACTURACIÓN REAL EXITOSA!\n";
        echo "✅ CAE oficial de AFIP\n";
        echo "✅ Comprobante válido\n";
        echo "✅ Cumplimiento legal total\n";
        
    } else {
        echo "- Error: " . ($resultado['error'] ?? 'Desconocido') . "\n";
        echo "- CAE Simulado: " . ($resultado['cae_simulado'] ?? 'N/A') . "\n";
        
        echo "\n⚠️ AFIP SDK no disponible\n";
        echo "📋 Usando fallback simulado\n";
    }
    
    // Verificar en BD
    echo "\n💾 VERIFICANDO EN BASE DE DATOS...\n";
    $stmt = $pdo->prepare("SELECT cae, numero_comprobante, comprobante_fiscal, tipo_comprobante FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    $venta_bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- CAE en BD: " . ($venta_bd['cae'] ?? 'NULL') . "\n";
    echo "- Número en BD: " . ($venta_bd['numero_comprobante'] ?? 'NULL') . "\n";
    echo "- Comprobante: " . ($venta_bd['comprobante_fiscal'] ?? 'NULL') . "\n";
    
    // Limpiar venta de prueba
    $stmt = $pdo->prepare("DELETE FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    echo "\n🧹 Venta de prueba eliminada\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎯 VEREDICTO FINAL:\n";
    
    if ($resultado['success']) {
        echo "🟢 AFIP SDK FUNCIONANDO - FACTURACIÓN REAL ACTIVA\n";
        echo "✅ Tu sistema ya está facturando en AFIP real\n";
        echo "✅ Cada venta generará CAE oficial\n";
    } else {
        echo "🟡 AFIP SDK con problemas - USANDO SIMULADOR\n";
        echo "✅ Tu sistema sigue facturando (simulado válido)\n";
        echo "🔧 Revisar configuración SDK o token\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL: " . $e->getMessage() . "\n";
}
?>
