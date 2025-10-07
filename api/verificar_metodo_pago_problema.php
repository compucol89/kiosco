<?php
/**
 * 🔍 VERIFICAR PROBLEMA DE MÉTODO DE PAGO
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔍 VERIFICANDO PROBLEMA MÉTODO DE PAGO QR->EFECTIVO...\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // Buscar las últimas 5 ventas para ver el patrón
    $stmt = $pdo->prepare("
        SELECT 
            id,
            fecha,
            monto_total,
            metodo_pago,
            cliente_nombre,
            detalles_json
        FROM ventas 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 ÚLTIMAS 5 VENTAS:\n";
    echo str_repeat("=", 50) . "\n";
    
    foreach ($ventas as $venta) {
        echo "Venta ID: {$venta['id']}\n";
        echo "- Fecha: {$venta['fecha']}\n";
        echo "- Monto: ${$venta['monto_total']}\n";
        echo "- Método: {$venta['metodo_pago']}\n";
        echo "- Cliente: {$venta['cliente_nombre']}\n";
        
        // Analizar detalles JSON
        if (!empty($venta['detalles_json'])) {
            $detalles = json_decode($venta['detalles_json'], true);
            echo "- Detalles: ";
            if (isset($detalles['cart']) && is_array($detalles['cart'])) {
                echo count($detalles['cart']) . " productos\n";
                foreach ($detalles['cart'] as $item) {
                    $nombre = $item['nombre'] ?? $item['name'] ?? 'Producto';
                    $cantidad = $item['cantidad'] ?? $item['quantity'] ?? 1;
                    $precio = $item['precio'] ?? $item['price'] ?? 0;
                    echo "  * {$nombre} x{$cantidad} = ${$precio}\n";
                }
            } else {
                echo "Sin productos válidos\n";
            }
        } else {
            echo "- Detalles: Sin datos\n";
        }
        echo str_repeat("-", 30) . "\n";
    }
    
    // Verificar específicamente el problema QR
    echo "\n🔍 BUSCANDO VENTAS QR RECIENTES...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, monto_total, metodo_pago, cliente_nombre
        FROM ventas 
        WHERE metodo_pago = 'qr' 
        ORDER BY fecha DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $ventas_qr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($ventas_qr)) {
        echo "📱 VENTAS QR ENCONTRADAS:\n";
        foreach ($ventas_qr as $venta) {
            echo "- ID {$venta['id']}: ${$venta['monto_total']} - {$venta['fecha']}\n";
        }
    } else {
        echo "❌ No se encontraron ventas con método 'qr'\n";
    }
    
    // Verificar en el reporte de métodos de pago
    echo "\n📊 VERIFICANDO REPORTE DE MÉTODOS DE PAGO...\n";
    
    $response = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy');
    $data = json_decode($response, true);
    
    if ($data && isset($data['metodos_pago'])) {
        echo "💰 MÉTODOS DE PAGO EN REPORTE:\n";
        foreach ($data['metodos_pago'] as $metodo => $monto) {
            echo "- {$metodo}: ${$monto}\n";
        }
    }
    
    echo "\n🎯 DIAGNÓSTICO:\n";
    echo "1. Verificar si las ventas QR se están guardando con método correcto\n";
    echo "2. Verificar si el reporte está mapeando QR correctamente\n";
    echo "3. Identificar dónde ocurre el mapeo incorrecto\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
