<?php
/**
 * 🧹 LIMPIEZA FINAL DEL SISTEMA
 * Elimina TODOS los datos operativos - DEFINITIVO
 */

header('Content-Type: application/json; charset=UTF-8');

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Eliminar TODOS los datos operativos
    // 🔥 ORDEN CORRECTO PARA EVITAR VIOLACIONES DE FOREIGN KEY
    $queries = [
        "SET FOREIGN_KEY_CHECKS = 0",  // Deshabilitar checks temporalmente
        "DELETE FROM historial_turnos_caja",
        "DELETE FROM movimientos_caja_detallados", 
        "DELETE FROM ventas",
        "DELETE FROM turnos_caja",
        "SET FOREIGN_KEY_CHECKS = 1",   // Rehabilitar checks
        "ALTER TABLE ventas AUTO_INCREMENT = 1",
        "ALTER TABLE turnos_caja AUTO_INCREMENT = 1",
        "ALTER TABLE movimientos_caja_detallados AUTO_INCREMENT = 1",
        "ALTER TABLE historial_turnos_caja AUTO_INCREMENT = 1"
    ];
    
    $executed_queries = [];

    foreach ($queries as $query) {
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $executed_queries[] = $query . " - OK";
        } catch (Exception $e) {
            $executed_queries[] = $query . " - ERROR: " . $e->getMessage();
        }
    }
    
    // Verificar productos y usuarios
    $stmtProductos = $pdo->prepare("SELECT COUNT(*) as total FROM productos");
    $stmtProductos->execute();
    $totalProductos = $stmtProductos->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtUsuarios = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmtUsuarios->execute();
    $totalUsuarios = $stmtUsuarios->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Sistema completamente limpio',
        'queries_ejecutadas' => $executed_queries,
        'productos_preservados' => $totalProductos,
        'usuarios_preservados' => $totalUsuarios,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
