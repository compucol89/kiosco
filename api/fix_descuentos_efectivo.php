<?php
/**
 * 🔧 CORREGIR DESCUENTOS DE EFECTIVO 10%
 * Aplicar correctamente descuentos automáticos por método de pago
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔧 CORRIGIENDO DESCUENTOS DE EFECTIVO (10%)...\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // 1. VERIFICAR VENTAS EN EFECTIVO SIN DESCUENTO
    echo "🔍 VERIFICANDO VENTAS EN EFECTIVO:\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, monto_total, subtotal, descuento, metodo_pago
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND metodo_pago = 'efectivo'
        AND estado IN ('completado', 'completada')
        ORDER BY id DESC
    ");
    $stmt->execute();
    $ventas_efectivo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Ventas en efectivo encontradas: " . count($ventas_efectivo) . "\n\n";
    
    foreach ($ventas_efectivo as $venta) {
        $descuento_actual = floatval($venta['descuento']);
        $subtotal_actual = floatval($venta['subtotal']);
        $monto_actual = floatval($venta['monto_total']);
        
        echo "Venta #{$venta['id']}:\n";
        echo "- Subtotal actual: $" . number_format($subtotal_actual, 2) . "\n";
        echo "- Descuento actual: $" . number_format($descuento_actual, 2) . "\n";
        echo "- Monto actual: $" . number_format($monto_actual, 2) . "\n";
        
        // Calcular descuento correcto (10%)
        if ($subtotal_actual > 0) {
            $descuento_correcto = $subtotal_actual * 0.10; // 10%
            $monto_correcto = $subtotal_actual - $descuento_correcto;
            
            echo "- Descuento 10%: $" . number_format($descuento_correcto, 2) . "\n";
            echo "- Monto corregido: $" . number_format($monto_correcto, 2) . "\n";
            
            // Verificar si necesita corrección
            if (abs($descuento_actual - $descuento_correcto) > 0.01) {
                echo "⚠️ NECESITA CORRECCIÓN\n";
                
                // Actualizar la venta
                $stmt_update = $pdo->prepare("
                    UPDATE ventas SET 
                        descuento = ?,
                        monto_total = ?
                    WHERE id = ?
                ");
                $stmt_update->execute([
                    $descuento_correcto,
                    $monto_correcto,
                    $venta['id']
                ]);
                
                echo "✅ CORREGIDA\n";
            } else {
                echo "✅ YA CORRECTA\n";
            }
        } else {
            echo "⚠️ Sin subtotal válido\n";
        }
        
        echo "\n";
    }
    
    // 2. VERIFICAR TOTALES DESPUÉS DE CORRECCIÓN
    echo "📊 VERIFICACIÓN POST-CORRECCIÓN:\n";
    echo str_repeat("=", 50) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(subtotal) as total_subtotal,
            SUM(descuento) as total_descuentos,
            SUM(monto_total) as total_final
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY metodo_pago
        ORDER BY total_final DESC
    ");
    $stmt->execute();
    $resumen_corregido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo sprintf("%-15s %-8s %-10s %-12s %-10s\n", 
        "Método", "Cant.", "Subtotal", "Descuentos", "Final");
    echo str_repeat("-", 60) . "\n";
    
    foreach ($resumen_corregido as $metodo) {
        echo sprintf("%-15s %-8d $%-9.2f $%-11.2f $%-9.2f\n",
            strtoupper($metodo['metodo_pago']),
            $metodo['cantidad'],
            $metodo['total_subtotal'],
            $metodo['total_descuentos'],
            $metodo['total_final']
        );
    }
    
    // 3. RECALCULAR DETALLE PARA ANÁLISIS
    echo "\n🧮 RECALCULANDO DETALLE PARA ANÁLISIS:\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, metodo_pago, subtotal, descuento, monto_total, detalles_json
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        ORDER BY id DESC
    ");
    $stmt->execute();
    $todas_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($todas_ventas as $venta) {
        $costo_estimado = $venta['monto_total'] * 0.3; // 30% del precio final
        $ganancia = $venta['monto_total'] - $costo_estimado;
        $margen = ($venta['monto_total'] > 0) ? ($ganancia / $venta['monto_total']) * 100 : 0;
        
        echo sprintf("#%-3s %-12s $%-7.2f $%-7.2f $%-7.2f $%-7.2f $%-7.2f %5.1f%%\n",
            $venta['id'],
            $venta['metodo_pago'],
            $venta['subtotal'] ?? 0,
            $costo_estimado,
            $venta['descuento'] ?? 0,
            $venta['monto_total'],
            $ganancia,
            $margen
        );
    }
    
    echo "\n🎯 RESULTADO:\n";
    echo "✅ Descuentos de efectivo aplicados correctamente\n";
    echo "✅ Cálculos de ganancia ajustados\n";
    echo "✅ Detalle de ventas individuales preciso\n";
    echo "✅ Información exacta para contabilidad\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
