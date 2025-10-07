<?php
/**
 * 🔧 CORRECCIÓN DE VENTAS CON MONTO $0.00
 * Recalcula montos desde detalles JSON para datos precisos
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔧 CORRIGIENDO VENTAS CON MONTO $0.00...\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    $pdo->beginTransaction();
    
    // Buscar ventas con monto $0.00 pero con productos en JSON
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, metodo_pago, monto_total, subtotal, descuento, detalles_json
        FROM ventas 
        WHERE monto_total = 0.00 
        AND detalles_json IS NOT NULL 
        AND detalles_json != '' 
        AND detalles_json != '[]'
        ORDER BY id DESC
    ");
    $stmt->execute();
    $ventas_problematicas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🔍 VENTAS PROBLEMÁTICAS ENCONTRADAS: " . count($ventas_problematicas) . "\n\n";
    
    $corregidas = 0;
    $errores = 0;
    
    foreach ($ventas_problematicas as $venta) {
        echo "Procesando Venta #{$venta['id']}...\n";
        
        try {
            // Parsear detalles JSON
            $detalles = json_decode($venta['detalles_json'], true);
            
            if (!$detalles) {
                echo "  ❌ JSON inválido\n";
                $errores++;
                continue;
            }
            
            $monto_calculado = 0;
            $subtotal_calculado = 0;
            $productos_encontrados = 0;
            
            // Calcular desde cart o items
            $items = $detalles['cart'] ?? $detalles['items'] ?? [];
            
            if (is_array($items)) {
                foreach ($items as $item) {
                    $precio = floatval($item['precio'] ?? $item['price'] ?? 0);
                    $cantidad = floatval($item['cantidad'] ?? $item['quantity'] ?? 1);
                    $subtotal_item = $precio * $cantidad;
                    
                    $subtotal_calculado += $subtotal_item;
                    $productos_encontrados++;
                    
                    $nombre = $item['nombre'] ?? $item['name'] ?? 'Producto';
                    echo "  - {$nombre}: $" . number_format($precio, 2) . " x {$cantidad} = $" . number_format($subtotal_item, 2) . "\n";
                }
            }
            
            // Aplicar descuento existente
            $descuento_actual = floatval($venta['descuento']);
            $monto_calculado = $subtotal_calculado - $descuento_actual;
            
            if ($productos_encontrados > 0 && $monto_calculado > 0) {
                // Actualizar la venta
                $stmt_update = $pdo->prepare("
                    UPDATE ventas SET 
                        subtotal = ?,
                        monto_total = ?
                    WHERE id = ?
                ");
                
                $stmt_update->execute([
                    $subtotal_calculado,
                    $monto_calculado,
                    $venta['id']
                ]);
                
                echo "  ✅ CORREGIDA: Subtotal $" . number_format($subtotal_calculado, 2) . 
                     " - Descuento $" . number_format($descuento_actual, 2) . 
                     " = Total $" . number_format($monto_calculado, 2) . "\n";
                $corregidas++;
            } else {
                echo "  ⚠️ No se pudo calcular monto (sin productos válidos)\n";
                $errores++;
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
            $errores++;
        }
        
        echo "\n";
    }
    
    $pdo->commit();
    
    echo str_repeat("=", 60) . "\n";
    echo "🎯 RESUMEN DE CORRECCIÓN:\n";
    echo "- Ventas analizadas: " . count($ventas_problematicas) . "\n";
    echo "- Ventas corregidas: {$corregidas} ✅\n";
    echo "- Errores: {$errores}\n\n";
    
    if ($corregidas > 0) {
        echo "🎉 ¡CORRECCIÓN COMPLETADA!\n";
        echo "✅ Los montos ahora reflejan los precios reales\n";
        echo "✅ Los reportes serán más precisos\n";
        echo "✅ Los métodos de pago mostrarán totales correctos\n";
    }
    
    // Verificar resultado final
    echo "\n📊 VERIFICACIÓN POST-CORRECCIÓN:\n";
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(monto_total) as total
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        GROUP BY metodo_pago
        ORDER BY total DESC
    ");
    $stmt->execute();
    $metodos_corregidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($metodos_corregidos as $metodo) {
        echo "- " . strtoupper($metodo['metodo_pago']) . ": {$metodo['cantidad']} ventas = $" . number_format($metodo['total'], 2) . "\n";
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
}
?>
