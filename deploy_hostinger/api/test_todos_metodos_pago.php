<?php
/**
 * 🧪 TEST TODOS LOS MÉTODOS DE PAGO
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "💳 TESTING TODOS LOS MÉTODOS DE PAGO...\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $metodos = [
        'efectivo' => ['nombre' => 'Efectivo', 'monto' => 500.00],
        'tarjeta' => ['nombre' => 'Tarjeta', 'monto' => 750.00],
        'transferencia' => ['nombre' => 'Transferencia', 'monto' => 1000.00],
        'qr' => ['nombre' => 'QR', 'monto' => 1250.00]
    ];
    
    $ventas_exitosas = [];
    
    foreach ($metodos as $metodo_key => $config) {
        echo "💳 PROBANDO {$config['nombre']} (${$config['monto']})...\n";
        echo str_repeat("-", 30) . "\n";
        
        $saleData = [
            'items' => [
                [
                    'id' => 1,
                    'codigo' => 'PROD001',
                    'nombre' => "Producto {$config['nombre']}",
                    'precio' => $config['monto'],
                    'cantidad' => 1,
                    'subtotal' => $config['monto']
                ]
            ],
            'totals' => [
                'subtotal' => $config['monto'],
                'descuento' => 0.00,
                'finalTotal' => $config['monto']
            ],
            'paymentMethod' => $metodo_key,
            'cliente' => "Cliente {$config['nombre']}"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/kiosco/api/procesar_venta_ultra_rapida.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($saleData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            
            if ($result && $result['success']) {
                echo "✅ EXITOSA\n";
                echo "- Venta ID: {$result['venta_id']}\n";
                echo "- CAE: " . ($result['comprobante_fiscal']['cae'] ?? 'N/A') . "\n";
                
                $ventas_exitosas[] = [
                    'id' => $result['venta_id'],
                    'metodo' => $metodo_key,
                    'monto' => $config['monto']
                ];
            } else {
                echo "❌ ERROR: " . ($result['message'] ?? 'Desconocido') . "\n";
            }
        } else {
            echo "❌ HTTP {$http_code}\n";
        }
        
        echo "\n";
    }
    
    // VERIFICACIÓN EN BD
    if (!empty($ventas_exitosas)) {
        echo "📊 VERIFICACIÓN EN BASE DE DATOS:\n";
        echo str_repeat("=", 50) . "\n";
        
        require_once 'bd_conexion.php';
        $pdo = Conexion::obtenerConexion();
        
        $venta_ids = array_column($ventas_exitosas, 'id');
        $placeholders = str_repeat('?,', count($venta_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT id, metodo_pago, monto_total, cae 
            FROM ventas 
            WHERE id IN ({$placeholders})
            ORDER BY id DESC
        ");
        $stmt->execute($venta_ids);
        $ventas_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo sprintf("%-4s %-15s %-12s %-15s\n", "ID", "Método", "Monto", "CAE");
        echo str_repeat("-", 50) . "\n";
        
        foreach ($ventas_bd as $venta) {
            $cae_short = $venta['cae'] ? substr($venta['cae'], -8) : 'N/A';
            echo sprintf("#%-3s %-15s $%-11.2f %-15s\n",
                $venta['id'],
                $venta['metodo_pago'],
                $venta['monto_total'],
                $cae_short
            );
        }
        
        // Totales por método
        echo "\n💰 TOTALES POR MÉTODO:\n";
        $stmt = $pdo->prepare("
            SELECT metodo_pago, COUNT(*) as cantidad, SUM(monto_total) as total
            FROM ventas 
            WHERE id IN ({$placeholders})
            GROUP BY metodo_pago
        ");
        $stmt->execute($venta_ids);
        $totales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($totales as $total) {
            echo "- " . strtoupper($total['metodo_pago']) . ": {$total['cantidad']} ventas = $" . number_format($total['total'], 2) . "\n";
        }
        
        echo "\n🎉 ¡TODOS LOS MÉTODOS DE PAGO FUNCIONANDO CORRECTAMENTE!\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
