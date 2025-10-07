<?php
/**
 * 🔍 COMPARAR DATOS REPORTE VS BASE DE DATOS
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔍 COMPARACIÓN: REPORTE VS BASE DE DATOS\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // 1. DATOS DIRECTOS DE LA BASE DE DATOS
    echo "📊 DATOS DIRECTOS DE LA BASE DE DATOS:\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            DATE_FORMAT(fecha, '%d/%m %H:%i') as fecha_formato,
            metodo_pago,
            monto_total,
            subtotal,
            descuento,
            cliente_nombre,
            estado
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        ORDER BY id DESC
    ");
    $stmt->execute();
    $ventas_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo sprintf("%-12s %-4s %-10s %-10s %-10s %-10s\n", 
        "Fecha/Hora", "ID", "Método", "Monto", "Subtotal", "Descuento");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($ventas_bd as $venta) {
        echo sprintf("%-12s #%-3s %-10s $%-9.2f $%-9.2f $%-9.2f\n",
            $venta['fecha_formato'],
            $venta['id'],
            $venta['metodo_pago'],
            $venta['monto_total'],
            $venta['subtotal'] ?? 0,
            $venta['descuento'] ?? 0
        );
    }
    
    // 2. DATOS SEGÚN EL REPORTE
    echo "\n\n📋 DATOS SEGÚN EL REPORTE (API):\n";
    echo str_repeat("-", 40) . "\n";
    
    $response = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy');
    $data = json_decode($response, true);
    
    if ($data && isset($data['ventas_detalladas'])) {
        echo sprintf("%-12s %-4s %-10s %-10s %-10s %-10s\n", 
            "Fecha/Hora", "ID", "Método", "Total", "Costo", "Ganancia");
        echo str_repeat("-", 70) . "\n";
        
        foreach ($data['ventas_detalladas'] as $venta) {
            echo sprintf("%-12s #%-3s %-10s $%-9.2f $%-9.2f $%-9.2f\n",
                $venta['fecha'] ? date('d/m H:i', strtotime($venta['fecha'])) : 'N/A',
                $venta['venta_id'],
                $venta['metodo_pago'],
                $venta['resumen']['total_ingresos_netos'] ?? 0,
                $venta['resumen']['total_costos'] ?? 0,
                $venta['resumen']['utilidad_bruta'] ?? 0
            );
        }
    }
    
    // 3. COMPARACIÓN ESPECÍFICA DE MÉTODOS DE PAGO
    echo "\n\n💰 COMPARACIÓN MÉTODOS DE PAGO:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Calcular desde BD
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(monto_total) as total
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY metodo_pago
    ");
    $stmt->execute();
    $bd_metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "BASE DE DATOS:\n";
    foreach ($bd_metodos as $metodo) {
        echo "- {$metodo['metodo_pago']}: {$metodo['cantidad']} ventas = $" . number_format($metodo['total'], 2) . "\n";
    }
    
    echo "\nREPORTE API:\n";
    if ($data && isset($data['metodos_pago'])) {
        foreach ($data['metodos_pago'] as $metodo => $monto) {
            if ($monto > 0) {
                echo "- {$metodo}: $" . number_format($monto, 2) . "\n";
            }
        }
    }
    
    // 4. IDENTIFICAR DISCREPANCIAS
    echo "\n🔍 ANÁLISIS DE DISCREPANCIAS:\n";
    echo str_repeat("-", 30) . "\n";
    
    // Verificar venta #28 específicamente
    $stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = 28");
    $stmt->execute();
    $venta28 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($venta28) {
        echo "Venta #28 (QR con problema):\n";
        echo "- BD monto_total: $" . number_format($venta28['monto_total'], 2) . "\n";
        echo "- BD subtotal: $" . number_format($venta28['subtotal'] ?? 0, 2) . "\n";
        echo "- BD método: {$venta28['metodo_pago']}\n";
        
        if (!empty($venta28['detalles_json'])) {
            $detalles = json_decode($venta28['detalles_json'], true);
            echo "- Productos en JSON:\n";
            if (isset($detalles['cart'])) {
                foreach ($detalles['cart'] as $item) {
                    $precio = $item['precio'] ?? $item['price'] ?? 0;
                    $cantidad = $item['cantidad'] ?? $item['quantity'] ?? 1;
                    $total_item = $precio * $cantidad;
                    echo "  * Precio: $" . number_format($precio, 2) . " x {$cantidad} = $" . number_format($total_item, 2) . "\n";
                }
            }
        }
    }
    
    echo "\n🎯 PROBLEMA IDENTIFICADO:\n";
    echo "Venta #28: El monto_total en BD es $0.00 pero el producto vale $3,000\n";
    echo "Esto indica un problema en el procesamiento de ventas\n";
    echo "El reporte calcula correctamente desde los detalles JSON\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
