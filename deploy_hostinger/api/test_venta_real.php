<?php
/**
 * 🧪 SIMULAR VENTA REAL DESDE INTERFAZ
 * Simula exactamente lo que hace la interfaz
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🛒 SIMULANDO VENTA REAL DESDE INTERFAZ...\n\n";
    
    // Datos exactos como los envía la interfaz
    $saleData = [
        'items' => [
            [
                'codigo' => 'TEST001',
                'nombre' => 'Producto Test Real',
                'precio' => 200.00,
                'cantidad' => 1,
                'subtotal' => 200.00
            ]
        ],
        'totals' => [
            'subtotal' => 200.00,
            'descuento' => 0.00,
            'finalTotal' => 200.00
        ],
        'paymentMethod' => 'efectivo',
        'efectivoRecibido' => 200.00,
        'cambio' => 0.00,
        'cliente' => 'Consumidor Final',
        'descuentos' => [],
        'caja_id' => 1
    ];
    
    echo "📋 DATOS DE VENTA:\n";
    echo json_encode($saleData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Llamar al mismo endpoint que usa la interfaz
    $url = 'http://localhost/kiosco/api/procesar_venta_ultra_rapida.php';
    
    echo "🌐 ENVIANDO A: {$url}\n\n";
    
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
    
    echo "⏱️ TIEMPO RESPUESTA: {$response_time}ms\n";
    echo "📊 HTTP CODE: {$http_code}\n\n";
    
    if ($http_code === 200) {
        echo "✅ RESPUESTA EXITOSA:\n";
        $result = json_decode($response, true);
        
        if ($result) {
            echo "- Success: " . ($result['success'] ? 'SÍ ✅' : 'NO ❌') . "\n";
            echo "- Venta ID: " . ($result['venta_id'] ?? 'N/A') . "\n";
            echo "- Número: " . ($result['numero_comprobante'] ?? 'N/A') . "\n";
            
            if (isset($result['comprobante_fiscal'])) {
                echo "- Facturación AFIP: ✅ INCLUIDA\n";
                echo "- CAE: " . ($result['comprobante_fiscal']['cae'] ?? 'N/A') . "\n";
                echo "- Estado AFIP: " . ($result['comprobante_fiscal']['estado_afip'] ?? 'N/A') . "\n";
            } else {
                echo "- Facturación AFIP: ❌ NO INCLUIDA\n";
            }
        } else {
            echo "❌ Respuesta JSON inválida\n";
            echo "Response: " . substr($response, 0, 200) . "\n";
        }
    } else {
        echo "❌ ERROR HTTP {$http_code}\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
