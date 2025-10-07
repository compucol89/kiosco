<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== VERIFICACIÓN DE TURNOS Y VENTAS ===\n\n";

$pdo = Conexion::obtenerConexion();

// Ver turnos
echo "📊 TURNOS EN LA BASE DE DATOS:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("SELECT * FROM turnos_caja ORDER BY id");
$turnos = $stmt->fetchAll();

foreach ($turnos as $t) {
    echo "Turno #{$t['id']}:\n";
    echo "  Estado: {$t['estado']}\n";
    echo "  Apertura: {$t['fecha_apertura']}\n";
    echo "  Monto apertura: \${$t['monto_apertura']}\n";
    echo "  Ventas: {$t['cantidad_ventas']}\n";
    echo "  Efectivo: \${$t['ventas_efectivo']}\n\n";
}

echo "\n📋 VENTAS DE HOY:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("SELECT id, fecha, metodo_pago, monto_total FROM ventas WHERE DATE(fecha) = CURDATE() ORDER BY id");
$ventas = $stmt->fetchAll();

echo "Total ventas: " . count($ventas) . "\n\n";
foreach ($ventas as $v) {
    echo "Venta #{$v['id']}: {$v['fecha']} - {$v['metodo_pago']} - \${$v['monto_total']}\n";
}

echo "\n\n💰 MOVIMIENTOS DE CAJA POR TURNO:\n";
echo str_repeat("-", 80) . "\n";
$stmt = $pdo->query("
    SELECT turno_id, tipo, COUNT(*) as cantidad, SUM(monto) as total
    FROM movimientos_caja_detallados
    GROUP BY turno_id, tipo
    ORDER BY turno_id, tipo
");
$movimientos = $stmt->fetchAll();

foreach ($movimientos as $m) {
    echo "Turno #{$m['turno_id']} - {$m['tipo']}: {$m['cantidad']} movimientos = \${$m['total']}\n";
}
?>


