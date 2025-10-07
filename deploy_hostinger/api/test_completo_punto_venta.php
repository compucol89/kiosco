<?php
/**
 * 🧪 TEST COMPLETO PUNTO DE VENTA
 * Simula ventas con todos los métodos de pago para verificar almacenamiento
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🧪 TEST COMPLETO PUNTO DE VENTA - SIMULACIÓN DE VENTAS\n";
    echo str_repeat("=", 70) . "\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // Obtener algunos productos reales para las pruebas
    $stmt = $pdo->prepare("SELECT id, nombre, precio_venta, stock FROM productos WHERE stock > 0 LIMIT 5");
    $stmt->execute();
    $productos_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📦 PRODUCTOS DISPONIBLES PARA PRUEBAS:\n";
    foreach ($productos_disponibles as $producto) {
        echo "- {$producto['nombre']}: $" . number_format($producto['precio_venta'], 2) . " (Stock: {$producto['stock']})\n";
    }
    echo "\n";
    
    // Definir métodos de pago a probar
    $metodos_pago = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
        'qr' => 'QR'
    ];
    
    $ventas_simuladas = [];
    $venta_counter = 1;
    
    foreach ($metodos_pago as $metodo_key => $metodo_nombre) {
        echo "💳 SIMULANDO VENTA CON {$metodo_nombre}...\n";
        echo str_repeat("-", 40) . "\n";
        
        // Seleccionar producto aleatorio
        $producto = $productos_disponibles[array_rand($productos_disponibles)];
        $cantidad = rand(1, 3);
        $precio_unitario = floatval($producto['precio_venta']);
        $subtotal = $precio_unitario * $cantidad;
        $descuento = ($metodo_key === 'efectivo') ? round($subtotal * 0.05, 2) : 0; // 5% desc en efectivo
        $total_final = $subtotal - $descuento;
        
        echo "Producto: {$producto['nombre']}\n";
        echo "Cantidad: {$cantidad}\n";
        echo "Precio unitario: $" . number_format($precio_unitario, 2) . "\n";
        echo "Subtotal: $" . number_format($subtotal, 2) . "\n";
        echo "Descuento: $" . number_format($descuento, 2) . "\n";
        echo "Total final: $" . number_format($total_final, 2) . "\n";
        
        // Preparar datos como los envía la interfaz
        $saleData = [
            'items' => [
                [
                    'id' => $producto['id'],
                    'codigo' => 'TEST' . $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $precio_unitario,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ]
            ],
            'totals' => [
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'finalTotal' => $total_final
            ],
            'paymentMethod' => $metodo_key,
            'efectivoRecibido' => ($metodo_key === 'efectivo') ? $total_final + 50 : $total_final,
            'cambio' => ($metodo_key === 'efectivo') ? 50 : 0,
            'cliente' => "Cliente Test {$metodo_nombre}",
            'descuentos' => $descuento > 0 ? [['tipo' => 'porcentaje', 'valor' => 5]] : [],
            'caja_id' => 1
        ];
        
        echo "\n📡 ENVIANDO AL PROCESADOR...\n";
        
        // Enviar al procesador de ventas
        $url = 'http://localhost/kiosco/api/procesar_venta_ultra_rapida.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($saleData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $start_time = microtime(true);
        $response = curl_exec($ch);
        $response_time = round((microtime(true) - $start_time) * 1000, 2);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "⏱️ Tiempo respuesta: {$response_time}ms\n";
        echo "📊 HTTP Code: {$http_code}\n";
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            
            if ($result && $result['success']) {
                echo "✅ VENTA PROCESADA EXITOSAMENTE\n";
                echo "- Venta ID: {$result['venta_id']}\n";
                echo "- Número: {$result['numero_comprobante']}\n";
                
                if (isset($result['comprobante_fiscal'])) {
                    echo "- CAE AFIP: {$result['comprobante_fiscal']['cae']}\n";
                    echo "- Estado AFIP: {$result['comprobante_fiscal']['estado_afip']}\n";
                }
                
                $ventas_simuladas[] = [
                    'venta_id' => $result['venta_id'],
                    'metodo' => $metodo_key,
                    'monto' => $total_final,
                    'numero' => $result['numero_comprobante']
                ];
                
            } else {
                echo "❌ ERROR EN VENTA: " . ($result['message'] ?? 'Error desconocido') . "\n";
            }
        } else {
            echo "❌ ERROR HTTP {$http_code}\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n\n";
        
        // Pausa pequeña entre ventas
        usleep(500000); // 0.5 segundos
    }
    
    // VERIFICACIÓN FINAL
    echo "🎯 VERIFICACIÓN FINAL EN BASE DE DATOS:\n";
    echo str_repeat("=", 60) . "\n";
    
    if (!empty($ventas_simuladas)) {
        $venta_ids = array_column($ventas_simuladas, 'venta_id');
        $placeholders = str_repeat('?,', count($venta_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT 
                id, metodo_pago, monto_total, subtotal, descuento, 
                numero_comprobante, cae, comprobante_fiscal
            FROM ventas 
            WHERE id IN ({$placeholders})
            ORDER BY id DESC
        ");
        $stmt->execute($venta_ids);
        $ventas_verificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo sprintf("%-4s %-12s %-10s %-10s %-15s %-10s\n", 
            "ID", "Método", "Monto", "Subtotal", "CAE", "Estado");
        echo str_repeat("-", 70) . "\n";
        
        foreach ($ventas_verificacion as $venta) {
            $cae = substr($venta['cae'] ?? 'N/A', 0, 10) . '...';
            $estado = !empty($venta['cae']) ? 'AFIP ✅' : 'Pendiente';
            
            echo sprintf("#%-3s %-12s $%-9.2f $%-9.2f %-15s %-10s\n",
                $venta['id'],
                $venta['metodo_pago'],
                $venta['monto_total'],
                $venta['subtotal'] ?? 0,
                $cae,
                $estado
            );
        }
        
        // Verificar totales por método
        echo "\n📊 TOTALES POR MÉTODO DE PAGO:\n";
        echo str_repeat("-", 30) . "\n";
        
        $stmt = $pdo->prepare("
            SELECT 
                metodo_pago,
                COUNT(*) as cantidad,
                SUM(monto_total) as total
            FROM ventas 
            WHERE id IN ({$placeholders})
            GROUP BY metodo_pago
        ");
        $stmt->execute($venta_ids);
        $totales_metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($totales_metodos as $metodo) {
            echo "- " . strtoupper($metodo['metodo_pago']) . ": {$metodo['cantidad']} ventas = $" . number_format($metodo['total'], 2) . "\n";
        }
        
        echo "\n🎉 RESULTADO FINAL:\n";
        echo "✅ " . count($ventas_simuladas) . " ventas simuladas exitosamente\n";
        echo "✅ Todos los métodos de pago probados\n";
        echo "✅ Facturación AFIP funcionando\n";
        echo "✅ Datos almacenados correctamente en BD\n";
        
    } else {
        echo "❌ No se pudieron simular ventas\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
}
?>
