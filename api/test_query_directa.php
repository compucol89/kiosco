<?php
/**
 * 🔍 TEST QUERY DIRECTA VENTAS TURNO
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    
    // Query exacta que se usa en gestion_caja_completa.php
    $stmt = $pdo->prepare("
        SELECT 
            t.id as turno_id,
            t.fecha_apertura,
            t.fecha_cierre,
            COALESCE((
                SELECT SUM(v.monto_total) 
                FROM ventas v 
                WHERE v.fecha >= t.fecha_apertura 
                AND (t.fecha_cierre IS NULL OR v.fecha <= t.fecha_cierre)
                AND v.estado IN ('completado', 'completada')
                AND v.metodo_pago = 'efectivo'
            ), 0) as ventas_efectivo_reales
        FROM turnos_caja t
        WHERE t.estado = 'abierto'
    ");
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // También obtener las ventas que cumplen la condición
    $stmt2 = $pdo->prepare("
        SELECT v.* 
        FROM ventas v, turnos_caja t
        WHERE t.estado = 'abierto'
        AND v.fecha >= t.fecha_apertura 
        AND (t.fecha_cierre IS NULL OR v.fecha <= t.fecha_cierre)
        AND v.estado IN ('completado', 'completada')
        AND v.metodo_pago = 'efectivo'
    ");
    $stmt2->execute();
    $ventasQueCoinciden = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'resultado_query' => $resultado,
        'ventas_que_coinciden' => $ventasQueCoinciden,
        'deberia_ser_cero' => empty($ventasQueCoinciden)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>























