<?php
/**
 * 🔍 ANÁLISIS DETALLADO DE VENTAS POR MÉTODO DE PAGO
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "📊 ANÁLISIS DETALLADO DE VENTAS POR MÉTODO DE PAGO - HOY\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // 1. RESUMEN POR MÉTODO DE PAGO
    echo "📋 RESUMEN POR MÉTODO DE PAGO (HOY):\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad_ventas,
            SUM(monto_total) as total_monto,
            AVG(monto_total) as promedio_ticket,
            MIN(monto_total) as venta_minima,
            MAX(monto_total) as venta_maxima
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY metodo_pago
        ORDER BY total_monto DESC
    ");
    $stmt->execute();
    $resumen_metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_ventas = 0;
    $total_monto = 0;
    
    foreach ($resumen_metodos as $metodo) {
        $total_ventas += $metodo['cantidad_ventas'];
        $total_monto += $metodo['total_monto'];
        
        echo sprintf("%-15s: %2d ventas | $%8.2f | Promedio: $%6.2f\n", 
            strtoupper($metodo['metodo_pago']),
            $metodo['cantidad_ventas'],
            $metodo['total_monto'],
            $metodo['promedio_ticket']
        );
    }
    
    echo str_repeat("-", 40) . "\n";
    echo sprintf("%-15s: %2d ventas | $%8.2f\n", "TOTAL", $total_ventas, $total_monto);
    
    // 2. DETALLE DE VENTAS QR ESPECÍFICAMENTE
    echo "\n\n📱 DETALLE ESPECÍFICO DE VENTAS QR:\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, monto_total, cliente_nombre, detalles_json
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND metodo_pago = 'qr'
        AND estado IN ('completado', 'completada')
        ORDER BY fecha DESC
    ");
    $stmt->execute();
    $ventas_qr = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($ventas_qr)) {
        echo "Total ventas QR encontradas: " . count($ventas_qr) . "\n\n";
        
        foreach ($ventas_qr as $venta) {
            echo "Venta QR #{$venta['id']}:\n";
            echo "- Fecha: {$venta['fecha']}\n";
            echo "- Monto: $" . number_format($venta['monto_total'], 2) . "\n";
            echo "- Cliente: {$venta['cliente_nombre']}\n";
            
            // Analizar productos
            if (!empty($venta['detalles_json'])) {
                $detalles = json_decode($venta['detalles_json'], true);
                if (isset($detalles['cart']) && is_array($detalles['cart'])) {
                    echo "- Productos:\n";
                    foreach ($detalles['cart'] as $item) {
                        $nombre = $item['nombre'] ?? $item['name'] ?? 'Producto';
                        $cantidad = $item['cantidad'] ?? $item['quantity'] ?? 1;
                        $precio = $item['precio'] ?? $item['price'] ?? 0;
                        echo "  * {$nombre} x{$cantidad} = $" . number_format($precio, 2) . "\n";
                    }
                }
            }
            echo "\n";
        }
    } else {
        echo "❌ No se encontraron ventas QR hoy\n";
    }
    
    // 3. VERIFICAR EN EL REPORTE
    echo "\n📊 VERIFICACIÓN EN REPORTE DE MÉTODOS DE PAGO:\n";
    echo str_repeat("-", 50) . "\n";
    
    $response = file_get_contents('http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy');
    $data = json_decode($response, true);
    
    if ($data && isset($data['metodos_pago'])) {
        echo "Métodos de pago según reporte:\n";
        foreach ($data['metodos_pago'] as $metodo => $monto) {
            echo "- " . ucfirst($metodo) . ": $" . number_format($monto, 2) . "\n";
        }
        
        // Verificar si coinciden los totales
        $total_reporte = array_sum($data['metodos_pago']);
        echo "\nTotal según reporte: $" . number_format($total_reporte, 2) . "\n";
        echo "Total según BD: $" . number_format($total_monto, 2) . "\n";
        
        if (abs($total_reporte - $total_monto) < 0.01) {
            echo "✅ TOTALES COINCIDEN\n";
        } else {
            echo "❌ DISCREPANCIA: $" . number_format(abs($total_reporte - $total_monto), 2) . "\n";
        }
    }
    
    // 4. ANÁLISIS TEMPORAL
    echo "\n⏰ ANÁLISIS TEMPORAL DE VENTAS HOY:\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(fecha) as hora,
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(monto_total) as total
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY HOUR(fecha), metodo_pago
        ORDER BY hora, metodo_pago
    ");
    $stmt->execute();
    $ventas_por_hora = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($ventas_por_hora)) {
        foreach ($ventas_por_hora as $venta_hora) {
            echo sprintf("%02d:00 - %-10s: %d ventas ($%.2f)\n", 
                $venta_hora['hora'],
                $venta_hora['metodo_pago'],
                $venta_hora['cantidad'],
                $venta_hora['total']
            );
        }
    }
    
    echo "\n🎯 CONCLUSIÓN:\n";
    echo "- Verificar si todas las ventas QR están contabilizadas\n";
    echo "- Confirmar que el reporte muestra datos correctos\n";
    echo "- Validar que no haya discrepancias entre BD y reportes\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
