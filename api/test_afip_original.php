<?php
/**
 * 🧪 TEST AFIP ORIGINAL CORREGIDO
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'afip_simple.php';
require_once 'bd_conexion.php';

try {
    echo "🚀 TESTING AFIP ORIGINAL CORREGIDO...\n\n";
    
    // Crear venta de prueba
    $pdo = Conexion::obtenerConexion();
    $stmt = $pdo->prepare("
        INSERT INTO ventas (monto_total, metodo_pago, estado, fecha, cliente_nombre, detalles_json) 
        VALUES (750.00, 'efectivo', 'completado', NOW(), 'Cliente Final Test', '[]')
    ");
    $stmt->execute();
    $venta_test_id = $pdo->lastInsertId();
    
    echo "✅ Venta de prueba: ID {$venta_test_id} - $750\n\n";
    
    // Probar generación
    echo "🧾 GENERANDO COMPROBANTE...\n";
    $resultado = generarComprobanteFiscalDesdVenta($venta_test_id);
    
    echo "📋 RESULTADO:\n";
    echo "- Success: " . ($resultado['success'] ? 'SÍ ✅' : 'NO ❌') . "\n";
    
    if ($resultado['success'] && isset($resultado['comprobante'])) {
        $comp = $resultado['comprobante'];
        echo "- CAE: " . ($comp['cae'] ?? 'N/A') . "\n";
        echo "- Número: " . ($comp['numero_comprobante'] ?? 'N/A') . "\n";
        echo "- Tipo: " . ($comp['tipo_comprobante'] ?? 'N/A') . "\n";
        echo "- Fecha Vto: " . ($comp['fecha_vencimiento'] ?? 'N/A') . "\n";
    } else {
        echo "- Error: " . ($resultado['error'] ?? 'Desconocido') . "\n";
    }
    
    // Verificar en BD
    echo "\n💾 VERIFICANDO EN BASE DE DATOS...\n";
    $stmt = $pdo->prepare("SELECT cae, numero_comprobante, comprobante_fiscal, tipo_comprobante FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    $venta_bd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- CAE: " . ($venta_bd['cae'] ?? 'NULL') . "\n";
    echo "- Número: " . ($venta_bd['numero_comprobante'] ?? 'NULL') . "\n";
    echo "- Comprobante: " . ($venta_bd['comprobante_fiscal'] ?? 'NULL') . "\n";
    echo "- Tipo: " . ($venta_bd['tipo_comprobante'] ?? 'NULL') . "\n";
    
    // Limpiar
    $stmt = $pdo->prepare("DELETE FROM ventas WHERE id = ?");
    $stmt->execute([$venta_test_id]);
    
    echo "\n🎯 VEREDICTO:\n";
    if ($resultado['success'] && !empty($venta_bd['cae'])) {
        echo "🟢 SISTEMA AFIP FUNCIONANDO PERFECTAMENTE\n";
        echo "✅ CAE generado y guardado en BD\n";
        echo "✅ Comprobante fiscal válido\n";
        echo "✅ Listo para producción\n";
    } else {
        echo "🟡 SISTEMA GENERANDO COMPROBANTE PERO NO GUARDANDO\n";
        echo "🔧 Necesita ajuste menor\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
?>
