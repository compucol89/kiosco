<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DE finanzas_completo.php ===\n\n";

$url = "http://localhost/kiosco/api/finanzas_completo.php?periodo=hoy&_t=" . time();
$response = file_get_contents($url);

if ($response === false) {
    echo "❌ Error al obtener respuesta\n";
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Respuesta no es JSON válido\n";
    echo "Respuesta recibida:\n";
    echo $response;
    exit;
}

echo "✅ Respuesta JSON válida\n\n";
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n\n";

if (isset($data['componente_1_ventas_ganancias'])) {
    echo "📊 COMPONENTE 1: Ventas y Ganancias\n";
    echo str_repeat("-", 60) . "\n";
    
    $c1 = $data['componente_1_ventas_ganancias'];
    
    if (isset($c1['tarjeta_1_ganancia_neta'])) {
        echo "Ganancia Neta: $" . $c1['tarjeta_1_ganancia_neta']['valor_principal'] . "\n";
    }
    
    if (isset($c1['tarjeta_2_ventas_brutas'])) {
        echo "Ventas Netas: $" . $c1['tarjeta_2_ventas_brutas']['valor_principal'] . "\n";
    }
    
    if (isset($c1['tarjeta_3_descuentos'])) {
        echo "Descuentos: $" . $c1['tarjeta_3_descuentos']['valor_principal'] . "\n";
    }
    echo "\n";
}

if (isset($data['componente_4_detalle_ventas'])) {
    echo "📋 COMPONENTE 4: Detalle Ventas\n";
    echo str_repeat("-", 60) . "\n";
    
    $ventas = $data['componente_4_detalle_ventas']['ventas_individuales'] ?? [];
    echo "Total ventas: " . count($ventas) . "\n";
    
    if (count($ventas) > 0) {
        echo "\nPrimeras 3 ventas:\n";
        foreach (array_slice($ventas, 0, 3) as $v) {
            echo "  - #{$v['referencia']}: \${$v['precio_final']} - Ganancia: \${$v['ganancia_neta']}\n";
        }
    }
    echo "\n";
}

if (isset($data['componente_3_metodos_pago'])) {
    echo "💳 COMPONENTE 3: Métodos de Pago\n";
    echo str_repeat("-", 60) . "\n";
    
    $metodos = $data['componente_3_metodos_pago'];
    foreach (['efectivo', 'tarjeta', 'transferencia', 'qr'] as $metodo) {
        if (isset($metodos[$metodo])) {
            $total = $metodos[$metodo]['total_ventas'] ?? 0;
            echo ucfirst($metodo) . ": $$total\n";
        }
    }
}

echo "\n=== FIN DEL TEST ===\n";
?>


