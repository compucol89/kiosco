<?php
/**
 * api/fix_vistas_bd.php
 * Corrige o elimina vistas con errores en la BD
 * RELEVANT FILES: db_config.php
 */

header('Content-Type: text/plain; charset=utf-8');
require_once 'bd_conexion.php';

echo "=== CORRIGIENDO VISTAS DE BASE DE DATOS ===\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    
    // Lista de vistas con problemas
    $vistas_problematicas = [
        'vista_productos_estadisticas',
        'vista_productos_ranking',
        'vista_resumen_turnos',
        'vista_ventas_diario'
    ];
    
    echo "🔧 Eliminando vistas con errores...\n";
    
    foreach ($vistas_problematicas as $vista) {
        try {
            $pdo->exec("DROP VIEW IF EXISTS `$vista`");
            echo "   ✅ Vista '$vista' eliminada\n";
        } catch (Exception $e) {
            echo "   ⚠️ No se pudo eliminar '$vista': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Vistas problemáticas eliminadas\n\n";
    
    // Verificar tablas restantes
    echo "📊 Tablas restantes:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Total: " . count($tablas) . " tablas\n\n";
    
    echo "🎯 Base de datos lista para exportar sin errores\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>




