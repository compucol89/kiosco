<?php
/**
 * 🔍 DEBUG TIEMPOS DE VENTAS VS TURNOS
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    
    // Obtener todos los turnos con sus fechas
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            fecha_apertura, 
            fecha_cierre, 
            estado,
            monto_apertura
        FROM turnos_caja 
        ORDER BY fecha_apertura DESC
        LIMIT 3
    ");
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener todas las ventas de efectivo
    $stmt2 = $pdo->prepare("
        SELECT 
            id,
            fecha,
            monto_total,
            metodo_pago
        FROM ventas 
        WHERE metodo_pago = 'efectivo'
        AND estado IN ('completado', 'completada')
        ORDER BY fecha DESC
        LIMIT 10
    ");
    $stmt2->execute();
    $ventas = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'turnos' => $turnos,
        'ventas_efectivo' => $ventas,
        'analisis' => [
            'turno_1' => $turnos[1] ?? null,
            'turno_2' => $turnos[0] ?? null,
            'explicacion' => 'Las ventas deben estar entre fecha_apertura y fecha_cierre del turno correspondiente'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>























