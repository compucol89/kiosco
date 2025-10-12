<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

$pdo = Conexion::obtenerConexion();
echo "=== ESTRUCTURA DE TABLA VENTAS ===\n\n";
$stmt = $pdo->query("DESCRIBE ventas");
$columns = $stmt->fetchAll();

foreach ($columns as $col) {
    echo "{$col['Field']} ({$col['Type']})\n";
}
?>








