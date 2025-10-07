<?php
/**
 * 🔧 CORRECCIÓN ESPECÍFICA PARA TURNO #1
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Datos del turno #1
    $apertura = 10000;
    $ingresos = 300000; // Karen huevos
    $egresos = 100000;  // Pago Arcor
    $ventas_efectivo = 9000;
    
    // Cálculo correcto
    $efectivo_teorico_correcto = $apertura + $ingresos + $ventas_efectivo - $egresos;
    
    // Actualizar turno #1
    $stmt = $pdo->prepare("UPDATE turnos_caja SET efectivo_teorico = ? WHERE id = 1");
    $stmt->execute([$efectivo_teorico_correcto]);
    
    echo json_encode([
        'success' => true,
        'turno_id' => 1,
        'calculo' => [
            'apertura' => $apertura,
            'ingresos' => $ingresos,
            'ventas_efectivo' => $ventas_efectivo,
            'egresos' => $egresos,
            'efectivo_teorico_correcto' => $efectivo_teorico_correcto
        ],
        'mensaje' => 'Turno #1 corregido exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>


















