<?php
/**
 * 🏦 TEST MOVIMIENTOS DE CAJA CORREGIDO
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "🏦 TEST MOVIMIENTOS DE CAJA - ENTRADA Y SALIDA\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // 1. VERIFICAR ESTADO INICIAL
    echo "📊 ESTADO INICIAL:\n";
    $response_inicial = file_get_contents('http://localhost/kiosco/api/gestion_caja_completa.php?accion=estado_caja&usuario_id=1');
    $estado_inicial = json_decode($response_inicial, true);
    
    if ($estado_inicial && $estado_inicial['success']) {
        $turno_inicial = $estado_inicial['turno'];
        echo "- Efectivo teórico inicial: $" . number_format($turno_inicial['efectivo_teorico'], 2) . "\n";
        echo "- Entradas actuales: $" . number_format($turno_inicial['entradas_efectivo'] ?? 0, 2) . "\n";
        echo "- Salidas actuales: $" . number_format($turno_inicial['salidas_efectivo'] ?? 0, 2) . "\n\n";
    }
    
    // 2. MOVIMIENTO DE ENTRADA
    echo "💰 REGISTRANDO ENTRADA DE EFECTIVO (+$500)...\n";
    echo str_repeat("-", 40) . "\n";
    
    $entrada_data = [
        'tipo' => 'ingreso',
        'categoria' => 'venta_externa',
        'monto' => 500.00,
        'descripcion' => 'Venta externa de productos - TEST',
        'referencia' => 'FACT-TEST-001'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/kiosco/api/gestion_caja_completa.php?accion=registrar_movimiento');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($entrada_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📡 Resultado entrada:\n";
    echo "- HTTP Code: {$http_code}\n";
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✅ ENTRADA REGISTRADA\n";
            echo "- Movimiento ID: " . ($result['movimiento_id'] ?? 'N/A') . "\n";
        } else {
            echo "❌ Error: " . ($result['error'] ?? 'Desconocido') . "\n";
            echo "Response: " . substr($response, 0, 300) . "\n";
        }
    } else {
        echo "❌ HTTP Error\n";
        echo "Response: " . substr($response, 0, 300) . "\n";
    }
    
    // Esperar
    sleep(1);
    
    // 3. MOVIMIENTO DE SALIDA
    echo "\n💸 REGISTRANDO SALIDA DE EFECTIVO (-$200)...\n";
    echo str_repeat("-", 40) . "\n";
    
    $salida_data = [
        'tipo' => 'egreso',
        'categoria' => 'gastos_operativos',
        'monto' => 200.00,
        'descripcion' => 'Pago de servicios - TEST',
        'referencia' => 'SERV-TEST-001'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/kiosco/api/gestion_caja_completa.php?accion=registrar_movimiento');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($salida_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📡 Resultado salida:\n";
    echo "- HTTP Code: {$http_code}\n";
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✅ SALIDA REGISTRADA\n";
            echo "- Movimiento ID: " . ($result['movimiento_id'] ?? 'N/A') . "\n";
        } else {
            echo "❌ Error: " . ($result['error'] ?? 'Desconocido') . "\n";
            echo "Response: " . substr($response, 0, 300) . "\n";
        }
    } else {
        echo "❌ HTTP Error\n";
        echo "Response: " . substr($response, 0, 300) . "\n";
    }
    
    // Esperar
    sleep(1);
    
    // 4. VERIFICAR ESTADO FINAL
    echo "\n🔍 VERIFICACIÓN ESTADO FINAL:\n";
    echo str_repeat("=", 50) . "\n";
    
    $response_final = file_get_contents('http://localhost/kiosco/api/gestion_caja_completa.php?accion=estado_caja&usuario_id=1&_t=' . time());
    $estado_final = json_decode($response_final, true);
    
    if ($estado_final && $estado_final['success']) {
        $turno_final = $estado_final['turno'];
        
        echo "💰 ESTADO DESPUÉS DE MOVIMIENTOS:\n";
        echo "- Apertura: $" . number_format($turno_final['monto_apertura'], 2) . "\n";
        echo "- Entradas: $" . number_format($turno_final['entradas_efectivo'] ?? 0, 2) . "\n";
        echo "- Salidas: $" . number_format($turno_final['salidas_efectivo'] ?? 0, 2) . "\n";
        echo "- Ventas efectivo: $" . number_format($turno_final['ventas_efectivo_reales'], 2) . "\n";
        echo "- Efectivo teórico: $" . number_format($turno_final['efectivo_teorico'], 2) . "\n\n";
        
        // Comparar con estado inicial
        if (isset($estado_inicial['turno'])) {
            $inicial = $estado_inicial['turno'];
            $cambio_entradas = ($turno_final['entradas_efectivo'] ?? 0) - ($inicial['entradas_efectivo'] ?? 0);
            $cambio_salidas = ($turno_final['salidas_efectivo'] ?? 0) - ($inicial['salidas_efectivo'] ?? 0);
            $cambio_teorico = $turno_final['efectivo_teorico'] - $inicial['efectivo_teorico'];
            
            echo "📈 CAMBIOS DETECTADOS:\n";
            echo "- Entradas: " . ($cambio_entradas >= 0 ? '+' : '') . "$" . number_format($cambio_entradas, 2) . "\n";
            echo "- Salidas: " . ($cambio_salidas >= 0 ? '+' : '') . "$" . number_format($cambio_salidas, 2) . "\n";
            echo "- Efectivo teórico: " . ($cambio_teorico >= 0 ? '+' : '') . "$" . number_format($cambio_teorico, 2) . "\n\n";
            
            if (abs($cambio_entradas - 500) < 0.01 && abs($cambio_salidas - 200) < 0.01) {
                echo "✅ MOVIMIENTOS REGISTRADOS CORRECTAMENTE\n";
            } else {
                echo "⚠️ Los movimientos no se reflejan como esperado\n";
            }
        }
    }
    
    echo "\n🎯 CONCLUSIÓN:\n";
    echo "Verificar si los movimientos se registran correctamente\n";
    echo "y si el efectivo teórico se actualiza en tiempo real\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
