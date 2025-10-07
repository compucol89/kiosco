<?php
/**
 * 🔍 DEBUG DETALLE DE SALIDAS
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    
    // Obtener turno activo
    $stmt = $pdo->prepare("SELECT id FROM turnos_caja WHERE estado = 'abierto' LIMIT 1");
    $stmt->execute();
    $turnoId = $stmt->fetchColumn();
    
    // Obtener detalle de todos los movimientos
    $stmt2 = $pdo->prepare("
        SELECT 
            tipo,
            categoria,
            monto,
            descripcion,
            fecha_movimiento
        FROM movimientos_caja_detallados 
        WHERE turno_id = ?
        ORDER BY fecha_movimiento DESC
    ");
    $stmt2->execute([$turnoId]);
    $movimientos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Separar por tipo
    $ingresos = array_filter($movimientos, fn($m) => $m['tipo'] === 'ingreso');
    $egresos = array_filter($movimientos, fn($m) => $m['tipo'] === 'egreso');
    
    $totalIngresos = array_sum(array_column($ingresos, 'monto'));
    $totalEgresos = array_sum(array_map(fn($e) => abs($e['monto']), $egresos));
    
    echo json_encode([
        'success' => true,
        'turno_id' => $turnoId,
        'movimientos_completos' => $movimientos,
        'ingresos' => $ingresos,
        'egresos' => $egresos,
        'totales' => [
            'ingresos' => $totalIngresos,
            'egresos' => $totalEgresos
        ],
        'explicacion' => [
            'ventas_efectivo_separadas' => '18.000 (son ventas del POS, NO movimientos manuales)',
            'egresos_manuales' => $totalEgresos . ' (son retiros/gastos manuales)',
            'calculo_correcto' => 'Apertura + Ventas_POS - Egresos_manuales = 318.000 + 18.000 - ' . $totalEgresos
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>


















