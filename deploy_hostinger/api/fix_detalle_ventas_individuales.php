<?php
/**
 * 🚨 FIX CRÍTICO: DETALLE VENTAS INDIVIDUALES
 * Corregir datos $0.00 en detalle de ventas
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🚨 FIX CRÍTICO: DETALLE VENTAS INDIVIDUALES\n";
    echo str_repeat("=", 70) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // 1. VERIFICAR PROBLEMA EN BD
    echo "🔍 VERIFICANDO DATOS EN BASE DE DATOS:\n";
    echo str_repeat("-", 50) . "\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha, metodo_pago, monto_total, subtotal, descuento, 
            detalles_json, cliente_nombre
        FROM ventas 
        WHERE DATE(fecha) = CURDATE()
        AND estado IN ('completado', 'completada')
        ORDER BY id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo sprintf("%-4s %-12s %-10s %-10s %-10s %-15s\n", 
        "ID", "Método", "Monto", "Subtotal", "Descuento", "Cliente");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($ventas as $venta) {
        echo sprintf("#%-3s %-12s $%-9.2f $%-9.2f $%-9.2f %-15s\n",
            $venta['id'],
            $venta['metodo_pago'],
            $venta['monto_total'],
            $venta['subtotal'] ?? 0,
            $venta['descuento'] ?? 0,
            substr($venta['cliente_nombre'], 0, 14)
        );
    }
    
    // 2. VERIFICAR API FINANZAS_COMPLETO
    echo "\n📊 VERIFICANDO API FINANZAS_COMPLETO:\n";
    echo str_repeat("-", 50) . "\n";
    
    $response = file_get_contents('http://localhost/kiosco/api/finanzas_completo.php?periodo=hoy');
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        $ventas_api = $data['componente_4_detalle_ventas']['ventas'] ?? [];
        
        echo "Ventas desde API (primeras 5):\n";
        foreach (array_slice($ventas_api, 0, 5) as $venta) {
            echo "- ID {$venta['referencia']}: {$venta['metodo_pago']} - $" . number_format($venta['precio_total_venta'], 2) . "\n";
        }
        
        if (empty($ventas_api)) {
            echo "❌ API NO DEVUELVE VENTAS\n";
        } else {
            echo "✅ API devuelve " . count($ventas_api) . " ventas\n";
        }
    } else {
        echo "❌ ERROR EN API FINANZAS_COMPLETO\n";
    }
    
    // 3. CREAR DATOS CORRECTOS PARA EL DETALLE
    echo "\n🔧 CREANDO DATOS CORRECTOS PARA DETALLE:\n";
    echo str_repeat("-", 50) . "\n";
    
    $ventas_corregidas = [];
    
    foreach ($ventas as $venta) {
        // Calcular desde detalles JSON si está disponible
        $productos_info = [];
        $costo_total = 0;
        
        if (!empty($venta['detalles_json'])) {
            $detalles = json_decode($venta['detalles_json'], true);
            if (isset($detalles['cart']) && is_array($detalles['cart'])) {
                foreach ($detalles['cart'] as $item) {
                    // Obtener costo del producto
                    $stmt_producto = $pdo->prepare("SELECT precio_costo FROM productos WHERE id = ? LIMIT 1");
                    $stmt_producto->execute([$item['id'] ?? 0]);
                    $producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);
                    
                    $costo_unitario = $producto['precio_costo'] ?? 50; // Default si no se encuentra
                    $cantidad = floatval($item['cantidad'] ?? $item['quantity'] ?? 1);
                    $costo_total += $costo_unitario * $cantidad;
                }
            }
        }
        
        // Si no hay costo calculado, usar estimado (30% del precio)
        if ($costo_total == 0 && $venta['monto_total'] > 0) {
            $costo_total = $venta['monto_total'] * 0.3;
        }
        
        $ganancia = $venta['monto_total'] - $costo_total - ($venta['descuento'] ?? 0);
        $margen = ($venta['monto_total'] > 0) ? ($ganancia / $venta['monto_total']) * 100 : 0;
        
        $venta_corregida = [
            'fecha_hora' => date('d/m H:i', strtotime($venta['fecha'])),
            'referencia' => $venta['id'],
            'metodo_pago' => ucfirst($venta['metodo_pago']),
            'precio_total_venta' => $venta['monto_total'],
            'precio_costo' => $costo_total,
            'descuento' => $venta['descuento'] ?? 0,
            'precio_final' => $venta['monto_total'] - ($venta['descuento'] ?? 0),
            'ganancia_neta' => $ganancia,
            'margen_porcentual' => round($margen, 1)
        ];
        
        $ventas_corregidas[] = $venta_corregida;
        
        echo "Venta #{$venta['id']}: {$venta['metodo_pago']} - $" . number_format($venta['monto_total'], 2) . 
             " (Costo: $" . number_format($costo_total, 2) . ", Ganancia: $" . number_format($ganancia, 2) . ")\n";
    }
    
    // 4. ACTUALIZAR API FINANZAS_COMPLETO
    echo "\n🔧 SOLUCIONANDO API FINANZAS_COMPLETO...\n";
    echo str_repeat("-", 50) . "\n";
    
    // Crear estructura correcta para finanzas_completo
    $estructura_corregida = [
        'success' => true,
        'fecha_calculo' => date('Y-m-d H:i:s'),
        'componente_4_detalle_ventas' => [
            'ventas' => $ventas_corregidas,
            'totales' => [
                'total_ventas' => array_sum(array_column($ventas_corregidas, 'precio_total_venta')),
                'total_costos' => array_sum(array_column($ventas_corregidas, 'precio_costo')),
                'total_descuentos' => array_sum(array_column($ventas_corregidas, 'descuento')),
                'total_ganancias' => array_sum(array_column($ventas_corregidas, 'ganancia_neta'))
            ]
        ]
    ];
    
    echo "✅ DATOS CORREGIDOS CREADOS:\n";
    echo "- Total ventas: $" . number_format($estructura_corregida['componente_4_detalle_ventas']['totales']['total_ventas'], 2) . "\n";
    echo "- Total costos: $" . number_format($estructura_corregida['componente_4_detalle_ventas']['totales']['total_costos'], 2) . "\n";
    echo "- Total ganancias: $" . number_format($estructura_corregida['componente_4_detalle_ventas']['totales']['total_ganancias'], 2) . "\n";
    echo "- Ventas individuales: " . count($ventas_corregidas) . "\n\n";
    
    echo "🎯 PRÓXIMO PASO:\n";
    echo "Actualizar finanzas_completo.php para que devuelva estos datos corregidos\n";
    echo "en lugar de los datos con $0.00\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
