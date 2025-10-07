<?php
/**
 * 🧹 LIMPIEZA COMPLETA DEL SISTEMA
 * Elimina TODOS los datos operativos manteniendo productos y usuarios
 * Ubicación: api/limpieza_completa_sistema.php
 * Propósito: Resetear sistema a estado inicial limpio
 */

header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'bd_conexion.php';

try {
    $pdo = Conexion::obtenerConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Iniciar transacción para seguridad
    $pdo->beginTransaction();
    
    echo json_encode(['status' => 'iniciando', 'mensaje' => 'Iniciando limpieza completa...']) . "\n";
    
    // 🗑️ LIMPIAR DATOS OPERATIVOS
    $tablasLimpiar = [
        'ventas' => 'Ventas del POS',
        'turnos_caja' => 'Turnos de caja',
        'movimientos_caja_detallados' => 'Movimientos de caja',
        'historial_turnos_caja' => 'Historial de turnos',
    ];
    
    $resultados = [];
    
    foreach ($tablasLimpiar as $tabla => $descripcion) {
        try {
            // Contar registros antes de eliminar
            $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM {$tabla}");
            $stmtCount->execute();
            $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Eliminar todos los registros
            $stmtDelete = $pdo->prepare("DELETE FROM {$tabla}");
            $stmtDelete->execute();
            $eliminados = $stmtDelete->rowCount();
            
            // Resetear AUTO_INCREMENT
            $stmtReset = $pdo->prepare("ALTER TABLE {$tabla} AUTO_INCREMENT = 1");
            $stmtReset->execute();
            
            $resultados[$tabla] = [
                'descripcion' => $descripcion,
                'registros_eliminados' => $eliminados,
                'total_original' => $total,
                'estado' => 'limpiado'
            ];
            
            echo json_encode([
                'status' => 'progreso', 
                'tabla' => $tabla, 
                'eliminados' => $eliminados,
                'descripcion' => $descripcion
            ]) . "\n";
            
        } catch (Exception $e) {
            $resultados[$tabla] = [
                'descripcion' => $descripcion,
                'error' => $e->getMessage(),
                'estado' => 'error'
            ];
        }
    }
    
    // 🧹 LIMPIAR CACHE DEL SISTEMA
    $cacheDir = __DIR__ . '/cache/';
    $archivosCache = 0;
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $archivosCache++;
            }
        }
    }
    
    // 📊 VERIFICAR PRODUCTOS Y USUARIOS (NO TOCAR)
    $stmtProductos = $pdo->prepare("SELECT COUNT(*) as total FROM productos");
    $stmtProductos->execute();
    $totalProductos = $stmtProductos->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtUsuarios = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmtUsuarios->execute();
    $totalUsuarios = $stmtUsuarios->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Confirmar transacción
    $pdo->commit();
    
    // 🎉 RESPUESTA FINAL
    echo json_encode([
        'success' => true,
        'mensaje' => 'Limpieza completa exitosa',
        'resultados' => $resultados,
        'preservados' => [
            'productos' => $totalProductos,
            'usuarios' => $totalUsuarios
        ],
        'cache_limpiado' => $archivosCache,
        'timestamp' => date('Y-m-d H:i:s')
    ]) . "\n";
    
} catch (Exception $e) {
    // Rollback en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]) . "\n";
}
?>














