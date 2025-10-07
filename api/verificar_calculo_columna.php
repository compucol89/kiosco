<?php
/**
 * 🔍 VERIFICAR CÁLCULO DE COLUMNA GENERADA
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener información de la columna generada
    $stmt = $pdo->prepare("
        SELECT 
            COLUMN_NAME,
            GENERATION_EXPRESSION
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'tayrona_pos_v2' 
        AND TABLE_NAME = 'turnos_caja'
        AND COLUMN_NAME = 'efectivo_teorico'
    ");
    $stmt->execute();
    $columna = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener datos del turno actual
    $stmt2 = $pdo->prepare("
        SELECT 
            id,
            monto_apertura,
            total_entradas,
            total_salidas,
            ventas_efectivo,
            efectivo_teorico
        FROM turnos_caja 
        WHERE estado = 'abierto'
        LIMIT 1
    ");
    $stmt2->execute();
    $turno = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    // Calcular manualmente
    $calculoManual = $turno['monto_apertura'] + $turno['total_entradas'] + $turno['ventas_efectivo'] - $turno['total_salidas'];
    
    echo json_encode([
        'success' => true,
        'columna_generada' => $columna,
        'turno_actual' => $turno,
        'calculo_manual' => $calculoManual,
        'diferencia' => $turno['efectivo_teorico'] - $calculoManual
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>























