<?php
/**
 * 🔧 SCRIPT PARA CORREGIR EFECTIVO_TEORICO EN TURNOS_CAJA
 * Corrige el efectivo teórico de turnos que tienen datos inconsistentes
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener turnos con efectivo teórico potencialmente incorrecto
    $stmt = $pdo->prepare("
        SELECT 
            id,
            monto_apertura,
            ventas_efectivo,
            efectivo_teorico as efectivo_teorico_actual
        FROM turnos_caja 
        WHERE estado IN ('abierto', 'cerrado')
    ");
    
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $turnosCorregidos = 0;
    $correcciones = [];
    
    foreach ($turnos as $turno) {
        // Obtener movimientos de este turno
        $stmtMov = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) as ingresos_reales,
                COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN ABS(monto) ELSE 0 END), 0) as egresos_reales
            FROM movimientos_caja_detallados 
            WHERE turno_id = ?
        ");
        $stmtMov->execute([$turno['id']]);
        $movimientos = $stmtMov->fetch(PDO::FETCH_ASSOC);
        
        // Calcular efectivo teórico correcto
        $efectivoTeoricoCorrecto = floatval($turno['monto_apertura']) + 
                                  floatval($movimientos['ingresos_reales']) + 
                                  floatval($turno['ventas_efectivo']) - 
                                  floatval($movimientos['egresos_reales']);
        
        $efectivoTeorico = floatval($turno['efectivo_teorico_actual']);
        
        // Solo actualizar si hay diferencia
        if (abs($efectivoTeoricoCorrecto - $efectivoTeorico) > 0.01) {
            $stmtUpdate = $pdo->prepare("
                UPDATE turnos_caja SET 
                    efectivo_teorico = ? 
                WHERE id = ?
            ");
            
            $stmtUpdate->execute([$efectivoTeoricoCorrecto, $turno['id']]);
            
            $correcciones[] = [
                'turno_id' => $turno['id'],
                'efectivo_teorico_anterior' => $efectivoTeorico,
                'efectivo_teorico_corregido' => $efectivoTeoricoCorrecto,
                'diferencia_corregida' => $efectivoTeoricoCorrecto - $efectivoTeorico,
                'calculo' => [
                    'apertura' => floatval($turno['monto_apertura']),
                    'ingresos' => floatval($movimientos['ingresos_reales']),
                    'ventas_efectivo' => floatval($turno['ventas_efectivo']),
                    'egresos' => floatval($movimientos['egresos_reales'])
                ]
            ];
            
            $turnosCorregidos++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => "Se corrigieron $turnosCorregidos turnos",
        'turnos_corregidos' => $turnosCorregidos,
        'correcciones' => $correcciones
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al corregir efectivo teórico: ' . $e->getMessage()
    ]);
}
?>
