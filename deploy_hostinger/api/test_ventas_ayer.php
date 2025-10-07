<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== DIAGNÓSTICO VENTAS DE AYER ===\n\n";

$pdo = Conexion::obtenerConexion();

// Ver todas las ventas con sus fechas
echo "📊 TODAS LAS VENTAS EN LA BD:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("SELECT id, DATE(fecha) as fecha_solo, fecha, metodo_pago, monto_total FROM ventas ORDER BY id DESC LIMIT 20");
$ventas = $stmt->fetchAll();

foreach ($ventas as $v) {
    echo "Venta #{$v['id']}: {$v['fecha']} (Fecha: {$v['fecha_solo']}) - {$v['metodo_pago']} - \${$v['monto_total']}\n";
}

echo "\n\n📅 FECHAS IMPORTANTES:\n";
echo str_repeat("-", 80) . "\n";
echo "Hoy: " . date('Y-m-d') . "\n";
echo "Ayer: " . date('Y-m-d', strtotime('-1 day')) . "\n\n";

// Buscar ventas de ayer
echo "🔍 VENTAS DE AYER:\n";
echo str_repeat("-", 80) . "\n";
$fecha_ayer = date('Y-m-d', strtotime('-1 day'));
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = ?");
$stmt->execute([$fecha_ayer]);
$count = $stmt->fetch();

echo "Total ventas de ayer ($fecha_ayer): {$count['total']}\n\n";

if ($count['total'] > 0) {
    $stmt = $pdo->prepare("SELECT id, fecha, metodo_pago, monto_total FROM ventas WHERE DATE(fecha) = ? ORDER BY id");
    $stmt->execute([$fecha_ayer]);
    $ventas_ayer = $stmt->fetchAll();
    
    foreach ($ventas_ayer as $v) {
        echo "  - Venta #{$v['id']}: {$v['fecha']} - {$v['metodo_pago']} - \${$v['monto_total']}\n";
    }
}

// Probar el endpoint de reportes con periodo ayer
echo "\n\n📊 PROBANDO ENDPOINT CON PERIODO='ayer':\n";
echo str_repeat("-", 80) . "\n";
$url = "http://localhost/kiosco/api/finanzas_completo.php?periodo=ayer&_t=" . time();
$response = @file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        $ventas_detalle = $data['componente_4_detalle_ventas']['ventas_individuales'] ?? [];
        echo "Ventas encontradas por el endpoint: " . count($ventas_detalle) . "\n";
        
        if (isset($data['periodo'])) {
            echo "Período devuelto:\n";
            echo "  - Tipo: {$data['periodo']['tipo']}\n";
            echo "  - Fecha inicio: {$data['periodo']['fecha_inicio']}\n";
            echo "  - Fecha fin: {$data['periodo']['fecha_fin']}\n";
        }
    } else {
        echo "❌ Endpoint no devolvió success\n";
    }
} else {
    echo "❌ No se pudo conectar al endpoint\n";
}
?>
