<?php
/**
 * api/test_afip_sdk_real.php
 * Prueba de conexión real con AFIP SDK
 * RELEVANT FILES: afip_sdk_optimizado.php, config_afip.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DE AFIP SDK REAL ===\n\n";

require_once 'config_afip.php';
require_once 'afip_sdk_real.php';

try {
    $afip_sdk = new AFIPReal();
    
    echo "✅ AFIP SDK inicializado\n\n";
    echo "📊 CONFIGURACIÓN:\n";
    echo "   CUIT: 20944515411\n";
    echo "   Ambiente: prod (PRODUCCIÓN)\n";
    echo "   Punto de venta: 3\n";
    echo "   Token: ZaGIy...Wu4uW\n\n";
    
    // Crear una venta de prueba en BD
    require_once 'bd_conexion.php';
    $pdo = Conexion::obtenerConexion();
    
    // Verificar si hay ventas recientes
    $stmt = $pdo->query("SELECT id, monto_total, metodo_pago FROM ventas ORDER BY id DESC LIMIT 1");
    $venta = $stmt->fetch();
    
    if (!$venta) {
        echo "⚠️ No hay ventas en la BD para probar\n";
        echo "💡 Crea una venta primero desde el punto de venta\n";
        exit;
    }
    
    echo "📋 Venta de prueba:\n";
    echo "   ID: {$venta['id']}\n";
    echo "   Monto: \${$venta['monto_total']}\n";
    echo "   Método: {$venta['metodo_pago']}\n\n";
    
    echo "🚀 Intentando generar comprobante con AFIP SDK...\n";
    echo str_repeat("-", 70) . "\n";
    
    $resultado = $afip_sdk->generarComprobante($venta['id']);
    
    if ($resultado['success']) {
        echo "\n✅ ¡COMPROBANTE GENERADO EXITOSAMENTE!\n\n";
        echo "📋 Datos del comprobante:\n";
        echo "   CAE: {$resultado['cae']}\n";
        echo "   Número: {$resultado['numero_comprobante']}\n";
        echo "   Tipo: {$resultado['tipo_comprobante']}\n";
        echo "   Vencimiento: {$resultado['fecha_vencimiento']}\n";
        echo "   Método: {$resultado['metodo']}\n\n";
        
        echo "🎯 ESTO ES UN CAE REAL DE AFIP\n";
        echo "✅ El sistema SÍ está conectado correctamente\n";
    } else {
        echo "\n❌ ERROR AL GENERAR COMPROBANTE\n\n";
        echo "Error: {$resultado['error']}\n\n";
        
        if (isset($resultado['cae_simulado'])) {
            echo "⚠️ Se generó CAE simulado: {$resultado['cae_simulado']}\n";
            echo "💡 Esto indica que AFIP SDK no respondió\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
