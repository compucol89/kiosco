<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DE ENDPOINTS DE ANÁLISIS ===\n\n";

$endpoints = [
    'finanzas_completo.php',
    'finanzas_completo_corregido.php',
    'reportes_financieros_precisos.php',
    'reportes.php'
];

foreach ($endpoints as $endpoint) {
    echo "📊 Testing: $endpoint\n";
    echo str_repeat("-", 60) . "\n";
    
    $url = "http://localhost/kiosco/api/$endpoint?periodo=hoy";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        echo "   ❌ No responde o no existe\n\n";
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "   ❌ Respuesta no es JSON válido\n\n";
        continue;
    }
    
    echo "   ✅ Responde correctamente\n";
    
    // Buscar datos de ventas
    if (isset($data['componente_1_ventas_ganancias'])) {
        $ganancia = $data['componente_1_ventas_ganancias']['tarjeta_1_ganancia_neta']['valor_principal'] ?? 0;
        $ventas = $data['componente_1_ventas_ganancias']['tarjeta_2_ventas_brutas']['valor_principal'] ?? 0;
        echo "   💰 Ganancia Neta: $$ganancia\n";
        echo "   💵 Ventas Netas: $$ventas\n";
    }
    
    if (isset($data['ganancia_neta_total'])) {
        echo "   💰 Ganancia Neta: $" . $data['ganancia_neta_total'] . "\n";
    }
    
    if (isset($data['ventas_detalladas'])) {
        $count = count($data['ventas_detalladas']);
        echo "   📋 Ventas detalladas: $count registros\n";
    }
    
    if (isset($data['detalle_ventas'])) {
        $count = count($data['detalle_ventas']);
        echo "   📋 Detalle ventas: $count registros\n";
    }
    
    echo "\n";
}

echo "=== FIN DEL TEST ===\n";
?>






