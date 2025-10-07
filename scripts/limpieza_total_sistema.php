<?php
/**
 * scripts/limpieza_total_sistema.php
 * Limpieza COMPLETA del sistema - Solo conserva productos y usuarios
 * Propósito: Resetear completamente el sistema para empezar limpio
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧹 LIMPIEZA TOTAL DEL SISTEMA KIOSCO POS\n";
echo "======================================\n\n";

echo "⚠️ ATENCIÓN: Este script eliminará TODOS los datos excepto productos y usuarios\n";
echo "📊 Se eliminarán:\n";
echo "   • Todas las ventas y sus detalles\n";
echo "   • Todos los turnos de caja\n";
echo "   • Todos los movimientos de caja\n";
echo "   • Todo el historial de turnos\n";
echo "   • Cualquier configuración de caja\n";
echo "   • Logs y auditorías\n\n";

echo "✅ Se conservarán ÚNICAMENTE:\n";
echo "   • Productos (stock, precios, categorías)\n";
echo "   • Usuarios (cuentas, permisos)\n\n";

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=localhost;dbname=kiosco_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexión establecida\n\n";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

// Contar registros antes de la limpieza
echo "📊 CONTEO ANTES DE LA LIMPIEZA:\n";
echo "=============================\n";

$tablas = [
    'ventas' => 'Ventas',
    'detalle_ventas' => 'Detalles de ventas',
    'turnos_caja' => 'Turnos de caja',
    'movimientos_caja_detallados' => 'Movimientos de caja',
    'historial_turnos_caja' => 'Historial de turnos',
    'productos' => 'Productos (SE CONSERVAN)',
    'usuarios' => 'Usuarios (SE CONSERVAN)'
];

$conteoAntes = [];
foreach ($tablas as $tabla => $descripcion) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $resultado = $stmt->fetch();
        $conteoAntes[$tabla] = $resultado['total'];
        
        $conservar = (strpos($descripcion, 'SE CONSERVAN') !== false) ? '✅' : '🗑️';
        echo sprintf("  %s %-30s: %d registros\n", $conservar, $descripcion, $resultado['total']);
    } catch (Exception $e) {
        echo sprintf("  ⚠️  %-30s: Tabla no existe\n", $descripcion);
        $conteoAntes[$tabla] = 0;
    }
}

echo "\n🚀 INICIANDO LIMPIEZA...\n";
echo "=======================\n";

try {
    // Deshabilitar verificaciones de claves foráneas temporalmente
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. ELIMINAR DETALLES DE VENTAS (primero por claves foráneas)
    echo "🗑️ Eliminando detalles de ventas...\n";
    $stmt = $pdo->exec("DELETE FROM detalle_ventas");
    echo "   ✅ $stmt detalles eliminados\n";
    
    // 2. ELIMINAR VENTAS
    echo "🗑️ Eliminando ventas...\n";
    $stmt = $pdo->exec("DELETE FROM ventas");
    echo "   ✅ $stmt ventas eliminadas\n";
    
    // 3. ELIMINAR MOVIMIENTOS DE CAJA DETALLADOS
    echo "🗑️ Eliminando movimientos de caja...\n";
    $stmt = $pdo->exec("DELETE FROM movimientos_caja_detallados");
    echo "   ✅ $stmt movimientos eliminados\n";
    
    // 4. ELIMINAR HISTORIAL DE TURNOS
    echo "🗑️ Eliminando historial de turnos...\n";
    $stmt = $pdo->exec("DELETE FROM historial_turnos_caja");
    echo "   ✅ $stmt registros de historial eliminados\n";
    
    // 5. ELIMINAR TURNOS DE CAJA
    echo "🗑️ Eliminando turnos de caja...\n";
    $stmt = $pdo->exec("DELETE FROM turnos_caja");
    echo "   ✅ $stmt turnos eliminados\n";
    
    // 6. ELIMINAR OTRAS TABLAS RELACIONADAS (si existen)
    $tablasOpcionales = [
        'cierres_caja' => 'Cierres de caja',
        'arqueos_caja' => 'Arqueos de caja',
        'sesiones_caja' => 'Sesiones de caja',
        'configuracion_caja' => 'Configuración de caja',
        'logs_sistema' => 'Logs del sistema',
        'auditoria_movimientos' => 'Auditoría de movimientos'
    ];
    
    foreach ($tablasOpcionales as $tabla => $descripcion) {
        try {
            $stmt = $pdo->exec("DELETE FROM $tabla");
            echo "🗑️ $descripcion: $stmt registros eliminados\n";
        } catch (Exception $e) {
            // Tabla no existe, continuar
        }
    }
    
    // 7. RESETEAR AUTO_INCREMENT de las tablas limpiadas
    echo "\n🔄 Reseteando contadores AUTO_INCREMENT...\n";
    $tablasReset = ['ventas', 'detalle_ventas', 'turnos_caja', 'movimientos_caja_detallados', 'historial_turnos_caja'];
    
    foreach ($tablasReset as $tabla) {
        try {
            $pdo->exec("ALTER TABLE $tabla AUTO_INCREMENT = 1");
            echo "   ✅ $tabla: contador reseteado\n";
        } catch (Exception $e) {
            // Tabla no existe o no tiene AUTO_INCREMENT
        }
    }
    
    // Rehabilitar verificaciones de claves foráneas
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✅ Limpieza completada exitosamente\n\n";
    
} catch (Exception $e) {
    // Rehabilitar verificaciones en caso de error
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    die("❌ Error durante la limpieza: " . $e->getMessage() . "\n");
}

// Verificar el resultado
echo "📊 VERIFICACIÓN POST-LIMPIEZA:\n";
echo "=============================\n";

foreach ($tablas as $tabla => $descripcion) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $resultado = $stmt->fetch();
        $totalDespues = $resultado['total'];
        
        if (strpos($descripcion, 'SE CONSERVAN') !== false) {
            $estado = ($totalDespues == $conteoAntes[$tabla]) ? '✅ CONSERVADO' : '⚠️ MODIFICADO';
            echo sprintf("  %s %-30s: %d registros\n", $estado, $descripcion, $totalDespues);
        } else {
            $estado = ($totalDespues == 0) ? '✅ LIMPIO' : '⚠️ RESIDUOS';
            echo sprintf("  %s %-30s: %d registros\n", $estado, $descripcion, $totalDespues);
        }
    } catch (Exception $e) {
        echo sprintf("  ⚠️  %-30s: Error verificando\n", $descripcion);
    }
}

echo "\n🎉 LIMPIEZA TOTAL COMPLETADA\n";
echo "===========================\n";
echo "✅ El sistema ha sido completamente limpiado\n";
echo "✅ Solo se conservaron productos y usuarios\n";
echo "✅ Todos los contadores AUTO_INCREMENT fueron reseteados\n";
echo "✅ El sistema está listo para operaciones desde cero\n\n";

echo "🚀 PRÓXIMOS PASOS:\n";
echo "=================\n";
echo "1. Abrir la caja con monto inicial\n";
echo "2. Realizar ventas de prueba\n";
echo "3. Verificar que todos los módulos funcionen correctamente\n";
echo "4. Los reportes mostrarán solo datos nuevos\n";
echo "5. El inventario conserva todos los productos\n\n";

echo "💡 NOTA: Este reseteo es irreversible. Todos los datos de ventas,\n";
echo "    movimientos y turnos anteriores han sido eliminados permanentemente.\n";

?>

