<?php
/**
 * 🔧 ARREGLAR COLUMNA GENERADA efectivo_teorico
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    
    // Eliminar la columna generada actual
    $pdo->exec("ALTER TABLE turnos_caja DROP COLUMN efectivo_teorico");
    
    // Crear la nueva columna generada con la fórmula correcta
    $pdo->exec("
        ALTER TABLE turnos_caja 
        ADD COLUMN efectivo_teorico DECIMAL(12,2) 
        AS (monto_apertura + COALESCE(total_entradas, 0) + COALESCE(ventas_efectivo, 0) - COALESCE(total_salidas, 0)) 
        STORED
    ");
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Columna efectivo_teorico corregida exitosamente',
        'formula_nueva' => 'monto_apertura + total_entradas + ventas_efectivo - total_salidas'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>























