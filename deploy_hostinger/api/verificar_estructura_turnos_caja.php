<?php
/**
 * 🔍 VERIFICAR ESTRUCTURA TABLA TURNOS_CAJA
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    echo "🔍 VERIFICANDO ESTRUCTURA TABLA TURNOS_CAJA...\n\n";
    
    $pdo = Conexion::obtenerConexion();
    
    // Verificar estructura
    $stmt = $pdo->prepare("DESCRIBE turnos_caja");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 COLUMNAS EXISTENTES:\n";
    echo str_repeat("-", 60) . "\n";
    echo sprintf("%-25s %-20s %-10s %-10s\n", "Campo", "Tipo", "Nulo", "Default");
    echo str_repeat("-", 60) . "\n";
    
    foreach ($columnas as $columna) {
        echo sprintf("%-25s %-20s %-10s %-10s\n", 
            $columna['Field'],
            $columna['Type'],
            $columna['Null'],
            $columna['Default'] ?? 'NULL'
        );
    }
    
    // Verificar si existen las columnas necesarias
    $columnas_necesarias = [
        'entradas_efectivo',
        'salidas_efectivo', 
        'ventas_efectivo',
        'efectivo_teorico'
    ];
    
    echo "\n🔍 COLUMNAS NECESARIAS PARA CONTROL DE CAJA:\n";
    echo str_repeat("-", 40) . "\n";
    
    $columnas_existentes = array_column($columnas, 'Field');
    
    foreach ($columnas_necesarias as $col_necesaria) {
        $existe = in_array($col_necesaria, $columnas_existentes);
        echo ($existe ? "✅" : "❌") . " {$col_necesaria}\n";
    }
    
    // Si faltan columnas, mostrar SQL para agregarlas
    $columnas_faltantes = array_diff($columnas_necesarias, $columnas_existentes);
    
    if (!empty($columnas_faltantes)) {
        echo "\n🔧 SQL PARA AGREGAR COLUMNAS FALTANTES:\n";
        echo str_repeat("-", 50) . "\n";
        
        foreach ($columnas_faltantes as $col) {
            $sql = match($col) {
                'entradas_efectivo' => "ALTER TABLE turnos_caja ADD COLUMN entradas_efectivo DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Ingresos manuales de efectivo';",
                'salidas_efectivo' => "ALTER TABLE turnos_caja ADD COLUMN salidas_efectivo DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Egresos manuales de efectivo';",
                'ventas_efectivo' => "ALTER TABLE turnos_caja ADD COLUMN ventas_efectivo DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Total ventas en efectivo';",
                'efectivo_teorico' => "ALTER TABLE turnos_caja ADD COLUMN efectivo_teorico DECIMAL(12,2) AS (monto_apertura + COALESCE(entradas_efectivo, 0) + COALESCE(ventas_efectivo, 0) - COALESCE(salidas_efectivo, 0)) STORED COMMENT 'Efectivo teórico calculado';"
            };
            echo "{$sql}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
