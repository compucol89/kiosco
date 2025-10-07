<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== ESTRUCTURA DE TABLA USUARIOS ===\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    
    // Ver estructura de la tabla
    echo "Columnas de la tabla usuarios:\n";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n\nPrimeros 3 usuarios:\n";
    $stmt = $pdo->query("SELECT * FROM usuarios LIMIT 3");
    $usuarios = $stmt->fetchAll();
    
    echo json_encode($usuarios, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>


