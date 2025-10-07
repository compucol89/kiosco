<?php
/**
 * 🔍 DEBUG FINANZAS_COMPLETO - IDENTIFICAR PROBLEMA
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔍 DEBUGGING FINANZAS_COMPLETO...\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // Simular exactamente lo que hace finanzas_completo.php
    $periodo = 'hoy';
    $fechaInicio = date('Y-m-d');
    $fechaFin = date('Y-m-d');
    
    echo "📅 PERÍODO: {$fechaInicio} a {$fechaFin}\n\n";
    
    // Query exacta de finanzas_completo.php
    $stmtVentas = $pdo->prepare("
        SELECT 
            v.id,
            v.fecha,
            v.cliente_nombre,
            v.metodo_pago,
            v.subtotal,
            v.descuento,
            v.monto_total,
            v.numero_comprobante,
            v.detalles_json,
            v.estado
        FROM ventas v
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        AND v.estado IN ('completado', 'completada')
        ORDER BY v.fecha DESC
    ");
    $stmtVentas->execute([$fechaInicio, $fechaFin]);
    $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 VENTAS ENCONTRADAS: " . count($ventas) . "\n\n";
    
    // Analizar cada venta
    $total_real = 0;
    $metodos_debug = [];
    
    foreach ($ventas as $venta) {
        echo "Venta #{$venta['id']}:\n";
        echo "  - Método: {$venta['metodo_pago']}\n";
        echo "  - Monto BD: $" . number_format($venta['monto_total'], 2) . "\n";
        echo "  - Subtotal: $" . number_format($venta['subtotal'] ?? 0, 2) . "\n";
        echo "  - Descuento: $" . number_format($venta['descuento'] ?? 0, 2) . "\n";
        
        // Calcular desde JSON si monto es 0
        $monto_calculado = $venta['monto_total'];
        
        if ($venta['monto_total'] == 0 && !empty($venta['detalles_json'])) {
            $detalles = json_decode($venta['detalles_json'], true);
            if ($detalles && isset($detalles['cart'])) {
                $subtotal_json = 0;
                foreach ($detalles['cart'] as $item) {
                    $precio = floatval($item['precio'] ?? $item['price'] ?? 0);
                    $cantidad = floatval($item['cantidad'] ?? $item['quantity'] ?? 1);
                    $subtotal_json += $precio * $cantidad;
                }
                $monto_calculado = $subtotal_json - floatval($venta['descuento']);
                echo "  - Calculado JSON: $" . number_format($monto_calculado, 2) . "\n";
            }
        }
        
        $total_real += $monto_calculado;
        
        $metodo_clean = strtolower($venta['metodo_pago']);
        if (!isset($metodos_debug[$metodo_clean])) {
            $metodos_debug[$metodo_clean] = 0;
        }
        $metodos_debug[$metodo_clean] += $monto_calculado;
        
        echo "  - Monto final: $" . number_format($monto_calculado, 2) . "\n\n";
    }
    
    echo "🎯 RESUMEN FINAL:\n";
    echo "- Total calculado: $" . number_format($total_real, 2) . "\n";
    echo "- Métodos debug:\n";
    foreach ($metodos_debug as $metodo => $monto) {
        echo "  * {$metodo}: $" . number_format($monto, 2) . "\n";
    }
    
    echo "\n💡 CONCLUSIÓN:\n";
    if ($total_real > 7000) {
        echo "✅ Los datos están correctos\n";
        echo "El problema está en finanzas_completo.php no usando monto_total actualizado\n";
    } else {
        echo "❌ Hay un problema en el cálculo\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
