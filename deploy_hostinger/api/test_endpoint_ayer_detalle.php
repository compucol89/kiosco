<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "http://localhost/kiosco/api/finanzas_completo.php?periodo=ayer&_t=" . time();
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "=== RESPUESTA COMPLETA DEL ENDPOINT ===\n\n";

if ($data) {
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n\n";
    
    if (isset($data['componente_4_detalle_ventas'])) {
        $c4 = $data['componente_4_detalle_ventas'];
        echo "📋 Componente 4 - Detalle Ventas:\n";
        echo "   ventas_individuales: " . count($c4['ventas_individuales'] ?? []) . "\n";
        echo "   totales: " . json_encode($c4['totales'] ?? [], JSON_PRETTY_PRINT) . "\n\n";
    }
    
    if (isset($data['periodo'])) {
        echo "📅 Período:\n";
        echo json_encode($data['periodo'], JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // Ver primeros 500 caracteres de la respuesta
    echo "📄 Respuesta completa (primeros 1000 chars):\n";
    echo substr(json_encode($data, JSON_PRETTY_PRINT), 0, 1000) . "\n...";
} else {
    echo "❌ No es JSON válido\n";
    echo "Respuesta:\n";
    echo substr($response, 0, 500);
}
?>
