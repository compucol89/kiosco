<?php
/**
 * 🔍 TEST DATOS PARA MODAL CIERRE
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Simular obtenerEstadoCaja
    $usuarioId = 1;
    
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            u.nombre as cajero_nombre,
            -- Cálculos de movimientos manuales
            COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.monto ELSE 0 END), 0) as entradas_efectivo,
            COALESCE(SUM(CASE WHEN m.tipo = 'egreso' THEN ABS(m.monto) ELSE 0 END), 0) as salidas_efectivo,
            COUNT(CASE WHEN m.tipo IN ('ingreso', 'egreso') THEN 1 END) as movimientos_manuales,
            -- Cálculos de ventas reales del sistema
            COALESCE((
                SELECT SUM(v.monto_total) 
                FROM ventas v 
                WHERE DATE(v.fecha) = DATE(t.fecha_apertura) 
                AND v.estado IN ('completado', 'completada')
                AND v.metodo_pago = 'efectivo'
            ), 0) as ventas_efectivo_reales
        FROM turnos_caja t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN movimientos_caja_detallados m ON t.id = m.turno_id
        WHERE t.estado = 'abierto'
        GROUP BY t.id
        LIMIT 1
    ");
    $stmt->execute();
    $turnoActivo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$turnoActivo) {
        echo json_encode(['error' => 'No hay turno activo']);
        exit;
    }
    
    // Calcular efectivo teórico correcto
    $efectivoTeorico = $turnoActivo['monto_apertura'] + $turnoActivo['entradas_efectivo'] + $turnoActivo['ventas_efectivo_reales'] - $turnoActivo['salidas_efectivo'];
    $turnoActivo['efectivo_teorico'] = $efectivoTeorico;
    
    echo json_encode([
        'success' => true,
        'turno_completo' => $turnoActivo,
        'campos_frontend' => [
            'monto_apertura' => $turnoActivo['monto_apertura'],
            'total_entradas_efectivo' => floatval($turnoActivo['monto_apertura']) + floatval($turnoActivo['entradas_efectivo']),
            'salidas_efectivo_reales' => $turnoActivo['salidas_efectivo'],
            'efectivo_teorico' => $efectivoTeorico
        ],
        'calculo_correcto' => [
            'apertura' => floatval($turnoActivo['monto_apertura']),
            'entradas' => floatval($turnoActivo['entradas_efectivo']),
            'ventas' => floatval($turnoActivo['ventas_efectivo_reales']),
            'salidas' => floatval($turnoActivo['salidas_efectivo']),
            'resultado' => $efectivoTeorico
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>























