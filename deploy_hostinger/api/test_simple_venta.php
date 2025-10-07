<?php
/**
 * 🧪 TEST SIMPLE DE VENTA
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🧪 TEST SIMPLE DE VENTA...\n\n";
    
    // Datos mínimos para una venta
    $saleData = [
        'items' => [
            [
                'id' => 1,
                'codigo' => 'TEST001',
                'nombre' => 'Producto Test',
                'precio' => 100.00,
                'cantidad' => 1,
                'subtotal' => 100.00
            ]
        ],
        'totals' => [
            'subtotal' => 100.00,
            'descuento' => 0.00,
            'finalTotal' => 100.00
        ],
        'paymentMethod' => 'efectivo',
        'cliente' => 'Test Cliente'
    ];
    
    echo "📋 DATOS DE VENTA:\n";
    echo json_encode($saleData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "🌐 ENVIANDO AL PROCESADOR...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/kiosco/api/procesar_venta_ultra_rapida.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($saleData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📊 HTTP Code: {$http_code}\n";
    echo "📄 Response: " . substr($response, 0, 500) . "\n";
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result) {
            echo "\n✅ RESPUESTA PARSEADA:\n";
            echo "- Success: " . ($result['success'] ? 'SÍ' : 'NO') . "\n";
            if (isset($result['venta_id'])) {
                echo "- Venta ID: {$result['venta_id']}\n";
            }
            if (isset($result['message'])) {
                echo "- Mensaje: {$result['message']}\n";
            }
        }
    } else {
        echo "\n❌ ERROR HTTP {$http_code}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
