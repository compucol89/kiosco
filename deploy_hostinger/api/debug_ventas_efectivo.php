<?php
/**
 * 🔍 DEBUG VENTAS EFECTIVO INCORRECTAS
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar turnos activos
    $stmt = $pdo->prepare("
        SELECT 
            id, fecha_apertura, estado,
            DATE(fecha_apertura) as fecha_turno
        FROM turnos_caja 
        WHERE estado = 'abierto'
    ");
    $stmt->execute();
    $turnoActivo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar ventas de ese día
    $stmt2 = $pdo->prepare("
        SELECT 
            id, fecha, monto_total, metodo_pago, estado,
            DATE(fecha) as fecha_venta
        FROM ventas 
        WHERE DATE(fecha) = ? 
        AND estado IN ('completado', 'completada')
        ORDER BY fecha DESC
    ");
    $stmt2->execute([$turnoActivo['fecha_turno']]);
    $ventasDelDia = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtrar solo efectivo
    $ventasEfectivo = array_filter($ventasDelDia, function($venta) {
        return $venta['metodo_pago'] === 'efectivo';
    });
    
    $totalVentasEfectivo = array_sum(array_column($ventasEfectivo, 'monto_total'));
    
    echo json_encode([
        'success' => true,
        'turno_activo' => $turnoActivo,
        'ventas_del_dia' => $ventasDelDia,
        'ventas_efectivo' => $ventasEfectivo,
        'total_ventas_efectivo' => $totalVentasEfectivo,
        'problema' => $totalVentasEfectivo > 0 ? 'Hay ventas de efectivo de otro turno del mismo día' : 'No debería haber ventas de efectivo'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>


















