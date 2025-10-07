<?php
header('Content-Type: text/plain; charset=utf-8');

$url = "http://localhost/kiosco/api/reportes_financieros_precisos.php?periodo=hoy&_t=" . time();
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "=== TEST REPORTES FINANCIEROS PRECISOS ===\n\n";

if (!$data) {
    echo "❌ No es JSON válido\n";
    exit;
}

echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n\n";

if (isset($data['resumen_general'])) {
    $r = $data['resumen_general'];
    echo "📊 RESUMEN GENERAL:\n";
    echo "   Total ventas: {$r['total_ventas']}\n";
    echo "   Ingresos netos: \${$r['total_ingresos_netos']}\n";
    echo "   Utilidad bruta: \${$r['total_utilidad_bruta']}\n\n";
}

if (isset($data['ventas_detalladas'])) {
    echo "📋 VENTAS DETALLADAS:\n";
    echo "   Total: " . count($data['ventas_detalladas']) . " ventas\n\n";
    
    foreach ($data['ventas_detalladas'] as $v) {
        echo "   Venta #{$v['venta_id']}: {$v['metodo_pago']} - \${$v['resumen']['monto_total_registrado']}\n";
    }
}
?>


