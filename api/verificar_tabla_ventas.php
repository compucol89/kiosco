<?php
/**
 * 🔍 VERIFICAR ESTRUCTURA DE TABLA VENTAS
 */

header('Content-Type: text/plain; charset=utf-8');

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    
    echo "🔍 ESTRUCTURA DE TABLA VENTAS:\n\n";
    
    $stmt = $pdo->prepare("DESCRIBE ventas");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columnas as $columna) {
        echo sprintf("%-25s %-20s %-10s %-10s %-10s %s\n", 
            $columna['Field'], 
            $columna['Type'], 
            $columna['Null'], 
            $columna['Key'], 
            $columna['Default'], 
            $columna['Extra']
        );
    }
    
    echo "\n🔍 COLUMNAS AFIP ESPECÍFICAS:\n\n";
    
    $columnas_afip = ['cae', 'numero_comprobante_fiscal', 'fecha_vencimiento_cae', 'tipo_comprobante', 'estado_afip'];
    
    foreach ($columnas as $columna) {
        if (in_array($columna['Field'], $columnas_afip)) {
            echo "✅ {$columna['Field']} - {$columna['Type']}\n";
        }
    }
    
    // Buscar columnas similares
    echo "\n🔍 COLUMNAS QUE CONTIENEN 'comprobante':\n\n";
    foreach ($columnas as $columna) {
        if (stripos($columna['Field'], 'comprobante') !== false) {
            echo "📋 {$columna['Field']} - {$columna['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
