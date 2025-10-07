<?php
header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== ELIMINANDO VISTAS DUPLICADAS ===\n\n";

$pdo = Conexion::obtenerConexion();

$vistas = [
    'vista_productos_estadisticas',
    'vista_productos_ranking', 
    'vista_resumen_turnos',
    'vista_ventas_diario'
];

foreach ($vistas as $vista) {
    try {
        // Intentar con nombre simple
        $pdo->exec("DROP VIEW IF EXISTS `$vista`");
        echo "✅ Eliminada: $vista\n";
        
        // Intentar con prefijo de BD
        $pdo->exec("DROP VIEW IF EXISTS `kiosco_db`.`$vista`");
        echo "✅ Eliminada: kiosco_db.$vista\n";
        
    } catch (Exception $e) {
        echo "⚠️ $vista: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Proceso completado\n";
echo "🔄 Refresca HeidiSQL (F5) para verificar\n";
?>




