<?php
/**
 * 🔍 VERIFICAR ESTRUCTURA DE TABLA TURNOS_CAJA
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'bd_conexion.php';
    
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener estructura de la tabla
    $stmt = $pdo->prepare("DESCRIBE turnos_caja");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener información adicional sobre columnas generadas
    $stmt2 = $pdo->prepare("
        SELECT 
            COLUMN_NAME,
            IS_NULLABLE,
            COLUMN_DEFAULT,
            EXTRA,
            GENERATION_EXPRESSION
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'tayrona_pos_v2' 
        AND TABLE_NAME = 'turnos_caja'
        AND COLUMN_NAME = 'efectivo_teorico'
    ");
    $stmt2->execute();
    $columnaGenerada = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'estructura_completa' => $columnas,
        'columna_efectivo_teorico' => $columnaGenerada,
        'es_columna_generada' => !empty($columnaGenerada['GENERATION_EXPRESSION'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>























