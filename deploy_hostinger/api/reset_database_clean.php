<?php
/**
 * api/reset_database_clean.php
 * Limpia la base de datos dejándola en estado inicial para pruebas
 * Mantiene usuarios y productos, elimina ventas y turnos
 * RELEVANT FILES: bd_conexion.php, reset_sistema.php
 */

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'bd_conexion.php';

echo "=== LIMPIEZA DE BASE DE DATOS ===\n\n";
echo "⚠️  IMPORTANTE: Este script eliminará todas las ventas y turnos\n";
echo "✅ Mantendrá: Usuarios y Productos\n\n";

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "1. Conectado a la base de datos exitosamente\n\n";
    
    // PASO 1: Eliminar movimientos de caja
    echo "2. Eliminando movimientos de caja...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM movimientos_caja_detallados");
    $count = $stmt->fetch();
    echo "   - Movimientos a eliminar: {$count['total']}\n";
    
    $pdo->exec("DELETE FROM movimientos_caja_detallados");
    echo "   ✅ Movimientos eliminados\n\n";
    
    // PASO 2: Eliminar historial de turnos
    if ($pdo->query("SHOW TABLES LIKE 'historial_turnos_caja'")->rowCount() > 0) {
        echo "3. Eliminando historial de turnos...\n";
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM historial_turnos_caja");
        $count = $stmt->fetch();
        echo "   - Registros a eliminar: {$count['total']}\n";
        
        $pdo->exec("DELETE FROM historial_turnos_caja");
        echo "   ✅ Historial eliminado\n\n";
    }
    
    // PASO 3: Eliminar turnos de caja
    echo "4. Eliminando turnos de caja...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turnos_caja");
    $count = $stmt->fetch();
    echo "   - Turnos a eliminar: {$count['total']}\n";
    
    $pdo->exec("DELETE FROM turnos_caja");
    echo "   ✅ Turnos eliminados\n\n";
    
    // PASO 4: Eliminar ventas
    echo "5. Eliminando ventas...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
    $count = $stmt->fetch();
    echo "   - Ventas a eliminar: {$count['total']}\n";
    
    $pdo->exec("DELETE FROM ventas");
    echo "   ✅ Ventas eliminadas\n\n";
    
    // PASO 5: Resetear AUTO_INCREMENT
    echo "6. Reseteando contadores...\n";
    $pdo->exec("ALTER TABLE ventas AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE turnos_caja AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE movimientos_caja_detallados AUTO_INCREMENT = 1");
    echo "   ✅ Contadores reseteados a 1\n\n";
    
    // PASO 6: Verificación final
    echo "7. Verificación final:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
    $ventas = $stmt->fetch();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turnos_caja");
    $turnos = $stmt->fetch();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM movimientos_caja_detallados");
    $movimientos = $stmt->fetch();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarios = $stmt->fetch();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos");
    $productos = $stmt->fetch();
    
    echo "   - Ventas: {$ventas['total']}\n";
    echo "   - Turnos: {$turnos['total']}\n";
    echo "   - Movimientos: {$movimientos['total']}\n";
    echo "   - Usuarios: {$usuarios['total']} (✅ mantenidos)\n";
    echo "   - Productos: {$productos['total']} (✅ mantenidos)\n\n";
    
    echo "=== ✅ LIMPIEZA COMPLETADA EXITOSAMENTE ===\n\n";
    echo "🎯 Base de datos lista para empezar desde cero\n";
    echo "📊 Próximos pasos:\n";
    echo "   1. Ir al Dashboard\n";
    echo "   2. Abrir una nueva caja con monto inicial\n";
    echo "   3. Realizar ventas de prueba\n";
    echo "   4. Verificar que todo funcione correctamente\n\n";
    
    echo json_encode([
        'success' => true,
        'message' => 'Base de datos limpiada exitosamente',
        'estadisticas' => [
            'ventas_eliminadas' => $count['total'],
            'usuarios_mantenidos' => $usuarios['total'],
            'productos_mantenidos' => $productos['total']
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>


